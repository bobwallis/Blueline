<?php
namespace Blueline;
use \Models\DataAccess\Towers;

Response::cacheType( 'static' );
View::set( 'count', Towers::findCount() );
