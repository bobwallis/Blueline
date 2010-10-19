<?php
namespace Blueline;
use \Models\Association;

View::set( 'associations', Association::index() );
Response::cacheType( 'static' );
