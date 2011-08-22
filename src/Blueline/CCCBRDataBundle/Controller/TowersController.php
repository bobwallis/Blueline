<?php

namespace Blueline\CCCBRDataBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class TowersController extends Controller {

	public function welcomeAction() {
		$request = $this->getRequest();
		$format = $request->getRequestFormat();
		$isSnippet = $format == 'html' && $request->query->get( 'snippet' );
		
		if( $isSnippet ) {
			;
		}
		else {
			return $this->render( 'BluelineCCCBRDataBundle:Towers:welcome.layout.'.$format.'.twig' );
		}
	}
	
	public function viewAction( $doveid ) {
		$request = $this->getRequest();
		$format = $request->getRequestFormat();
		$isSnippet = $format == 'html' && $request->query->get( 'snippet' );
		
		$doveids = explode( '|', $doveid );
		
		// If we're building a layout, or a snippet for multiple associations, then check we are at the canonical URL for the content
		if( !$isSnippet || count( $doveids ) > 1 ) {
			$towers = $this->getDoctrine()->getEntityManager()->createQuery( '
				SELECT partial t.{doveid,place,dedication} FROM BluelineCCCBRDataBundle:Towers t
				LEFT JOIN t.oldpk t2
				WHERE t.doveid IN (:doveid) OR t2.oldpk IN (:doveid)' )
				->setParameter( 'doveid', $doveids )
				->setMaxResults( count( $doveids ) )
				->getArrayResult();
			$url = implode( '|', array_map( function( $t ) { return $t['doveid']; }, $towers ) );
			$pageTitle = \Blueline\Helpers\Text::toList( array_map( function( $t ) { return $t['place'].(($t['dedication']!='Unknown')?' ('.$t['dedication'].')':''); }, $towers ) );
			if( empty( $url ) ) {
				die( 'not found' );
			}
			elseif( $doveid !== $url ) {
				die('non-canonical url, should be: "'.$url.'", not: "'.$doveid.'"');
			}
		}
		
		if( !$isSnippet ) {
			return $this->render( 'BluelineCCCBRDataBundle:Towers:view.layout.'.$format.'.twig', compact( 'doveids', 'pageTitle' ) );
		}
		elseif( count( $doveids ) > 1 ){
			return $this->render( 'BluelineCCCBRDataBundle:Towers:view.'.$format.'.twig', compact( 'doveids' ) );
		}
		else {
			$em = $this->getDoctrine()->getEntityManager();
			
			// Create a HTML-safe id
			$id = preg_replace( '/\s*/', '', preg_replace( '/[^a-z0-9]/', '', strtolower( $doveid ) ) );
			
			// Get information about the tower, its affiliations, and first pealed methods
			$tower = $em->createQuery( '
				SELECT t, partial a.{abbreviation,name}, partial m.{title,firstTowerbellPealDate} FROM BluelineCCCBRDataBundle:Towers t
				LEFT JOIN t.affiliations a
				LEFT JOIN t.firstPealedMethods m
				WHERE t.doveid = :doveid' )
			->setParameter( 'doveid', $doveid )
			->getArrayResult();
			$tower = $tower[0];
			
			$tower['countyCountry'] = \Blueline\Helpers\Text::toList( array( $tower['county'], $tower['country'] ), ', ', ', ' );
			
			$days = array( '', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );
			if( !empty( $tower['practiceNight'] ) ) { $tower['practiceNight_day'] = $days[$tower['practiceNight']]; }
			
			return $this->render( 'BluelineCCCBRDataBundle:Towers:view.'.$format.'.twig', compact( 'tower', 'id' ) );
		}
	}
}
