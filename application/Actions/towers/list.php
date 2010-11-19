<?php
namespace Blueline;
use \Models\DataAccess\Towers;

Response::cacheType( 'static' );
View::set( 'towers', Towers::find( array(
	'fields' => array( 'doveId', 'place', 'dedication' )
) ) );
