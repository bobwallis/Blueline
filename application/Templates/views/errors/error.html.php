<?php
namespace Blueline;

switch( Response::code() ) {
	case 403:
		$errorTitle = 'Forbidden';
		break;
	case 404:
		$errorTitle = 'Not Found';
		$errorSub = 'The requested page either no longer exists, or never has.';
		break;
	case 500:
	default:
		$errorTitle = 'Internal Server Error';
		break;
}
$title_for_layout = $errorTitle.' | Blueline';
$headerSearch = array( 
	'action' => '/search',
	'placeholder' => 'Search'
);
?>
<header>
	<h1><?php echo $errorTitle; ?></h1>
</header>
<?php echo isset( $errorSub )? '<p>'.$errorSub.'</p>' : ''; ?>
<p>Try a search, or visit the homepage to find what you're looking for.</p>
<?php echo ( DEVELOPMENT && isset( $errorMessage ) )? '<p>'.str_replace( "\n", "<br />\n", $errorMessage ).'</p>' : ''; ?>
