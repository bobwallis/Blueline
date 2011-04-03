<?php
namespace Blueline;
use Pan\View;

View::cache( true );

View::element( 'header', array(
	'title' => 'Towers | Blueline',
	'breadcrumb' => array(
		'<a href="/towers">Towers</a>'
	),
	'headerSearch' => array(
		'action' => '/towers/search',
		'placeholder' => 'Search towers'
	)
) );
?>
<script>
//<![CDATA[
require( ['ui/TowerMap'], function( TowerMap ) {
	TowerMap.maximise();
	TowerMap.set( {} );
} );
//]]>
</script>

<?php View::element( 'footer' );
