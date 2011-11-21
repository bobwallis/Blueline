<?php
namespace Blueline\CCCBRDataBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class TowersController extends Controller {

	public function welcomeAction() {
		$request = $this->getRequest();
		$format = $request->getRequestFormat();
		$chromeless = 0;
		if( $format == 'html' ) {
			$chromeless = intval( $request->query->get( 'chromeless' ) );
			$chromeless = ($chromeless == 0 && strpos( $_SERVER['HTTP_USER_AGENT'], 'Blueline' ) !== false)? 2 : (($chromeless > 2)? 2 : $chromeless);
		}
		
		$response = $this->render( 'BluelineCCCBRDataBundle:Towers:welcome.'.$format.'.twig', compact( 'chromeless' ) );
		
		// Caching headers
		$response->setPublic();
		$response->setMaxAge( 129600 );
		$response->setSharedMaxAge( 129600 );
		
		return $response;
	}
	
	public function viewAction( $doveid ) {
		$request = $this->getRequest();
		$format = $request->getRequestFormat();
		$chromeless = 0;
		if( $format == 'html' ) {
			$chromeless = intval( $request->query->get( 'chromeless' ) );
			$chromeless = ($chromeless == 0 && strpos( $_SERVER['HTTP_USER_AGENT'], 'Blueline' ) !== false)? 2 : (($chromeless > 2)? 2 : $chromeless);
		}
		
		$doveids = explode( '|', $doveid );
		
		$towersRepository = $this->getDoctrine()->getEntityManager()->getRepository( 'BluelineCCCBRDataBundle:Towers' );
		$em = $this->getDoctrine()->getEntityManager();
		
		// Check we are at the canonical URL for the content
		$towers = $em->createQuery( '
			SELECT partial t.{doveid,place,dedication} FROM BluelineCCCBRDataBundle:Towers t
			LEFT JOIN t.oldpk t2
			WHERE t.doveid IN (:doveid) OR t2.oldpk IN (:doveid)' )
			->setParameter( 'doveid', $doveids )
			->setMaxResults( count( $doveids ) )
			->getArrayResult();
		if( empty( $towers ) || count( $towers ) < count( $doveids ) ) {
			throw $this->createNotFoundException( 'The tower does not exist' );
		}
		$url = $this->generateUrl( 'Blueline_Towers_view', array( 'chromeless' => ($chromeless?:null), 'doveid' => implode( '|', array_map( function( $t ) { return $t['doveid']; }, $towers ) ), '_format' => $format ) );
	
		if( $request->getRequestUri() !== $url ) {
			return $this->redirect( $url, 301 );
		}

		$pageTitle = \Blueline\Helpers\Text::toList( array_map( function( $t ) { return $t['place'].(($t['dedication']!='Unknown')?' ('.$t['dedication'].')':''); }, $towers ) );
		$towers = array();
		$nearbyTowers = array();
		
		foreach( $doveids  as $doveid ) {
			// Get information about the tower, its affiliations, and first pealed methods
			$tower = $em->createQuery( '
				SELECT t, partial a.{abbreviation,name}, partial m.{title,firstTowerbellPealDate} FROM BluelineCCCBRDataBundle:Towers t
				LEFT JOIN t.affiliations a
				LEFT JOIN t.firstPealedMethods m
				WHERE t.doveid = :doveid' )
			->setParameter( 'doveid', $doveid )
			->getSingleResult();
			
			$nearbyTowers[] = array_slice( $towersRepository->nearbyTowers( $tower->getLatitude(), $tower->getLongitude(), 7 ), 1 );
			$towers[] = $tower;
		}
		
		$bbox = array();
		if( count( $towers ) > 1 && $format == 'html' ) {
			$bbox['lat_min'] = min( array_map( function( $t ) { return $t->getLatitude(); }, $towers ) );
			$bbox['long_min'] = min( array_map( function( $t ) { return $t->getLongitude(); }, $towers ) );
			$bbox['lat_max'] = max( array_map( function( $t ) { return $t->getLatitude(); }, $towers ) );
			$bbox['long_max'] = max( array_map( function( $t ) { return $t->getLongitude(); }, $towers ) );
		}
		
		// Create response
		$response = $this->render( 'BluelineCCCBRDataBundle:Towers:view.'.$format.'.twig', compact( 'pageTitle', 'towers', 'nearbyTowers', 'bbox', 'chromeless' ) );

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
			$chromeless = ($chromeless == 0 && strpos( $_SERVER['HTTP_USER_AGENT'], 'Blueline' ) !== false)? 2 : (($chromeless > 2)? 2 : $chromeless);
		}
		
		$towersRepository = $this->getDoctrine()->getEntityManager()->getRepository( 'BluelineCCCBRDataBundle:Towers' );
		$searchVariables = empty( $searchVariables )? $towersRepository->requestToSearchVariables( $request ) : $searchVariables;
		
		$towers = $towersRepository->search( $searchVariables );
		$count = (count( $towers ) > 0)? $towersRepository->searchCount( $searchVariables ) : 0;
		$pageActive = max( 1, ceil( ($searchVariables['offset']+1)/$searchVariables['count'] ) );
		$pageCount =  max( 1, ceil( $count / $searchVariables['count'] ) );
		$response = $this->render( 'BluelineCCCBRDataBundle:Towers:search.'.$format.'.twig', compact( 'searchVariables', 'count', 'pageActive', 'pageCount', 'towers', 'chromeless' ) );
		
		// Caching headers
		$response->setPublic();
		$response->setMaxAge( 129600 );
		$response->setSharedMaxAge( 129600 );
		
		return $response;
	}

	public function sitemapAction() {
		$request = $this->getRequest();
		$format = $request->getRequestFormat();
		
		$towers = $this->getDoctrine()->getEntityManager()->createQuery( 'SELECT partial t.{doveid} FROM BluelineCCCBRDataBundle:Towers t' )->getArrayResult();

		$response = $this->render( 'BluelineCCCBRDataBundle:Towers:sitemap.'.$format.'.twig', compact( 'towers' ) );
		
		// Caching headers
		$response->setPublic();
		$response->setMaxAge( 129600 );
		$response->setSharedMaxAge( 129600 );
		
		return $response;
	}
}
