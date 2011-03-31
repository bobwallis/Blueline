<?php
namespace Blueline;

echo json_encode( $this->get( 'errorCode', '500' ).' | '.$this->get( 'errorTitle', 'Unknown Error' ) );