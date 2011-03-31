<?php
namespace Blueline;
use Pan\View, Helpers\Text;

View::cache( true );

View::element( 'header', array(
	'title' => 'Methods | Blueline',
	'breadcrumb' => array(
		'<a href="/methods">Methods</a>'
	),
	'bigSearch' => array(
		'action' => '/methods/search',
		'placeholder' => 'Search methods'
	)
) );
View::element( 'footer' );