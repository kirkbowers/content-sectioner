<?php

// This is an incredibly difficult thing to test exhaustively because there are so
// many combinations of things that can be done.  I try to hit the most important
// features here.  Here are the priorities:
//
// - Matching on HTML open tags, both to open and close a match
//   - The simple case of just the tag and angle brackets, eg. <h2>
//   - Uppercase, eg. <H2>
//   - A tag with attributes, eg. <h2 class="foo">
//   - A tag with the XML-style /> to close itself, eg. <hr/>
//   - A tag with the XML-style and preceding whitespace, eg. <hr />
// - Matching on HTML closing tags by supplying a slash in the pattern, 
//   eg. "/h2" to match </h2>
// - Replaces first
// - Replaces next
// - Replaces all
// - Replaces remaining
// - Works with regex
// - Respects the order replacement rules are added
//   - All before first
//   - First before all
// - Starts next search where last left off
//   - Open match with before policy starts after match to avoid rematching
//   - Open match with replace policy starts after replacement
//   - Open match with after policy starts after insertion
//   - Close match with before policy starts after insertion to allow matching on next
//     open
//   - Close match with replace policy starts after replacement
//   - Close match with after policy starts after insertion
// - Replaces mustache i with the count in replace_all
//   - Without whitespace
//   - With whitespace
// - Matches end of content for close by default
// - Does not match end of content when close_strict is true

// This is a total stub to pretend we are in the WordPress ecosystem
namespace {
  function add_filter() {
    // no-op
  }
}


namespace spec {

  use PhpSpec\ObjectBehavior;
  use Prophecy\Argument;

  class ContentSectionerSpec extends ObjectBehavior
  {
    function it_is_initializable()
    {
      $this->shouldHaveType('ContentSectioner');
    }
    
    //=================================================================================
    // Matching on HTML open tags, both to open and close a match
    
    function it_finds_simple_case_of_just_the_tag_and_angle_brackets()
    {
      $before = <<<EOF
Some stuff
<h1>A header</h1>
<p>Bacon ipsem</p>
<h2>A subheader</h2>
EOF;

      $after = <<<EOF
Some stuff
<div><h1>A header</h1>
<p>Bacon ipsem</p>
</div><h2>A subheader</h2>
EOF;

      $this->replace_all(array(
        'open_tag' => 'h1',
        'open_insert' => '<div>',
        'open_policy' => 'before',
        'close_tag' => 'h2',
        'close_insert' => '</div>',
        'close_policy' => 'before'
      ));

      $this->go($before)->shouldBe($after);    
    }
    
    function it_finds_simple_case_in_uppercase()
    {
      $before = <<<EOF
Some stuff
<H1>A header</H1>
<p>Bacon ipsem</p>
<H2>A subheader</H2>
EOF;

      $after = <<<EOF
Some stuff
<div><H1>A header</H1>
<p>Bacon ipsem</p>
</div><H2>A subheader</H2>
EOF;

      // NOTE: The rule is still given in lowercase!
      $this->replace_all(array(
        'open_tag' => 'h1',
        'open_insert' => '<div>',
        'open_policy' => 'before',
        'close_tag' => 'h2',
        'close_insert' => '</div>',
        'close_policy' => 'before'
      ));

      $this->go($before)->shouldBe($after);    
    }
    
    function it_finds_tags_with_attributes()
    {
      $before = <<<EOF
Some stuff
<h1 class="special">A header</h1>
<p>Bacon ipsem</p>
<h2 id="the-awesome-subhead" >A subheader</h2>
EOF;

      $after = <<<EOF
Some stuff
<div><h1 class="special">A header</h1>
<p>Bacon ipsem</p>
</div><h2 id="the-awesome-subhead" >A subheader</h2>
EOF;

      $this->replace_all(array(
        'open_tag' => 'h1',
        'open_insert' => '<div>',
        'open_policy' => 'before',
        'close_tag' => 'h2',
        'close_insert' => '</div>',
        'close_policy' => 'before'
      ));

      $this->go($before)->shouldBe($after);    
    }
    
