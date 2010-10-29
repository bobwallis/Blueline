<?php
namespace Blueline;
use \Models\Method;

Response::cacheType( 'static' );
View::set( 'count', Method::searchCount() );
