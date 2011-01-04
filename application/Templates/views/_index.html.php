<?php
namespace Blueline;
use \Helpers\Text;

View::element( 'default.header', array(
	'bigSearch' => array(
		'action' => '/search',
		'placeholder' => 'Search'
	)
) );
?>
<?php View::element( 'default.footer' ); ?>
