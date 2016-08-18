<?php

/*
Plugin Name: Content Sectioner
Plugin URI: http://kirkbowerssoftware.com
Description: Provides a mechanism for theme developers to easily modify the content of a page based on regular expression markers.  This allows theme consumers to create content in one long continuous stream that later is modified to insert sections, backgrounds, or what have you.
Version: 0.1.1
Author: Kirk Bowers
Author URI: http://kirkbowers.com
license: GPLv2
*/


class ContentSectioner {
  function __construct($inside_wp = true) {
    $this->rules = array();
    
    if ($inside_wp) {
      add_filter('the_content', array($this, 'go'));
    }
  }
  
  private function merge_defaults($opts) {
    $default_opts = array(
      'open_insert' => '',
      'open_policy' => 'replace',
      'open_tag' => '',
      'open_regex' => false,
      'close_insert' => '',
      'close_policy' => 'replace',
      'close_tag' => false,
      'close_regex' => false
    );
    
    return array_merge($default_opts, $opts);  
  }
  
  function replace_all($opts) {
    $opts['when'] = 'all';
    $opts = $this->merge_defaults($opts);
  
    $this->rules[] = $opts;
  }  
  
  function replace_first($opts) {
    $opts['when'] = 'first';
    $opts = $this->merge_defaults($opts);
  
    $this->rules[] = $opts;
  }

  function replace_next($opts) {
    $opts['when'] = 'next';
    $opts = $this->merge_defaults($opts);
  
    $this->rules[] = $opts;
  }

  function go($content) {
    $this->next_offset = 0;    
    $this->offset = 0;
    foreach ($this->rules as $opts) {
      if ($opts['when'] == 'first') {
        // Reset the uber offset and the current offset
        $this->next_offset = 0;    
        $this->offset = 0;
        $content = $this->replace($content, $opts); 
        // Advance the uber offset
        $this->next_offset = $this->offset;     
      } else if ($opts['when'] == 'next' ) {  
        if ($this->next_offset >= 0) {
          // Set the current offset to the uber
          $this->offset = $this->next_offset;
          $content = $this->replace($content, $opts);      
          // Advance the uber offset
          $this->next_offset = $this->offset;     
        }
      } else if ($opts['when'] == 'all' ) {
        $this->offset = 0;
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
      return '/<' . $regex . '(>|\\/>|\s[^>]*>)/';    
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
          $content = $maybe_content . $opts['close_insert'];  
          $this->offset = -1;  
        }
      } else {
        $content = $maybe_content;
      }
      
    } else {
      $this->offset = -1;
    }
    
    return $content;
  }

}