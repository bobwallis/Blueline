<?php

namespace Blueline\CCCBRDataBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class MethodsController extends Controller {

	public function welcomeAction() {
		$request = $this->getRequest();
		$format = $request->getRequestFormat();
		$chromeless = 0;
		if( $format == 'html' ) {
			$chromeless = intval( $request->query->get( 'chromeless' ) );
		}
		
		$response = $this->render( 'BluelineCCCBRDataBundle:Methods:welcome.'.$format.'.twig', compact( 'chromeless' ) );
		
		// Caching headers
		$response->setPublic();
		$response->setMaxAge( 129600 );
		$response->setSharedMaxAge( 129600 );
		
		return $response;
	}
	
	public function viewAction( $title ) {
		$request = $this->getRequest();
		$format = $request->getRequestFormat();
		$chromeless = 0;
		if( $format == 'html' ) {
			$chromeless = intval( $request->query->get( 'chromeless' ) );
		}
		
		$titles = explode( '|', str_replace( '_', ' ', $title ) );
		
		$em = $this->getDoctrine()->getEntityManager();
		
		// Check we are at the canonical URL for the content
		$methods = $em->createQuery( '
			SELECT m.title FROM BluelineCCCBRDataBundle:Methods m
			WHERE m.title IN (:title)' )
			->setParameter( 'title', $titles )
			->setMaxResults( count( $titles ) )
			->getArrayResult();
		
		if( empty( $methods ) || count( $methods ) < count( $titles ) ) {
			throw $this->createNotFoundException( 'The method does not exist' );
		}
		
		$titlesFound = array_map( function( $m ) { return str_replace( ' ', '_', $m['title'] ); }, $methods );
		$url = $this->generateUrl( 'Blueline_Methods_view', array( 'chromeless' => ($chromeless?:null), 'title' => implode( '|', $titlesFound ), '_format' => $format ) );
		
		if( $request->getRequestUri() !== $url && $request->getRequestUri() !== urldecode( $url ) ) {
			return $this->redirect( $url, 301 );
		}
		
		$pageTitle = \Blueline\Helpers\Text::toList( array_map( function( $m ) { return $m['title']; }, $methods ) );
		$methods = array();
		
		foreach( $titles as $title ) {
			// We don't have _ in the database
			$title = str_replace( '_', ' ', $title );
			
			$query = $em->createQuery(
				'SELECT m, partial e.{id,calls,ruleOffs}, t FROM BluelineCCCBRDataBundle:Methods m 
				LEFT JOIN m.extras e
				LEFT JOIN m.duplicates d
				LEFT JOIN m.firstTowerbellPealTower t 
				WHERE m.title LIKE :title' )
				->setParameter( 'title', $title );
			
			$methods[] = $query->getSingleResult();
		}
		
		// Create response
		$response = $this->render( 'BluelineCCCBRDataBundle:Methods:view.'.$format.'.twig', compact( 'pageTitle', 'methods', 'chromeless' ) );
		
		// Caching headers
		$response->setPublic();
		$response->setMaxAge( 129600 );
		$response->setSharedMaxAge( 129600 );
		
		return $response;
	}
	
	public function searchAction( $searchVariables = array() ) {
		$request = $this->getRequest();
		$format = $request->getRequestFormat();
		$chromeless = 0;
		if( $format == 'html' ) {
			$chromeless = intval( $request->query->get( 'chromeless' ) );
		}
		
		$methodsRepository = $this->getDoctrine()->getEntityManager()->getRepository( 'BluelineCCCBRDataBundle:Methods' );
		$searchVariables = empty( $searchVariables )? $methodsRepository->requestToSearchVariables( $request ) : $searchVariables;
		
		$methods = $methodsRepository->search( $searchVariables );
		$count = (count( $methods ) > 0)? $methodsRepository->searchCount( $searchVariables ) : 0;
		$pageActive = max( 1, ceil( ($searchVariables['offset']+1)/$searchVariables['count'] ) );
		$pageCount =  max( 1, ceil( $count / $searchVariables['count'] ) );
		$response = $this->render( 'BluelineCCCBRDataBundle:Methods:search.'.$format.'.twig', compact( 'searchVariables', 'count', 'pageActive', 'pageCount', 'methods', 'chromeless' ) );
		
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
