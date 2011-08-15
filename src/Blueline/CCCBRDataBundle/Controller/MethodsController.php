<?php

namespace Blueline\CCCBRDataBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class MethodsController extends Controller {

	public function welcomeAction() {
		$request = $this->getRequest();
		$format = $request->getRequestFormat();
		$isSnippet = $format == 'html' && $request->query->get( 'snippet' );
		
		if( $isSnippet ) {
			;
		}
		else {
			return $this->render( 'BluelineCCCBRDataBundle:Methods:welcome.layout.'.$format.'.twig' );
		}
	}
	
	public function viewAction( $title ) {
		$request = $this->getRequest();
		$format = $request->getRequestFormat();
		$isSnippet = $format == 'html' && $request->query->get( 'snippet' );
		
		$titles = explode( '|', str_replace( '_', ' ', $title ) );
		
		// If we're building a layout, or a snippet for multiple associations, then check we are at the canonical URL for the content
		if( !$isSnippet || count( $titles ) > 1 ) {
			$methods = $this->getDoctrine()->getEntityManager()->createQuery( '
				SELECT m.title FROM BluelineCCCBRDataBundle:Methods m
				WHERE m.title IN (:title)' )
				->setParameter( 'title', $titles )
				->getArrayResult();
			$url = implode( '|', array_map( function( $m ) { return str_replace( ' ', '_', $m['title'] ); }, $methods ) );
			$pageTitle = \Blueline\Helpers\Text::toList( array_map( function( $m ) { return $m['title']; }, $methods ) );
			if( empty( $url ) ) {
				die( 'not found' );
			}
			elseif( $title !== $url ) {
				die('non-canonical url, should be: "'.$url.'", not: "'.$title.'"');
			}
		}
		
		if( !$isSnippet ) {
			return $this->render( 'BluelineCCCBRDataBundle:Methods:view.layout.'.$format.'.twig', compact( 'titles', 'pageTitle' ) );
		}
		elseif( count( $titles ) > 1 ){
			return $this->render( 'BluelineCCCBRDataBundle:Methods:view.'.$format.'.twig', compact( 'titles' ) );
		}
		else {
			$em = $this->getDoctrine()->getEntityManager();
			
			$title = str_replace( '_', ' ', $title );
			$id = preg_replace( '/\s*/', '', preg_replace( '/[^a-z0-9]/', '', strtolower( $title ) ) );
			
			$query = $em->createQuery(
				'SELECT m, t FROM BluelineCCCBRDataBundle:Methods m 
				LEFT JOIN m.firstTowerbellPealTower t 
				WHERE m.title LIKE :title' )
				->setParameter( 'title', $title );
			
			$method = $query->getSingleResult();
			
			return $this->render( 'BluelineCCCBRDataBundle:Methods:view.'.$format.'.twig', compact( 'method', 'id' ) );
		}
	}
}
