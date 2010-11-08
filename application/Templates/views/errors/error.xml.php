<?php
namespace Blueline;

switch( Response::code() ) {
	case 403:
		$errorTitle = 'Forbidden';
		break;
	case 404:
		$errorTitle = 'Not Found';
		$errorText = 'The requested page either no longer exists, or never has.';
		break;
	case 500:
	default:
		$errorTitle = 'Internal Server Error';
		break;
}
echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
?>
<error code="<?php echo Response::code(); ?>" title="<?php echo $errorTitle; ?>"><?php echo isset( $errorText )? $errorText : ''; ?></error>
