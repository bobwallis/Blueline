<?php
namespace Blueline;
use Pan\View, Helpers\Text;

View::cache( true );

View::element( 'header', array(
	'title' => 'Towers | Blueline',
	'breadcrumb' => array(
		'<a href="/towers">Towers</a>'
	),
	'bigSearch' => array(
		'action' => '/towers/search',
		'placeholder' => 'Search towers'
	)
) );
View::element( 'footer' );