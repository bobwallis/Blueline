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

	public function suggestionsAction( $type ) {
		$request = $this->getRequest();
	
		switch( $type ) {
			case 'associations':
				$repository = $this->getDoctrine()->getEntityManager()->getRepository( 'BluelineCCCBRDataBundle:Associations' );
				break;
			case 'methods':
				$repository = $this->getDoctrine()->getEntityManager()->getRepository( 'BluelineCCCBRDataBundle:Methods' );
				break;
			case 'towers':
				$repository = $this->getDoctrine()->getEntityManager()->getRepository( 'BluelineCCCBRDataBundle:Towers' );
				break;
		}
		$searchVariables = empty( $searchVariables )? $repository->requestToSearchVariables( $request ) : $searchVariables;
		$searchVariables['count'] = 8;
		$searchVariables['offset'] = 0;
		
		$results = $repository->search( $searchVariables );
		
		$completions = array();
		$descriptions = array();
		$URLs = array();
		
		switch( $type ) {
			case 'associations':
				foreach( $results as $a ) {
					$completions[] = $a['name'];
					$descriptions[] = "";
					$URLs[] = $this->generateUrl( 'Blueline_Associations_view', array( 'abbreviation' => $a['abbreviation'] ), true );
				}
				break;
			case 'methods':
				foreach( $results as $m ) {
					$completions[] = $m['title'];
					$descriptions[] = "";
					$URLs[] = $this->generateUrl( 'Blueline_Methods_view', array( 'title' => str_replace( ' ', '_', $m['title'] ) ), true );
				}
				break;
			case 'towers':
				foreach( $results as $t ) {
					$completions[] = $t['place'].' ('.$t['dedication'].')';
					$descriptions[] = "";
					$URLs[] = $this->generateUrl( 'Blueline_Towers_view', array( 'doveid' => $t['doveid'] ), true );
				}
				break;
		}
	
		// Render response
		$response = $this->render( 'BluelineCCCBRDataBundle:Opensearch:suggestions.json.twig', compact( 'searchVariables', 'completions', 'descriptions', 'URLs' ) );
		
		// Set correct content type
		$response->headers->set( 'Content-Type', 'application/x-suggestions+json' );
		
		// Caching headers
		$response->setPublic();
		$response->setMaxAge( 604800 );
		$response->setSharedMaxAge( 604800 );
		
		return $response;
	}

}
