<?php
namespace Blueline;
use \Helpers\Text;

View::element( 'default.header', array(
	'title' => 'Methods | Blueline',
	'breadcrumb' => array(
		'<a href="/methods">Methods</a>'
	)
) );
?>
<section class="search">
	<header>
<?php
		View::element( 'sectionSearch', array(
			'action' => '/methods/search',
			'placeholder' => 'Search methods',
			'extra' => Text::pluralise( $count, 'method' )
		) );
?>
	</header>
</section>
<?php View::element( 'default.footer' ); ?>
