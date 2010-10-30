<?php
namespace Blueline;
use \Models\Tower;

Response::cacheType( 'static' );
View::set( 'towers', Tower::fullList() );