    function it_finds_tags_with_immediate_slash_angle_bracket()
    {
      $before = <<<EOF
blah
<hr/>
blech
<hr/>
EOF;

      $after = <<<EOF
blah
<div>
blech
</div>
EOF;

      $this->replace_all(array(
        'open_tag' => 'hr',
        'open_insert' => '<div>',
        'open_policy' => 'replace',
        'close_tag' => 'hr',
        'close_insert' => '</div>',
        'close_policy' => 'replace'
      ));

      $this->go($before)->shouldBe($after);    
    }
    
    function it_finds_tags_with_space_slash_angle_bracket()
    {
      $before = <<<EOF
blah
<hr />
blech
<hr />
EOF;

      $after = <<<EOF
blah
<div>
blech
</div>
EOF;
 
      $this->replace_all(array(
        'open_tag' => 'hr',
        'open_insert' => '<div>',
        'open_policy' => 'replace',
        'close_tag' => 'hr',
        'close_insert' => '</div>',
        'close_policy' => 'replace'
      ));

      $this->go($before)->shouldBe($after);    
    }
    
    
    //=================================================================================
    // Matching on HTML closing tags by supplying a slash in the pattern
    
    function it_finds_closing_tags()
    {
      $before = <<<EOF
Some stuff
<h1>A header</h1>
<p>Bacon ipsem</p>
<h2>A subheader</h2>
<p>More Stuff</p>
EOF;

      $after = <<<EOF
Some stuff
<h1>A header</h1><div>
<p>Bacon ipsem</p>
<h2>A subheader</h2></div>
<p>More Stuff</p>
EOF;

      $this->replace_all(array(
        'open_tag' => '/h1',
        'open_insert' => "<div>",
        'open_policy' => 'after',
        'close_tag' => '/h2',
        'close_insert' => "</div>",
        'close_policy' => 'after'
      ));

      $this->go($before)->shouldBe($after);    
    }

    
    //=================================================================================
    // Replace first
    
    function it_replaces_only_the_first()
    {
      $before = <<<EOF
blah
<hr>
blech
foo
<hr>      
horf
<hr>
yucky
EOF;

      $after = <<<EOF
blah
<hr class="first">
blech
foo
<hr>      
horf
<hr>
yucky
EOF;
    
      $this->replace_first(array(
        'open_tag' => 'hr',
        'open_insert' => '<hr class="first">',
        'open_policy' => 'replace'
      ));

      $this->go($before)->shouldBe($after);
    }

    //=================================================================================
    // Replace next
    
    function it_replaces_first_then_next()
    {
      $before = <<<EOF
blah
<hr>
blech
foo
<hr>      
horf
<hr>
yucky
EOF;

      $after = <<<EOF
blah
<hr class="first">
blech
foo
<hr class="next">      
horf
<hr>
yucky
EOF;
    
      $this->replace_first(array(
        'open_tag' => 'hr',
        'open_insert' => '<hr class="first">',
        'open_policy' => 'replace'
      ));
      
      // Note, this should skip the hr produced by the replace_first

      $this->replace_next(array(
        'open_tag' => 'hr',
        'open_insert' => '<hr class="next">',
        'open_policy' => 'replace'
      ));

      $this->go($before)->shouldBe($after);
    }

    //=================================================================================
    // Replace all
    
    function it_replaces_all()
    {
      $before = <<<EOF
blah
<hr>
blech
foo
<hr>      
horf
<hr>
yucky
EOF;

      $after = <<<EOF
blah
<hr class="all">
blech
foo
<hr class="all">      
horf
<hr class="all">
yucky
EOF;
    
      $this->replace_all(array(
        'open_tag' => 'hr',
        'open_insert' => '<hr class="all">',
        'open_policy' => 'replace'
      ));
      
      $this->go($before)->shouldBe($after);
    }

