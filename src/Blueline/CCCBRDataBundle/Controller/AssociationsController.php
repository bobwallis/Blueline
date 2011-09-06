<?php

namespace Blueline\CCCBRDataBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class AssociationsController extends Controller {

	public function welcomeAction() {
		$request = $this->getRequest();
		$format = $request->getRequestFormat();
		$isLayout = $format == 'html' && !$request->query->get( 'snippet' );
		
		if( $isLayout ) {
			$response = $this->render( 'BluelineCCCBRDataBundle:Associations:welcome.layout.'.$format.'.twig' );
		}
		else {
			$associations = $this->getDoctrine()->getEntityManager()->createQuery( 'SELECT partial a.{abbreviation,name} FROM BluelineCCCBRDataBundle:Associations a' )->getArrayResult();
			$response = $this->render( 'BluelineCCCBRDataBundle:Associations:welcome.'.$format.'.twig', compact( 'associations' ) );
		}
		
		// Caching headers
		$response->setPublic();
		$response->setMaxAge( 129600 );
		$response->setSharedMaxAge( 129600 );
		
		return $response;
	}
	
	public function viewAction( $abbreviation ) {
		$request = $this->getRequest();
		$format = $request->getRequestFormat();
		$isLayout = $format == 'html' && !$request->query->get( 'snippet' );
		
		$abbreviations = explode( '|', $abbreviation );
		
		$em = $this->getDoctrine()->getEntityManager();
		
		// Check we are at the canonical URL for the content
		if( ( !$isLayout && $format != 'html' ) || $isLayout ) {
			$associations = $em->createQuery( '
				SELECT partial a.{abbreviation,name} FROM BluelineCCCBRDataBundle:Associations a
				WHERE a.abbreviation IN (:abbreviation)' )
				->setParameter( 'abbreviation', $abbreviations )
				->setMaxResults( count( $abbreviations ) )
				->getArrayResult();
			if( empty( $associations ) || count( $associations ) < count( $abbreviations ) ) {
				throw $this->createNotFoundException( 'The association does not exist' );
			}
			$url = $this->generateUrl( 'Blueline_Associations_view', array( 'abbreviation' => implode( '|', array_map( function( $a ) { return $a['abbreviation']; }, $associations ) ), '_format' => $format ) );
			$pageTitle = \Blueline\Helpers\Text::toList( array_map( function( $a ) { return $a['name']; }, $associations ) );
			
			if( $request->getRequestUri() !== $url ) {
				return $this->redirect( $url, 301 );
			}
		}
		
		if( $isLayout ) {
			$response = $this->render( 'BluelineCCCBRDataBundle:Associations:view.layout.'.$format.'.twig', compact( 'abbreviations', 'pageTitle' ) );
		}
		elseif( count( $abbreviations ) > 1 ){
			$response = $this->render( 'BluelineCCCBRDataBundle:Associations:view.'.$format.'.twig', compact( 'abbreviations' ) );
		}
		else {
			// Create a HTML-safe id
			$id = preg_replace( '/\s*/', '', preg_replace( '/[^a-z0-9]/', '', strtolower( $abbreviation ) ) );
			
			// Get information about the association and its towers
			$association = $em->createQuery( '
				SELECT a, partial t.{doveid,place,dedication} FROM BluelineCCCBRDataBundle:Associations a
				JOIN a.affiliatedTowers t
				WHERE a.abbreviation = :abbreviation' )
			->setParameter( 'abbreviation', $abbreviation )
			->getArrayResult();
			$association = $association[0];
			
			// Get the bounding box for the tower map if needed
			if( $format == 'html' ) {
				$association['bbox'] = $em->createQuery( '
					SELECT MAX(t.latitude) as lat_max, MIN(t.latitude) as lat_min, MAX(t.longitude) as long_max, MIN(t.longitude) as long_min FROM BluelineCCCBRDataBundle:Associations a
					JOIN a.affiliatedTowers t
					WHERE a.abbreviation = :abbreviation' )
				->setParameter( 'abbreviation', $abbreviation )
				->getSingleResult();
			}
			
			$response = $this->render( 'BluelineCCCBRDataBundle:Associations:view.'.$format.'.twig', compact( 'association', 'id' ) );
		}
		
		// Caching headers
		$response->setPublic();
		$response->setMaxAge( 129600 );
		$response->setSharedMaxAge( 129600 );
		
		return $response;
	}
	
	public function searchAction( $searchVariables = array() ) {
		$request = $this->getRequest();
		$format = $request->getRequestFormat();
		$isLayout = $format == 'html' && !$request->query->get( 'snippet' );
		
		$associationsRepository = $this->getDoctrine()->getEntityManager()->getRepository( 'BluelineCCCBRDataBundle:Associations' );
		$searchVariables = empty( $searchVariables )? $associationsRepository->requestToSearchVariables( $request ) : $searchVariables;
		
		if( $isLayout ) {
			$response = $this->render( 'BluelineCCCBRDataBundle:Associations:search.layout.'.$format.'.twig', compact( 'searchVariables' ) );
		}
		else {
			$associations = $associationsRepository->search( $searchVariables );
			$count = (count( $associations ) > 0)? $associationsRepository->searchCount( $searchVariables ) : 0;
			$pageActive = max( 1, ceil( ($searchVariables['offset']+1)/$searchVariables['count'] ) );
			$pageCount =  max( 1, ceil( $count / $searchVariables['count'] ) );
			$response = $this->render( 'BluelineCCCBRDataBundle:Associations:search.'.$format.'.twig', compact( 'searchVariables', 'count', 'pageActive', 'pageCount', 'associations' ) );
		}
		
		// Caching headers
		$response->setPublic();
		$response->setMaxAge( 129600 );
		$response->setSharedMaxAge( 129600 );
		
		return $response;
	}

	public function sitemapAction() {
		$request = $this->getRequest();
		$format = $request->getRequestFormat();
		
		$associations = $this->getDoctrine()->getEntityManager()->createQuery( 'SELECT partial a.{abbreviation} FROM BluelineCCCBRDataBundle:Associations a' )->getArrayResult();

		$response = $this->render( 'BluelineCCCBRDataBundle:Associations:sitemap.'.$format.'.twig', compact( 'associations' ) );
		
		// Caching headers
		$response->setPublic();
		$response->setMaxAge( 129600 );
		$response->setSharedMaxAge( 129600 );
		
		return $response;
	}
}
