<?php
namespace Blueline;

echo $this->get( 'errorCode', '500' ).' | '.$this->get( 'errorTitle', 'Unknown Error' );