    //=================================================================================
    // Replace remaining
    
     function it_replaces_remaining()
    {
      $before = <<<EOF
blah
<hr>
blech
foo
<hr>      
horf
<hr>
yucky
EOF;

      $after = <<<EOF
blah
<hr class="first">
blech
foo
<hr class="remaining">      
horf
<hr class="remaining">
yucky
EOF;
    
      $this->replace_first(array(
        'open_tag' => 'hr',
        'open_insert' => '<hr class="first">',
        'open_policy' => 'replace'
      ));
      
      $this->replace_remaining(array(
        'open_tag' => 'hr',
        'open_insert' => '<hr class="remaining">',
        'open_policy' => 'replace'
      ));

      $this->go($before)->shouldBe($after);
    }

   
    
    //=================================================================================
    // Works with a regex
    
    function it_works_with_a_regex()
    {
      $before = <<<EOF
Some stuff
<h1>A header</h1>
<p>Bacon ipsem</p>
<h2>A subheader</h2>
EOF;

      $after = <<<EOF
<div>Some stuff
</div><h1>A header</h1>
<p>Bacon ipsem</p>
<h2>A subheader</h2>
EOF;

      $this->replace_first(array(
        'open_regex' => '/^/',
        'open_insert' => '<div>',
        'open_policy' => 'before',
        'close_tag' => 'h1',
        'close_insert' => '</div>',
        'close_policy' => 'before'
      ));

      $this->go($before)->shouldBe($after);
    }
    
    
    //=================================================================================
    // Respects the order replacement rules are added
    
    function it_runs_in_the_order_specified_with_all_first()
    {
      $before = <<<EOF
blah
<hr>
blech
foo
<hr>      
horf
<hr>
yucky
EOF;

      $after = <<<EOF
blah
<hr class="first">
blech
foo
<hr class="next">      
horf
<hr class="all">
yucky
EOF;
    
      $this->replace_all(array(
        'open_tag' => 'hr',
        'open_insert' => '<hr class="all">',
        'open_policy' => 'replace'
      ));

      $this->replace_first(array(
        'open_tag' => 'hr',
        'open_insert' => '<hr class="first">',
        'open_policy' => 'replace'
      ));
      
      $this->replace_next(array(
        'open_tag' => 'hr',
        'open_insert' => '<hr class="next">',
        'open_policy' => 'replace'
      ));

      $this->go($before)->shouldBe($after);
    }


    function it_runs_in_the_order_specified_with_all_last()
    {
      $before = <<<EOF
blah
<hr>
blech
foo
<hr>      
horf
<hr>
yucky
EOF;

      $after = <<<EOF
blah
<hr class="all">
blech
foo
<hr class="all">      
horf
<hr class="all">
yucky
EOF;
    
      $this->replace_first(array(
        'open_tag' => 'hr',
        'open_insert' => '<hr class="first">',
        'open_policy' => 'replace'
      ));
      
      // Note, this should skip the hr produced by the replace_first

      $this->replace_next(array(
        'open_tag' => 'hr',
        'open_insert' => '<hr class="next">',
        'open_policy' => 'replace'
      ));

      $this->replace_all(array(
        'open_tag' => 'hr',
        'open_insert' => '<hr class="all">',
        'open_policy' => 'replace'
      ));

      $this->go($before)->shouldBe($after);
    }


    //=================================================================================
    // Starts next search where last left off
    
    //   - Open match with before policy starts after match to avoid rematching
    function it_starts_after_match_on_open_before()
    {
      $before = <<<EOF
Some stuff
<h1 class="classy">A header</h1>
<p>Bacon ipsem</p>
<h2>A subheader</h2>
EOF;

      $after = <<<EOF
Some stuff
<div><h1 class="classy">*A header</h1>
<p>Bacon ipsem</p>
<h2>A subheader</h2>
EOF;

      $this->replace_first(array(
        'open_tag' => 'h1',
        'open_insert' => '<div>',
        'open_policy' => 'before'
      ));

      $this->replace_next(array(
        'open_regex' => '/./',
        'open_insert' => '*',
        'open_policy' => 'before'
      ));

      $this->go($before)->shouldBe($after);
    }

