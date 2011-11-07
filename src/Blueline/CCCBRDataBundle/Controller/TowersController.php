<?php
namespace Blueline\CCCBRDataBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class TowersController extends Controller {

	public function welcomeAction() {
		$request = $this->getRequest();
		$format = $request->getRequestFormat();
		$isSnippet = $format == 'html' && $request->query->get( 'snippet' );
		
		$response = $this->render( 'BluelineCCCBRDataBundle:Towers:welcome.'.$format.'.twig', compact( 'isSnippet' ) );
		
		// Caching headers
		$response->setPublic();
		$response->setMaxAge( 129600 );
		$response->setSharedMaxAge( 129600 );
		
		return $response;
	}
	
	public function viewAction( $doveid ) {
		$request = $this->getRequest();
		$format = $request->getRequestFormat();
		$isSnippet = $format == 'html' && $request->query->get( 'snippet' );
		
		$doveids = explode( '|', $doveid );
		
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
		$url = $this->generateUrl( 'Blueline_Towers_view', array( 'snippet' => ($isSnippet?1:null), 'snippet' => ($isSnippet?1:null), 'doveid' => implode( '|', array_map( function( $t ) { return $t['doveid']; }, $towers ) ), '_format' => $format ) );
	
		if( $request->getRequestUri() !== $url ) {
			return $this->redirect( $url, 301 );
		}

		$pageTitle = \Blueline\Helpers\Text::toList( array_map( function( $t ) { return $t['place'].(($t['dedication']!='Unknown')?' ('.$t['dedication'].')':''); }, $towers ) );
		$towers = array();
		
		foreach( $doveids  as $doveid ) {
			// Get information about the tower, its affiliations, and first pealed methods
			$towers[] = $em->createQuery( '
				SELECT t, partial a.{abbreviation,name}, partial m.{title,firstTowerbellPealDate} FROM BluelineCCCBRDataBundle:Towers t
				LEFT JOIN t.affiliations a
				LEFT JOIN t.firstPealedMethods m
				WHERE t.doveid = :doveid' )
			->setParameter( 'doveid', $doveid )
			->getSingleResult();
			
			// Get nearby tower data
			/*
			if( $format == 'html' ) {
				$distance = '( 6371 * acos( cos( radians(:near_lat) ) * cos( radians( t.latitude ) ) * cos( radians( t.longitude ) - radians(:near_long) ) + sin( radians(:near_lat) ) * sin( radians( t.latitude ) ) ) )';
				$tower['nearbyTowers'] = array_map( function( $t ) { return array_merge( $t[0], array( 'distance' => $t['distance'] ) ); }, $em->createQuery( '
						SELECT partial t.{doveid,place,dedication,latitude,longitude}, '.$distance.' as distance FROM BluelineCCCBRDataBundle:Towers t
						WHERE t.latitude IS NOT NULL
						HAVING '.$distance.' < 20
						ORDER BY distance ASC' )
					->setFirstResult( 1 )
					->setMaxResults( 7 )
					->setParameter( 'near_lat', $tower['latitude'] )
					->setParameter( 'near_long', $tower['longitude'] )
					->getArrayResult() );
			}
			*/
		}
		
		// Create response
		$response = $this->render( 'BluelineCCCBRDataBundle:Towers:view.'.$format.'.twig', compact( 'pageTitle', 'towers', 'isSnippet' ) );

		// Caching headers
		$response->setPublic();
		$response->setMaxAge( 129600 );
		$response->setSharedMaxAge( 129600 );
		
		return $response;
	}
	
	public function searchAction( $searchVariables = array() ) {
		$request = $this->getRequest();
		$format = $request->getRequestFormat();
		$isSnippet = $format == 'html' && $request->query->get( 'snippet' );
		
		$towersRepository = $this->getDoctrine()->getEntityManager()->getRepository( 'BluelineCCCBRDataBundle:Towers' );
		$searchVariables = empty( $searchVariables )? $towersRepository->requestToSearchVariables( $request ) : $searchVariables;
		
		$towers = $towersRepository->search( $searchVariables );
		$count = (count( $towers ) > 0)? $towersRepository->searchCount( $searchVariables ) : 0;
		$pageActive = max( 1, ceil( ($searchVariables['offset']+1)/$searchVariables['count'] ) );
		$pageCount =  max( 1, ceil( $count / $searchVariables['count'] ) );
		$response = $this->render( 'BluelineCCCBRDataBundle:Towers:search.'.$format.'.twig', compact( 'searchVariables', 'count', 'pageActive', 'pageCount', 'towers', 'isSnippet' ) );
		
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
