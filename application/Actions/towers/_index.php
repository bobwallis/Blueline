<?php
namespace Blueline;
use \Models\Tower;

Response::cacheType( 'static' );
View::set( 'count', Tower::searchCount() );
