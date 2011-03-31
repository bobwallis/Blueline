<?php
namespace Blueline;
use Pan\View;

View::cache( true );

View::element( 'header', array(
	'title' => 'About | Blueline',
	'headerSearch' => array(
		'action' => '/search',
		'placeholder' => 'Search'
	)
) );
?>
<header>
	<h1>About</h1>
</header>
<div class="content wallOfText">
	<p>This is a project begun over the summer of 2010 by <a href="http://www.rsw.me.uk" class="external">Robert Wallis</a>, and aims to be a modern, user-friendly interface to the data provided on <a href="http://www.methods.org.uk" class="external">www.methods.org.uk</a>, and <a href="http://dove.cccbr.org.uk" class="external">dove.cccbr.org.uk</a>.</p>
	<p>The source code is <a href="http://www.github.com/bobwallis/Blueline" class="external">made available</a> for interested people. Feedback, bug reports and advice are encouraged.</p>
	<h2>FAQ</h2>
	<ul class="noliststyle">
		<li>
			<h3>Which browsers are supported?</h3>
			<p>I aim to eventually support any browser (although not with every feature). At the moment any modern browser should work properly on the majority of the site.</p>
			<p>The current major exception to this is the blue line drawing, which only works in browsers which support <a href="http://en.wikipedia.org/wiki/Scalable_Vector_Graphics" class="external">SVG</a> (Chrome, Opera, Firefox...), particularly not in Internet Explorer 8 or below. (I haven't been able to test in Internet Explorer 9, but that should work properly). Support for Internet Explorer using <a href="http://en.wikipedia.org/wiki/Vector_Markup_Language" class="external">VML</a> is planned.</p>
		</li>
	</ul>
</div>
<?php View::element( 'footer' ); ?>
