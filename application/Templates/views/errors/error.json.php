<?php
namespace Blueline;

switch( Response::code() ) {
	case 403:
		echo json_encode( '403 | Forbidden' );
		break;
	case 404:
		echo json_encode( '404 | Not Found' );
		break;
	case 500:
	default:
		echo json_encode( '500 | Internal Server Error' );
		break;
}
?>
