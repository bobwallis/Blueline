<?php
namespace Blueline;
use Flourish\fJSON;

echo fJSON::encode( $this->get( 'errorCode', '500' ).' | '.$this->get( 'errorTitle', 'Unknown Error' ) );
