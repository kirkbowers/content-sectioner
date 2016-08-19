<?php

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
    
    function it_finds_a_tag_with_immediate_closing_angle_bracket_on_opens()
    {
      $before = <<<EOF
blah
<hr>
blech
EOF;

      $after = <<<EOF
blah
<br />
blech
EOF;

      $this->replace_all(array(
        'open_tag' => 'hr',
        'open_insert' => '<br />',
        'open_policy' => 'replace'
      ));

      $this->go($before)->shouldBe($after);    
    }
    
    function it_finds_a_tag_with_immediate_slash_angle_bracket_on_opens()
    {
      $before = <<<EOF
blah
<hr/>
blech
EOF;

      $after = <<<EOF
blah
<br />
blech
EOF;

      $this->replace_all(array(
        'open_tag' => 'hr',
        'open_insert' => '<br />',
        'open_policy' => 'replace'
      ));

      $this->go($before)->shouldBe($after);    
    }
    
    function it_finds_a_tag_with_space_slash_angle_bracket_on_opens()
    {
      $before = <<<EOF
blah
<hr />
blech
EOF;

      $after = <<<EOF
blah
<br />
blech
EOF;

      $this->replace_all(array(
        'open_tag' => 'hr',
        'open_insert' => '<br />',
        'open_policy' => 'replace'
      ));

      $this->go($before)->shouldBe($after);    
    }
    
    function it_finds_a_tag_with_space_and_attributes_on_opens()
    {
      $before = <<<EOF
blah
<hr id="stupid" class="dumb">
blech
EOF;

      $after = <<<EOF
blah
<br />
blech
EOF;

      $this->replace_all(array(
        'open_tag' => 'hr',
        'open_insert' => '<br />',
        'open_policy' => 'replace'
      ));

      $this->go($before)->shouldBe($after);    
    }
    
    function it_replaces_more_than_one_on_replace_all_opens()
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
        'open_insert' => '<br />',
        'open_policy' => 'replace'
      ));

      $this->go($before)->shouldBe($after);
    }
    
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
    
    function it_defaults_to_replacing_on_opens()
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
    
    function it_does_before_on_opens()
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
<br /><hr>
blech
foo
  <br /><hr>      
horf
EOF;
    
      $this->replace_all(array(
        'open_tag' => 'hr',
        'open_insert' => '<br />',
        'open_policy' => 'before'
      ));


      $this->go($before)->shouldBe($after);
    }
    
    function it_does_after_on_opens()
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
<hr><br />
blech
foo
  <hr><br />      
horf
EOF;
    
      $this->replace_all(array(
        'open_tag' => 'hr',
        'open_insert' => '<br />',
        'open_policy' => 'after'
      ));


      $this->go($before)->shouldBe($after);
    }
    
    function it_finds_a_closing_tag_on_opens()
    {
      $before = <<<EOF
blah
<h1>This is a heading</h1>
blech
EOF;

      $after = <<<EOF
blah
<h1>This is a heading</h1>
<br />
blech
EOF;

      $this->replace_all(array(
        'open_tag' => '/h1',
        'open_insert' => "\n<br />",
        'open_policy' => 'after'
      ));

      $this->go($before)->shouldBe($after);    
    }
    
    function it_works_with_regex_on_opens()
    {
      $before = <<<EOF
blah
<hr>
blech
foo
  <hr />      
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
        'open_regex' => '/<hr(>|\\/>|\s[^>]*>)/',
        'open_insert' => '<br />',
        'open_policy' => 'replace'
      ));

      $this->go($before)->shouldBe($after);
    }

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

    function it_does_remaining()
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

    function it_does_arounds_and_closes_at_end()
    {
      $before = <<<EOF
<p>
Stuff
</p>
<h2>First heading</h2>
<p>
More stuff.
</p>
<p>
Even more stuff.
</p>
<h2>Second heading</h2>
<p>
Yada yada.
</p>
EOF;

      $after = <<<EOF
<p>
Stuff
</p>
<div class="section" id="section-1">
<h2>First heading</h2>
<p>
More stuff.
</p>
<p>
Even more stuff.
</p>
</div><div class="section" id="section-2">
<h2>Second heading</h2>
<p>
Yada yada.
</p></div>
EOF;
    
      $this->replace_all(array(
        'open_tag' => 'h2',
        'open_insert' => '<div class="section" id="section-{{i}}">' . "\n",
        'open_policy' => 'before',
        'close_tag' => 'h2',
        'close_insert' => "</div>",
        'close_policy' => 'before'
      ));

      $this->go($before)->shouldBe($after);
    }


  }  
}
