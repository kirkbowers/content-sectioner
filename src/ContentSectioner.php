<?php

/**
Plugin Name: Content Sectioner
Plugin URI: http://kirkbowers.com/plugins/content-sectioner
Description: Provides a mechanism for theme developers to easily modify the content of a page based on tag markers.  This allows theme consumers to create content in one long continuous stream that later is modified to insert sections, backgrounds, or what have you.
Version: 1.0.0
Author: Kirk Bowers
Author URI: http://kirkbowers.com
license: GPLv2
*/

/**
 * Provides a mechanism for WordPress theme developers to easily modify the content of an HTML page based on tag markers.  
 *
 * Content is filtered by a sequence of replacement rules.  Rules tell the filter how
 * to match a block of HTML, what HTML code to insert relative to the matched block,
 * and where the new code is to be inserted.
 *
 * A block is defined by an opening match and optionally a closing match.  If only an
 * opening is provided, the block is only the tag matched.  If both an opening and closing
 * are provided, the block includes everything from the opening match to the closing 
 * match.
 * 
 * To make that concrete, consider an `h2` element:
 *
 *     <h2>My Heading</h2>
 *
 * The block for a rule matching only the opening `'h2'` is strictly the opening tag:
 *
 *     <h2>
 *
 * Whereas the block for a rule matching the opening `'h2'` and the closing `'/h2'` 
 * contains the entire heading, including the text "My Heading".
 *
 * The replacement rules are added to the content sectioner by calling the `replace_*` methods.  The rules are run in the order they are added.  The only difference between the different replace methods is where within the content the current and next search are performed.
 *
 * @author Kirk Bowers <kirk@kirkbowers.com>
 */
class ContentSectioner {
  /**
   * Creates a new instance with no replacement rules.
   *
   * The constructor will assume that this class is being used inside of WordPress.
   * So, by default, a filter on `the_content` is automatically placed for you upon
   * instantiation.  To disable this behavior, pass `false` to the constructor.
   *
   * @param $inside_wp Whether or not the class is being used inside of WordPress.  If
   *    `true` (the default), a filter hook on `the_content` will be added automatically.
   */
  function __construct($inside_wp = true) {
    if ($inside_wp) {
      add_filter('the_content', array($this, 'go'));
    }
  }
  
  // The array of replacement rules.  It should not be manipulated directly, but rather
  // rules should be added via the `add_rule` method.
  private $rules = array();
  
  protected function add_rule($when, $opts) {
    $opts['when'] = $when;
    
    $opts = $this->merge_defaults($opts);
  
    $this->rules[] = $opts;  
  }
  
  /**
   * Takes the supplied replacement rule and fills in defaults for values not supplied.
   *
   * A replacement rule, as provided to any of the `replace_*` methods, is an array 
   * with certain expected option key values.  Those keys that may be supplied, 
   * their default values, and the affect each option has on how a replacement rule
   * does its job, are:
   * 
   * - `open_tag` (required, unless `open_regex` is provided) The tag to be matched to 
   *   fire this rule.  It is specified as
   *   a simple string without the angle brackets.  For example, `'h2'` matches a second
   *   level heading tag.  To match a close tag, include the leading forward slash (eg.
   *   `'/h2'` to match closing a second level heading).
   * - `open_insert` (default: the empty string `''`) The HTML to insert when this rule is fired at the 
   *   opening of the matched block.
   * - `open_policy` (default: `'replace'`) How the `open_insert` text should be inserted 
   *   into the content.  The choices are:
   *   - `'replace'` to completely replace the opening match and start the next search at the end of the replacement text
   *   - `'before'` to insert the text just before the opening match and start the next search at the end of the _opening match_ (excluding the opening match from the next search in order to protect against infinite loops).
   *   - `'after'` to insert the text just after the opening match and start the next search at the end of the inserted text
   * - `open_regex` (default: `false`) If provided, it will override the `open_tag` value.  To be used if finer grain matching is needed.  A full regex string should be supplied, with opening and closing slashes (eg. `'/<hr.*>/'`)
   * - `close_tag` (default: `false`) If provided, the tag to match to serve as the end of
   *   the matched block.  If the tag specified is not encountered, the end of the content
   *   will serve as a match.
   * - `close_insert` (default: the empty string `''`) The HTML to insert at the 
   *   closing of the matched block.
   * - `close_policy` (default: `'replace'`) How the `close_insert` text should be inserted 
   *   into the content.  The choices are:
   *   - `'replace'` to completely replace the closing match and start the next search at the end of the replacement text
   *   - `'before'` to insert the text just before the closing match and start the next search at the end of the _inserted text_ (leaving the closing match available to be matched as the opening of the next rule)
   *   - `'after'` to insert the text just after the closing match and start the next search at the end of the inserted text
   * - `close_regex` (default: `false`) If provided, it will override the `close_tag` value.  To be used if finer grain matching is needed.  A full regex string should be supplied, with opening and closing slashes (eg. `'/<hr.*>/'`)
   * - 'close_strict' (default: 'false') If false, the end of the content will serve
   *   as a match for the close tag or regex if no exact match is found.  If true, the
   *   replacement rule will not fire unless a strict match is found.
   *
   * @param $opts A replacement rule array that may need default values filled in.
   * @return array A copy of the supplied replacement rule array with defaults filled in where
   *   not overridden by the supplied params.
   * @see \ContentSectioner::replace_first()
   * @see \ContentSectioner::replace_next()
   * @see \ContentSectioner::replace_all()
   * @see \ContentSectioner::replace_remaining()
   */
  protected function merge_defaults($opts) {
    $default_opts = array(
      'open_insert' => '',
      'open_policy' => 'replace',
      'open_tag' => '',
      'open_regex' => false,
      'close_insert' => '',
      'close_policy' => 'replace',
      'close_tag' => false,
      'close_regex' => false,
      'close_strict' => false
    );
    
    return array_merge($default_opts, $opts);  
  }
  
