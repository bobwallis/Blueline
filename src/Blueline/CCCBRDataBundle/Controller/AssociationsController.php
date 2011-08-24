<?php

namespace Blueline\CCCBRDataBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class AssociationsController extends Controller {

	public function welcomeAction() {
		$request = $this->getRequest();
		$format = $request->getRequestFormat();
		$isSnippet = $format == 'html' && $request->query->get( 'snippet' );
		
		if( $isSnippet ) {
			$associations = $this->getDoctrine()->getEntityManager()->createQuery( 'SELECT a FROM BluelineCCCBRDataBundle:Associations a' )->getArrayResult();
			return $this->render( 'BluelineCCCBRDataBundle:Associations:welcome.'.$format.'.twig', compact( 'associations' ) );
		}
		else {
			return $this->render( 'BluelineCCCBRDataBundle:Associations:welcome.layout.'.$format.'.twig' );
		}
	}
	
	public function viewAction( $abbreviation ) {
		$request = $this->getRequest();
		$format = $request->getRequestFormat();
		$isSnippet = $format == 'html' && $request->query->get( 'snippet' );
		
		$abbreviations = explode( '|', $abbreviation );
		
		$em = $this->getDoctrine()->getEntityManager();
		
		// If we're building a layout, or a snippet for multiple associations, then check we are at the canonical URL for the content
		if( !$isSnippet || count( $abbreviations ) > 1 ) {
			$associations = $em->createQuery( '
				SELECT partial a.{abbreviation,name} FROM BluelineCCCBRDataBundle:Associations a
				WHERE a.abbreviation IN (:abbreviation)' )
				->setParameter( 'abbreviation', $abbreviations )
				->setMaxResults( count( $abbreviations ) )
				->getArrayResult();
			$url = implode( '|', array_map( function( $a ) { return $a['abbreviation']; }, $associations ) );
			$pageTitle = \Blueline\Helpers\Text::toList( array_map( function( $a ) { return $a['name']; }, $associations ) );
			if( empty( $url ) ) {
				die( 'not found' );
			}
			elseif( $abbreviation !== $url ) {
				die('non-canonical url, should be: "'.$url.'", not: "'.$abbreviation.'"');
			}
		}
		
		if( !$isSnippet ) {
			return $this->render( 'BluelineCCCBRDataBundle:Associations:view.layout.'.$format.'.twig', compact( 'abbreviations', 'pageTitle' ) );
		}
		elseif( count( $abbreviations ) > 1 ){
			return $this->render( 'BluelineCCCBRDataBundle:Associations:view.'.$format.'.twig', compact( 'abbreviations' ) );
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
			
			// Count towers
			$association['affiliatedTowers_count'] = count( $association['affiliatedTowers'] );
			
			// Get bounding box
			$association['bbox'] = $em->createQuery( '
				SELECT MAX(t.latitude) as lat_max, MIN(t.latitude) as lat_min, MAX(t.longitude) as long_max, MIN(t.longitude) as long_min FROM BluelineCCCBRDataBundle:Associations a
				JOIN a.affiliatedTowers t
				WHERE a.abbreviation = :abbreviation' )
			->setParameter( 'abbreviation', $abbreviation )
			->getSingleResult();
			
			return $this->render( 'BluelineCCCBRDataBundle:Associations:view.'.$format.'.twig', compact( 'association', 'id' ) );
		}
	}
}
