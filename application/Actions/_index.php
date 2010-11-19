<?php
namespace Blueline;
use \Models\DataAccess\Associations, \Models\DataAccess\Methods, \Models\DataAccess\Towers;

Response::cacheType( 'static' );
View::set( 'associationCount', Associations::findCount() );
View::set( 'methodCount', Methods::findCount() );
View::set( 'towerCount', Towers::findCount() );
