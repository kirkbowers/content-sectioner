=== Content Sectioner ===
Contributors: kirkbowers
Tags: developer, content filtering, content formatting
Requires at least: 3.1.0
Tested up to: 4.6
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Content Sectioner is a developer plugin that makes it easy to insert formatting markup (div and img tags) into long pieces of content.

== Description ==

Most modern websites have pages that break the content into multiple sections, with changing background colors and graphics marking the breaks between sections.  In order for a page to be broken into sections, typically there needs to be additional markup within the HTML (ie. `div` tags) that can be targeted in the stylesheet for formatting.  However, most content authors don't want to think about techie stuff like `div` tags, and prefer to work in the Visual editor which only provides for semantic markup ("Paragraph", "Heading 1", etc.).

Content Sectioner takes advantage of WordPress's content filtering mechanism and makes it easy for a theme developer to insert the necessary sectioning markup auto-magically while allowing content authors to still work as they prefer.  It looks for specified landmarks within the content (such as `h3` tags) and performs the necessary code insertions or replacements.  It does this using a concise and easy-to-use notation, relieving the theme developer from having to write (often repetitive) regular expressions and string manipulation.

#### A concrete example

Suppose you're working on a site and you want the About page (with slug `about`) to have an inset in the middle of it with a blue background setting it apart.  The inset should contain the first occurence of a "Heading 3" and all the paragraphs up until just before the next "Heading 2".  The stylesheet will apply the changing background to elements with the selector `.inset`.

Assuming your `index.php` file contains something like this:

    <?php
    
      $current_slug = get_queried_object()->post_name;
      
      get_template_part('content', $current_slug);
      
    ?>
    
Then in the file `content-about.php`, you can set up the Content Sectioner like so:

    <?php
    
      $sectioner = new ContentSectioner();
      
      $sectioner->replace_first(
        array(
          'open_tag' => 'h3',
          'open_insert' => '<div class="inset">',
          'open_policy' => 'before',
          'close_tag' => 'h2',
          'close_insert' => '</div>',
          'close_policy' => 'before'
        )
      );

      // Do the usual Loop thing here...
    ?>
    
The About page (and only the About page) will have this inset section inserted.

You can provide replacement rules that replace/insert at the first occurrence of a match, the next occurrence, all remaining occurrences, or all occurrences in the entire piece of content.  You can place the inserted HTML before or after a match, or replace the match.  Also, you can match a closing tag by simply providing the preceding slash character (eg. '/h2').  Tag matches will match against any variant of a tag (upper or lowercase, with or without attributes, as an opening tag or as a self-closing tag like `<hr />`).  In the rare case that something other than a tag needs to be matched, you can supply a raw regex instead of a tag.

#### Providing instructions

As the theme developer, you likely would want to let the content authors know that this magic insertion of sections will occur, and what landmarks need to be present in their content to trigger the sectioning.

In some file in your theme that gets loaded for every page (most likely `functions.php`), you can provide such instructions along with the slug for the page to which the instructions apply.  The instructions will appear at the top of the Edit Page page in the admin.

    ContentSectioner::provide_instructions('about', "
      A blue background will be placed behind everything starting at the first Heading 3
      through just before the next Heading 2.");

#### Full User Guide

For full documentation and more sample use cases, visit [the Content Sectioner homepage](http://www.kirkbowers.com/plugins/content-sectioner).

== Installation ==

The plugin may be used either as a conventional plugin, or since it is only one class file, embedded within a custom theme.

#### As a plugin from the WordPress dashboard

1. Visit 'Plugins > Add New'
2. Search for 'Content Sectioner'
3. Activate Content Sectioner from your Plugins page

#### As a plugin from WordPress.org

1. Download Content Sectioner
2. Upload the `ContentSectioner.php` file to the `/wp-content/plugins` directory
3. Activate Content Sectioner from your Plugins page

#### Embedded in a custom theme

1. Download Content Sectioner
2. Either concatenate the contents of the `ContentSectioner.php` file into your `functions.php` file, or place it in your theme folder and `require` it when needed

== Frequently Asked Questions ==

= Is this utility unit tested? =

Yes.  The plugin is tested with PHPSpec.  
The original source for this plugin lives on github at [github.com/kirkbowers/content-sectioner](https://github.com/kirkbowers/content-sectioner) (the wordpress.org `svn` repo is used strictly for distribution).  

To run the specs, you first need to install PHPSpec using `composer`.  In the project root directory, run:

    util/install.sh
    
Then, once that's in place, you can run the tests by running:

    util/spec.sh


== Screenshots ==

1. This blue inset was created by the code sample given in "Description >> A concrete example"
2. These instructions are the result of the code sample given in "Description >> Providing instructions"

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release, nothing to upgrade!