    //   - Open match with replace policy starts after replacement
    function it_starts_after_replacement_on_open_replace()
    {
      $before = <<<EOF
Some stuff
<h1 class="classy">A header</h1>
<p>Bacon ipsem</p>
<h2>A subheader</h2>
EOF;

      $after = <<<EOF
Some stuff
<div>*A header</h1>
<p>Bacon ipsem</p>
<h2>A subheader</h2>
EOF;

      $this->replace_first(array(
        'open_tag' => 'h1',
        'open_insert' => '<div>',
        'open_policy' => 'replace'
      ));

      $this->replace_next(array(
        'open_regex' => '/./',
        'open_insert' => '*',
        'open_policy' => 'before'
      ));

      $this->go($before)->shouldBe($after);
    }

    //   - Open match with after policy starts after insertion
    function it_starts_after_insertion_on_open_after()
    {
      $before = <<<EOF
Some stuff
<h1 class="classy">A header</h1>
<p>Bacon ipsem</p>
<h2>A subheader</h2>
EOF;

      $after = <<<EOF
Some stuff
<h1 class="classy"><div>*A header</h1>
<p>Bacon ipsem</p>
<h2>A subheader</h2>
EOF;

      $this->replace_first(array(
        'open_tag' => 'h1',
        'open_insert' => '<div>',
        'open_policy' => 'after'
      ));

      $this->replace_next(array(
        'open_regex' => '/./',
        'open_insert' => '*',
        'open_policy' => 'before'
      ));

      $this->go($before)->shouldBe($after);
    }

    //   - Close match with before policy starts after insertion to allow matching on next
    //     open
    function it_starts_after_insertion_on_close_before()
    {
      $before = <<<EOF
Some stuff
<h1 class="classy">A header</h1>
<p>Bacon ipsem</p>
<h2>A subheader</h2>
EOF;

      $after = <<<EOF
Some stuff
<div><h1 class="classy">A header</h1>
<p>Bacon ipsem</p>
</div>*<h2>A subheader</h2>
EOF;

      $this->replace_first(array(
        'open_tag' => 'h1',
        'open_insert' => '<div>',
        'open_policy' => 'before',
        'close_tag' => 'h2',
        'close_insert' => '</div>',
        'close_policy' => 'before'
      ));

      $this->replace_next(array(
        'open_regex' => '/./',
        'open_insert' => '*',
        'open_policy' => 'before'
      ));

      $this->go($before)->shouldBe($after);
    }

    //   - Close match with replace policy starts after replacement
    function it_starts_after_replacement_on_close_replace()
    {
      $before = <<<EOF
Some stuff
<h1 class="classy">A header</h1>
<p>Bacon ipsem</p>
<h2>A subheader</h2>
EOF;

      $after = <<<EOF
Some stuff
<div><h1 class="classy">A header</h1>
<p>Bacon ipsem</p>
</div>*A subheader</h2>
EOF;

      $this->replace_first(array(
        'open_tag' => 'h1',
        'open_insert' => '<div>',
        'open_policy' => 'before',
        'close_tag' => 'h2',
        'close_insert' => '</div>',
        'close_policy' => 'replace'
      ));

      $this->replace_next(array(
        'open_regex' => '/./',
        'open_insert' => '*',
        'open_policy' => 'before'
      ));

      $this->go($before)->shouldBe($after);
    }

