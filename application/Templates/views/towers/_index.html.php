<?php
namespace Blueline;
use \Helpers\Text;

View::element( 'default.header', array(
	'title' => 'Towers | Blueline',
	'breadcrumb' => array(
		'<a href="/towers">Towers</a>'
	)
) );
?>
<section class="search">
	<header>
<?php View::element( 'sectionSearch', array(
	'action' => '/towers/search',
	'placeholder' => 'Search towers',
	'extra' => Text::pluralise( $count, 'tower' )
) ); ?>
	</header>
</section>
<?php View::element( 'default.footer' ); ?>
