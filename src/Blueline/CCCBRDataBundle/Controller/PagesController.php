<?php
namespace Blueline\CCCBRDataBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class PagesController extends Controller {	
	public function pageAction( $page ) {
		$request = $this->getRequest();
		$format = $request->getRequestFormat();
		$chromeless = 0;
		if( $format == 'html' ) {
			$chromeless = intval( $request->query->get( 'chromeless' ) );
			$chromeless = ($chromeless == 0 && strpos( $_SERVER['HTTP_USER_AGENT'], 'Blueline' ) !== false)? 1 : (($chromeless > 2)? 2 : $chromeless);
		}
		
		$response = $this->render( 'BluelineCCCBRDataBundle:Pages:'.$page.'.'.$format.'.twig', compact( 'chromeless' ) );
		
		// Set headers differently for manifest
		if( $format == 'manifest' ) {
			$response->headers->set( 'Content-Type', 'text/cache-manifest' );
			$response->setMaxAge( 21600 );
		}
		else {
			$response->setMaxAge( 129600 );
		}
		$response->setPublic();
		$response->setSharedMaxAge( 129600 );
		
		return $response;
	}
}