    //   - Close match with after policy starts after insertion
    function it_starts_after_insertion_on_close_after()
    {
      $before = <<<EOF
Some stuff
<h1 class="classy">A header</h1>
<p>Bacon ipsem</p>
<h2>A subheader</h2>
EOF;

      // This makes garbage HTML, but it proves the test...
      $after = <<<EOF
Some stuff
<div><h1 class="classy">A header</h1>
<p>Bacon ipsem</p>
<h2></div>*A subheader</h2>
EOF;

      $this->replace_first(array(
        'open_tag' => 'h1',
        'open_insert' => '<div>',
        'open_policy' => 'before',
        'close_tag' => 'h2',
        'close_insert' => '</div>',
        'close_policy' => 'after'
      ));

      $this->replace_next(array(
        'open_regex' => '/./',
        'open_insert' => '*',
        'open_policy' => 'before'
      ));

      $this->go($before)->shouldBe($after);
    }


    //=================================================================================
    // Replaces mustache i with the count in replace_all    
    
    function it_replaces_mustache_i_on_replace_all_opens()
    {
      $before = <<<EOF
blah
<hr>
blech
foo
  <hr>      
horf
EOF;

      $after = <<<EOF
blah
<img src="image-1.png">
blech
foo
  <img src="image-2.png">      
horf
EOF;
    
      $this->replace_all(array(
        'open_tag' => 'hr',
        'open_insert' => '<img src="image-{{i}}.png">',
        'open_policy' => 'replace'
      ));

      $this->go($before)->shouldBe($after);
    }
    
    function it_replaces_mustache_i_with_spaces_on_replace_all_opens()
    {
      $before = <<<EOF
blah
<hr>
blech
foo
  <hr>      
horf
EOF;

      $after = <<<EOF
blah
<img src="image-1.png">
blech
foo
  <img src="image-2.png">      
horf
EOF;
    
      $this->replace_all(array(
        'open_tag' => 'hr',
        'open_insert' => '<img src="image-{{ i }}.png">',
        'open_policy' => 'replace'
      ));

      $this->go($before)->shouldBe($after);
    }
    
    //=================================================================================
    // Defaults to replace 
    
    function it_defaults_to_replacing()
    {
      $before = <<<EOF
blah
<hr>
blech
foo
  <hr>      
horf
EOF;

    
      $after = <<<EOF
blah
<br />
blech
foo
  <br />      
horf
EOF;
    
      $this->replace_all(array(
        'open_tag' => 'hr',
        'open_insert' => '<br />'
      ));

      $this->go($before)->shouldBe($after);
    }
    

    //=================================================================================
    // Matches end of content for close by default
    
    function it_matches_end_of_content_for_close_by_default()
    {
      $before = <<<EOF
Some stuff
<h2>A header</h2>
<p>Bacon ipsem</p>
<h2>A subheader</h2>
<p>Turducken</p>
EOF;

      $after = <<<EOF
Some stuff
<div><h2>A header</h2>
<p>Bacon ipsem</p>
</div><div><h2>A subheader</h2>
<p>Turducken</p></div>
EOF;

      $this->replace_all(array(
        'open_tag' => 'h2',
        'open_insert' => '<div>',
        'open_policy' => 'before',
        'close_tag' => 'h2',
        'close_insert' => '</div>',
        'close_policy' => 'before'
      ));

      $this->go($before)->shouldBe($after);    
    }

    //=================================================================================
    // Does not match end of content when close_strict is true
    
    function it_does_not_match_end_of_content_for_close_strict_is_true()
    {
      $before = <<<EOF
Some stuff
<h2>A header</h2>
<p>Bacon ipsem</p>
<h2>A subheader</h2>
<p>Turducken</p>
EOF;

      $after = <<<EOF
Some stuff
<div><h2>A header</h2>
<p>Bacon ipsem</p>
</div><h2>A subheader</h2>
<p>Turducken</p>
EOF;

      $this->replace_all(array(
        'open_tag' => 'h2',
        'open_insert' => '<div>',
        'open_policy' => 'before',
        'close_tag' => 'h2',
        'close_insert' => '</div>',
        'close_policy' => 'before',
        'close_strict' => true
      ));

      $this->go($before)->shouldBe($after);    
    }
    
  }  
}
