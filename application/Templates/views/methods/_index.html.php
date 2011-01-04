<?php
namespace Blueline;
use \Helpers\Text;

View::element( 'default.header', array(
	'title' => 'Methods | Blueline',
	'breadcrumb' => array(
		'<a href="/methods">Methods</a>'
	),
	'bigSearch' => array(
		'action' => '/methods/search',
		'placeholder' => 'Search methods'
	)
) );

View::element( 'default.footer' );
?>