  /**
   * Replace every block that matches the supplied rule. 
   *
   * Replace every block that matches starting at the beginning of the 
   * content through to the end of the content.  The next search performed will be
   * started at the beginning of the content.
   *
   * @param $opts An array with keys and values defining the replacement rule.
   * @see \ContentSectioner::merge_defaults() for the expected keys and values
   */
  function replace_all($opts) {
    $this->add_rule('all', $opts);
  }  
  
  /**
   * Replace every remaining block that matches the supplied rule found after the previous match. 
   *
   * Replace every block that matches starting where the previous replacement rule
   * left off through to the end of the content.  The next search performed will be
   * started at the beginning of the content.
   *
   * @param $opts An array with keys and values defining the replacement rule.
   * @see \ContentSectioner::merge_defaults() for the expected keys and values
   */
  function replace_remaining($opts) {
    $this->add_rule('remaining', $opts);
  }  
  
  /**
   * Replace the first block that matches the supplied rule. 
   *
   * Replace only the first block that matches starting at the beginning of the 
   * content.  The next search performed will be
   * started just after the last character of this replacement block.  Which character
   * is deemed to be the "last character" depends on the replacement policy and 
   * whether or not the rule provided a closing match.
   *
   * @param $opts An array with keys and values defining the replacement rule.
   * @see \ContentSectioner::merge_defaults() for the expected keys and values
   */
  function replace_first($opts) {
    $this->add_rule('first', $opts);
  }

  /**
   * Replace the next block that matches the supplied rule found after the previous match. 
   *
   * Replace only the next block that matches starting where the previous replacement rule
   * left off.  The next search performed will be
   * started just after the last character of this replacement block.  Which character
   * is deemed to be the "last character" depends on the replacement policy and 
   * whether or not the rule provided a closing match.
   *
   * @param $opts An array with keys and values defining the replacement rule.
   * @see \ContentSectioner::merge_defaults() for the expected keys and values
   */
  function replace_next($opts) {
    $this->add_rule('next', $opts);
  }

  /**
   * Applies all the replacement rule filters to the provided content.
   *
   * If you are using this class within WordPress, you will not need to call this method
   * directly.  It will be fired for you at the `the_content` filter hook.
   *
   * @param string $content The content to be filtered.
   * @return string The resulting filtered content.
   */
  function go($content) {
    $this->offset = 0;
    foreach ($this->rules as $opts) {
      if ($opts['when'] == 'first') {
        // Reset the current offset
        $this->offset = 0;
        $content = $this->replace($content, $opts); 
      } else if ($opts['when'] == 'next' ) {  
        if ($this->offset >= 0) {
          $content = $this->replace($content, $opts);      
        }
      } else if (($opts['when'] == 'all') || ($opts['when'] == 'remaining')) {
        if ($opts['when'] == 'all') {
          $this->offset = 0;
        }
        $i = 1;
        while ($this->offset >= 0) {
          // This takes advantage of PHP's copy on modify feature.  The original
          // $opts array is preserved, with the mustaches intact.
          $occurance_opts = $opts;
          $occurance_opts['open_insert'] = preg_replace('/\{\{\s*i\s*\}\}/', $i, $opts['open_insert']);    
          $occurance_opts['close_insert'] = preg_replace('/\{\{\s*i\s*\}\}/', $i, $opts['close_insert']);    
  
          $content = $this->replace($content, $occurance_opts);
            
          $i += 1;
        }
        
        // Reset to the beginning
        $this->offset = 0;  
      }
    }
    
    return $content;
  }

