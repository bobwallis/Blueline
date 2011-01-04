<?php
namespace Blueline;
use \Helpers\Text;

View::element( 'default.header', array(
	'title' => 'Towers | Blueline',
	'breadcrumb' => array(
		'<a href="/towers">Towers</a>'
	),
	'bigSearch' => array(
		'action' => '/towers/search',
		'placeholder' => 'Search towers'
	)
) );

View::element( 'default.footer' );
?>
