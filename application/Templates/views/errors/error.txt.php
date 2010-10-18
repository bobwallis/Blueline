<?php
namespace Blueline;

switch( Response::code() ) {
	case 403:
		echo '403 | Forbidden';
		break;
	case 404:
		echo '404 | Not Found';
		break;
	case 500:
	default:
		echo '500 | Internal Server Error';
		break;
}
?>