  private function make_regex($tag, $regex) {
    if ($regex) {
      return $regex;
    } else if ($tag) {
      $regex = $tag;
      // If the first character is a forward slash, that means we're trying to match a
      // closing tag.  We need to escape the forward slash since otherwise it would
      // close the regex.
      if ($regex[0] == '/') {
        $regex = '\\' . $regex;
      }
      return '/<' . $regex . '(>|\\/>|\s[^>]*>)/i';    
    } else {
      return false;
    }
  }

  private function replace($content, $opts) {
    $regex = $this->make_regex($opts['open_tag'], $opts['open_regex']);
  
    if (preg_match($regex, $content, $matches, PREG_OFFSET_CAPTURE, $this->offset)) {
      $pos = $matches[0][1];
      $matched_string = $matches[0][0];
      $matched_len = strlen($matched_string);
      $insert_string = $opts['open_insert'];
      $insert_len = strlen($insert_string);
      
      if ($opts['open_policy'] == 'before') {
        $advance = $matched_len + $insert_len;
        $maybe_content = substr_replace($content, $insert_string, $pos, 0);
      } else if ($opts['open_policy'] == 'after') {
        $advance = $matched_len + $insert_len;
        $maybe_content = substr_replace($content, $insert_string, $pos + $matched_len, 0);      
      } else {
        $advance = $insert_len;
        $maybe_content = substr_replace($content, $insert_string, $pos, $matched_len);            
      }

      $this->offset = $pos + $advance;
      
      $regex = $this->make_regex($opts['close_tag'], $opts['close_regex']);
      
      if ($regex) {
        if (preg_match($regex, $maybe_content, $matches, PREG_OFFSET_CAPTURE, $this->offset)) {

          $pos = $matches[0][1];
          $matched_string = $matches[0][0];
          $matched_len = strlen($matched_string);
          $insert_string = $opts['close_insert'];
          $insert_len = strlen($insert_string);
      
          if ($opts['close_policy'] == 'before') {
            $advance = $insert_len;
            $content = substr_replace($maybe_content, $insert_string, $pos, 0);
          } else if ($opts['close_policy'] == 'after') {
            $advance = $matched_len + $insert_len;
            $content = substr_replace($maybe_content, $insert_string, $pos + $matched_len, 0);      
          } else {
            $advance = $insert_len;
            $content = substr_replace($maybe_content, $insert_string, $pos, $matched_len);            
          }

          $this->offset = $pos + $advance;

      
        } else {
          // We've fallen off the end
          // Go ahead and count this as a match, but only if we strict isn't true
          if (! $opts['close_strict']) {
            $content = $maybe_content . $opts['close_insert'];  
            $this->offset = -1;
          }
        }
      } else {
        $content = $maybe_content;
      }
      
    } else {
      $this->offset = -1;
    }
    
    return $content;
  }

  /**
   * Provide instructions to the content author which elements on the page will trigger
   * the sectioning.
   *
   * @param $slug The slug of the page for which instructions 
   *    should appear while being edited.
   * @param $instructions The instructions to provide.  It is quasi-HTML.  It will be
   *    surrounded by a `<p>` and `</p>` for you, so if you need additional paragraphs,
   *    you can close the implied `<p>` and re-open it for the implied closing tag.
   */
  static function provide_instructions($slug, $instructions) {
    add_action('admin_notices', function() use ($slug, $instructions) {
      global $post;

      if ($post && ($post->post_name == $slug)) {
?>
  <div class="notice notice-info is-dismissible">
    <h2>Instructions</h2>
    <p>
      <?php echo $instructions ?>
    </p>
  </div>

<?php
      }
    });
  }
}
