<?php
namespace Blueline;
use \Models\Association, \Models\Method, \Models\Tower;

Response::cacheType( 'static' );
View::set( 'associationCount', Association::searchCount() );
View::set( 'methodCount', Method::searchCount() );
View::set( 'towerCount', Tower::searchCount() );
