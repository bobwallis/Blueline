<?php
namespace Blueline;
use Pan\View;

View::cache( 43200 );
?>
CACHE MANIFEST
# <?=$this->get( 'timestamp' )."\n"?>
NETWORK:
*
CACHE:
/
<?php foreach( $this->get( 'resources' ) as $resource ) : ?>
<?=$resource."\n"?>
<?php endforeach; ?>