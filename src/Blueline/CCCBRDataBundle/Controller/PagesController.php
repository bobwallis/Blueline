<?php
namespace Blueline\CCCBRDataBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class PagesController extends Controller {	
	public function pageAction( $page ) {
		$request = $this->getRequest();
		$format = $request->getRequestFormat();
		$isSnippet = $format == 'html' && $request->query->get( 'snippet' );
		
		$response = $this->render( 'BluelineCCCBRDataBundle:Pages:'.$page.'.'.$format.'.twig', compact( 'isSnippet' ) );
		
		// Caching headers
		$response->setPublic();
		$response->setMaxAge( 129600 );
		$response->setSharedMaxAge( 129600 );
		
		return $response;
	}
}
