<?php
namespace Blueline;
use \Models\Method;

Response::cacheType( 'static' );
View::set( 'methods', Method::fullList() );
