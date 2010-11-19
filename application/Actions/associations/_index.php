<?php
namespace Blueline;
use \Models\DataAccess\Associations;

View::set( 'associations', Associations::find( array( 'order' => 'name ASC' ) ) );
Response::cacheType( 'static' );
