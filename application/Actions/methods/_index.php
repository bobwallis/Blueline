<?php
namespace Blueline;
use \Models\DataAccess\Methods;

Response::cacheType( 'static' );
View::set( 'count', Methods::findCount() );
