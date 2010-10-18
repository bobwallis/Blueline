<?php
namespace Blueline;
use \Models\Association;

View::set( 'associations', Association::fullList() );
Response::cacheType( 'static' );
