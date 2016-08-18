# content-sectioner
A WordPress developer plugin that makes it easy to break one long piece of content into sections.

It is currently in development... coming soon!

## The Motivation

There are a lot of websites that have big long pages that are broken into a lot of little sections.  Since WordPress traditionally has one piece of content per page, providing an easy interface for content authors to provide the copy for different sections is not supported out of the box, and you really don't want to require authors to have to add non-content markup manually (like `div` tags).

There are a couple possible solutions.  One is to create a custom post type and add post meta fields for each of the different sections.  Another is to provide textareas in the theme options that correspond to the different sections.  Neither solution is ideal, as they both require content authors to bounce around to different editors, and the meta or options editors don't have the usual WYSIWYG that WordPress users are used to.

This plugin provides a cleaner solution.  It allows content authors to write the pages as they normally would, in the single page content editor, then the theme will look for certain markers (like `h3` tags) to tell it where to insert additional markup.

## Usage

To use the Content Sectioner, simply instantiate a new `ContentSectioner` at the top of a theme file (such as `front-page.php`), then give it a sequence of replacement rules by calling the methods `replace_all`, `replace_first` and/or `replace_next`.

The argument to the replacement rule methods is an array of options in this format (with the defaults shown for any options not supplied):

    array(
      'open_insert' => '',
      'open_policy' => 'replace',
      'open_tag' => '',
      'close_insert' => '',
      'close_policy' => 'replace',
      'close_tag' => false,
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
    <h3 id="First-big-aside">First big aside</h3>
    
    <p>Aside ipsem...</p>
    
    <h2 id="Another-big-sub-head">Another big sub head</h2>

It would still work, yielding:

    ...
    <div class="inset"><h3 id="First-big-aside">First big aside</h3>
    
    <p>Aside ipsem...</p>
    
    </div><h2 id="Another-big-sub-head">Another big sub head</h2>

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

Suppose you want to place the div only around the `h3` itself.  You can match a close tag by simply including the forward slash (note the `close_tag` and `close_policy`):

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

## Testing

There are a few tests written for the plugin in PHPSpec.  They aren't exhaustive by any means, but they do provide some confidence that the most common use cases work.

To run the specs, you first need to install PHPSpec using `composer`.  In the project root directory, run:

    util/install.sh
    
Then, once that's in place, you can run the tests by running:

    util/spec.sh
