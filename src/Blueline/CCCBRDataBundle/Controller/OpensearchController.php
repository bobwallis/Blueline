<?php
namespace Blueline\CCCBRDataBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class OpensearchController extends Controller {	

	public function descriptionAction( $type ) {
		// Render response
		$response = $this->render( 'BluelineCCCBRDataBundle:Opensearch:description_'.$type.'.xml.twig' );
		
		// Set correct content type
		$response->headers->set( 'Content-Type', 'application/opensearchdescription+xml' );
		
		// Caching headers
		$response->setPublic();
		$response->setMaxAge( 604800 );
		$response->setSharedMaxAge( 604800 );
		
		return $response;
	}

}
