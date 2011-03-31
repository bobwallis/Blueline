<?php
namespace Blueline;
use \Pan\View;

View::cache( true );

View::element( 'header', array(
	'manifest' => true,
	'bigSearch' => array(
		'action' => '/search',
		'placeholder' => 'Search'
	)
) );
View::element( 'footer' );