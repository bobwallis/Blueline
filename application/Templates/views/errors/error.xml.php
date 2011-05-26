<?php
namespace Blueline;
use Flourish\fXML;

echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
?>
<error code="<?=$this->get( 'errorCode', '500' )?>" title="<?=$this->get( 'errorTitle', 'Unknown Error' )?>"><?=$this->get( 'errorMessage', '' )?> />
