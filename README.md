# content-sectioner
A WordPress developer plugin that makes it easy to break one long piece of content into sections.

It is currently in development... coming soon!

## The Motivation

There are a lot of websites that have big long pages that are broken into a lot of little sections.  Since WordPress traditionally has one piece of content per page, providing an easy interface for content authors to provide the copy for different sections is not supported out of the box, and you really don't want to require authors to have to add non-content markup manually (like `div` tags).

There are a couple possible solutions.  One is to create a custom post type and add post meta fields for each of the different sections.  Another is to provide textareas in the theme options that correspond to the different sections.  Neither solution is ideal, as they both require content authors to bounce around to different editors, and the meta or options editors don't have the usual WYSIWYG that WordPress users are used to.

This plugin provides a cleaner solution.  It takes advantage of WordPress's content filtering mechanism.  It allows content authors to write the pages as they normally would, in the single page content editor.  The theme then will look for certain markers (like `h3` tags) where it should insert additional markup.

As a theme developer, this plugin makes it easy for you to perform such filtering without having to roll your own regular expressions and string manipulation.  All you do is provide "replacement rules", arrays with certain keys to define how a replacement is to
be performed, and the rest is done for you.

## Usage

To use the Content Sectioner, simply instantiate a new `ContentSectioner` at the top of a theme file (such as `front-page.php`), then give it a sequence of replacement rules by calling the `replace_*` methods.

The argument to the replacement rule methods is an array of options in this format (with the defaults shown for any options not supplied):

    array(
      'open_insert' => '',
      'open_policy' => 'replace',
      'open_tag' => '',
      'close_insert' => '',
      'close_policy' => 'replace',
      'close_tag' => false,
      'close_strict' => false,
    );
    
## Examples

Let's say you have a page, and you want every `h3` tag to mark the start of an inset area with a different background color.  The inset should end when an `h2` is found.  So the HTML produced by a content author might look something like this:

    <h1>Site title</h1>
    
    <h2>Sub head</h2>
    
    <p>Lorem ipsem....</p>
    
    <h3>First big aside</h3>
    
    <p>Aside ipsem...</p>
    
    <h2>Another big sub head</h2>
      
What you want to have happen is you want to insert a `<div class="inset">` before each `h3`, then close that `div` upon the next `h2` (or end of the content).  The code would look like this in your theme:

    <?php
    
    $sectioner = new ContentSectioner();
    
    $sectioner->replace_all(
      array(
        'open_tag' => 'h3',
        'open_insert' => '<div class="inset">',
        'open_policy' => 'before',
        'close_tag' => 'h2',
        'close_insert' => '</div>',
        'close_policy' => 'before'
      )
    );
    
The result would look like this when the page is viewed by a site visitor:


    <h1>Site title</h1>
    
    <h2>Sub head</h2>
    
    <p>Lorem ipsem....</p>
    
    <div class="inset"><h3>First big aside</h3>
    
    <p>Aside ipsem...</p>
    
    </div><h2>Another big sub head</h2>
      
Note that you don't have to provide any of the angle brackets for the tags to match, just the name of the tag (such as `'h3'`).  It will match even if there are attributes on the tags.  So, if the HTML were produced by a markdown compiler instead of the WordPress editor, it might look like this:

    ...
    <h3 id="first-big-aside">First big aside</h3>
    
    <p>Aside ipsem...</p>
    
    <h2 id="another-big-sub-head">Another big sub head</h2>

It would still work, yielding:

    ...
    <div class="inset"><h3 id="first-big-aside">First big aside</h3>
    
    <p>Aside ipsem...</p>
    
    </div><h2 id="another-big-sub-head">Another big sub head</h2>

Suppose you want to give each inserted piece of markup a sequential attribute to target different sections with different styling in your CSS.  You can use the handlebar markup with the variable `i` to place sequential numbers into the `open_insert` and `close_insert` options:

    $sectioner->replace_all(
      array(
        'open_tag' => 'h3',
        'open_insert' => '<div class="inset" id="inset-{{ i }}">',
        'open_policy' => 'before',
        'close_tag' => 'h2',
        'close_insert' => '</div>',
        'close_policy' => 'before'
      )
    );

This would produce:

    ...
    <div class="inset" id="inset-1"><h3 id="First-big-aside">First big aside</h3>
    
    <p>Aside ipsem...</p>
    
    </div><h2 id="Another-big-sub-head">Another big sub head</h2>

As another use case, suppose you want to place the div only around the `h3` itself.  You can match a close tag by simply including the forward slash (note the `close_tag` and `close_policy`):

    $sectioner->replace_all(
      array(
        'open_tag' => 'h3',
        'open_insert' => '<div class="inset">',
        'open_policy' => 'before',
        'close_tag' => '/h3',
        'close_insert' => '</div>',
        'close_policy' => 'after'
      )
    );

This would produce:

    ...
    <div class="inset"><h3 id="First-big-aside">First big aside</h3></div>
    
    <p>Aside ipsem...</p>
    
    <h2 id="Another-big-sub-head">Another big sub head</h2>

## Providing Instructions

Since content sectioning happens at certain magic landmarks (like `h2` tags), it helps to instruct content authors which landmarks are the ones that do the magic.  For example, if you are inserting a blue background behind everthing from the first Heading 3 to the next Heading 2, you should let content authors that's how to indicate where they want the blue background to occur.

You can do this by calling the static method `ContentSectioner::provide_instructions`, ideally in `functions.php`, providing the slug of the page on which the sectioning rules
are applied, and the instructions:

    ContentSectioner::provide_instructions('about', "
      A blue background will be created behind everything from the first Heading 3 to
      the next Heading 2");

## Testing

There are several tests written for the plugin in PHPSpec.  They aren't quite exhaustive, but they do provide a good cross section of the most common use cases work.

To run the specs, you first need to install PHPSpec using `composer`.  In the project root directory, run:

    util/install.sh
    
Then, once that's in place, you can run the tests by running:

    util/spec.sh
    
## PHPDoc

The source is fairly thoroughly documented with `phpdoc`.  In order to produce the HTML docs, simply run:

    util/doc.sh
    
Then open `docs/index.html` in your browser.


