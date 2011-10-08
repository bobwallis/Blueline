<?php

namespace Blueline\CCCBRDataBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class MethodsController extends Controller {

	public function welcomeAction() {
		$request = $this->getRequest();
		$format = $request->getRequestFormat();
		$isLayout = $format == 'html' && !$request->query->get( 'snippet' );
		
		if( $isLayout ) {
			$response = $this->render( 'BluelineCCCBRDataBundle:Methods:welcome.layout.'.$format.'.twig' );
		}
		else {
			$response = $this->render( 'BluelineCCCBRDataBundle:Methods:welcome.'.$format.'.twig' );
		}
		
		// Caching headers
		$response->setPublic();
		$response->setMaxAge( 129600 );
		$response->setSharedMaxAge( 129600 );
		
		return $response;
	}
	
	public function viewAction( $title ) {
		$request = $this->getRequest();
		$format = $request->getRequestFormat();
		$isLayout = $format == 'html' && !$request->query->get( 'snippet' );
		
		$titles = explode( '|', str_replace( '_', ' ', $title ) );
		
		$em = $this->getDoctrine()->getEntityManager();
		
		// Check we are at the canonical URL for the content
		if( ( !$isLayout && $format != 'html' ) || $isLayout ) {
			$methods = $em->createQuery( '
				SELECT m.title FROM BluelineCCCBRDataBundle:Methods m
				WHERE m.title IN (:title)' )
				->setParameter( 'title', $titles )
				->setMaxResults( count( $titles ) )
				->getArrayResult();
			if( empty( $methods ) || count( $methods ) < count( $methods ) ) {
				throw $this->createNotFoundException( 'The method does not exist' );
			}
			$url = $this->generateUrl( 'Blueline_Methods_view', array( 'title' => implode( '|', array_map( function( $m ) { return str_replace( ' ', '_', $m['title'] ); }, $methods ) ), '_format' => $format ) );
			$pageTitle = \Blueline\Helpers\Text::toList( array_map( function( $m ) { return $m['title']; }, $methods ) );
		
			if( $request->getRequestUri() !== $url ) {
				return $this->redirect( $url, 301 );
			}
		}
		
		if( $isLayout ) {
			$response = $this->render( 'BluelineCCCBRDataBundle:Methods:view.layout.'.$format.'.twig', compact( 'titles', 'pageTitle' ) );
		}
		elseif( count( $titles ) > 1 ){
			$response = $this->render( 'BluelineCCCBRDataBundle:Methods:view.'.$format.'.twig', compact( 'titles' ) );
		}
		else {
			// We don't have _ in the database
			$title = str_replace( '_', ' ', $title );
			// Create a HTML-safe id
			$id = preg_replace( '/\s*/', '', preg_replace( '/[^a-z0-9]/', '', strtolower( $title ) ) );
			
			$query = $em->createQuery(
				'SELECT m, partial e.{id,calls,ruleOffs}, t FROM BluelineCCCBRDataBundle:Methods m 
				LEFT JOIN m.extras e
				LEFT JOIN m.firstTowerbellPealTower t 
				WHERE m.title LIKE :title' )
				->setParameter( 'title', $title );
			
			$method = $query->getSingleResult();

			$response = $this->render( 'BluelineCCCBRDataBundle:Methods:view.'.$format.'.twig', compact( 'method', 'id' ) );
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
		
		$methodsRepository = $this->getDoctrine()->getEntityManager()->getRepository( 'BluelineCCCBRDataBundle:Methods' );
		$searchVariables = empty( $searchVariables )? $methodsRepository->requestToSearchVariables( $request ) : $searchVariables;
		
		if( $isLayout ) {
			$response = $this->render( 'BluelineCCCBRDataBundle:Methods:search.layout.'.$format.'.twig', compact( 'searchVariables' ) );
		}
		else {
			$methods = $methodsRepository->search( $searchVariables );
			$count = (count( $methods ) > 0)? $methodsRepository->searchCount( $searchVariables ) : 0;
			$pageActive = max( 1, ceil( ($searchVariables['offset']+1)/$searchVariables['count'] ) );
			$pageCount =  max( 1, ceil( $count / $searchVariables['count'] ) );
			$response = $this->render( 'BluelineCCCBRDataBundle:Methods:search.'.$format.'.twig', compact( 'searchVariables', 'count', 'pageActive', 'pageCount', 'methods' ) );
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
		
		$methods = $this->getDoctrine()->getEntityManager()->createQuery( 'SELECT partial m.{title} FROM BluelineCCCBRDataBundle:Methods m' )->getArrayResult();

		$response = $this->render( 'BluelineCCCBRDataBundle:Methods:sitemap.'.$format.'.twig', compact( 'methods' ) );
		
		// Caching headers
		$response->setPublic();
		$response->setMaxAge( 129600 );
		$response->setSharedMaxAge( 129600 );
		
		return $response;
	}
}