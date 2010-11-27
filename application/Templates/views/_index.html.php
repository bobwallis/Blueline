<?php
namespace Blueline;
use \Helpers\Text;

View::element( 'default.header' );
?>
<section class="search">
	<header>
<?php
View::element( 'sectionSearch', array(
	'action' => '/search',
	'placeholder' => 'Search',
	'extra' => Text::toList( array( Text::pluralise( $associationCount, 'association' ), Text::pluralise( $methodCount, 'method' ), Text::pluralise( $towerCount, 'tower' ) ) )
) );
?>
	</header>
</section>
<?php View::element( 'default.footer' ); ?>
