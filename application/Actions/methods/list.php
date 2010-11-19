<?php
namespace Blueline;
use \Models\DataAccess\Methods;

Response::cacheType( 'static' );
View::set( 'methods', Methods::find( array(
	'fields' => array( 'title' )
) ) );
