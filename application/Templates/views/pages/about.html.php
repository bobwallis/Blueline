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
</div>
<?php View::element( 'footer' ); ?>
