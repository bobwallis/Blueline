<?php

namespace Blueline\CCCBRDataBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class SearchController extends Controller {

	public function globalAction() {
	}
	
	public function associationsAction( $searchVariables = array() ) {
		$request = $this->getRequest();
		$format = $request->getRequestFormat();
		$isLayout = $format == 'html' && !$request->query->get( 'snippet' );
		
		$associationsRepository = $this->getDoctrine()->getEntityManager()->getRepository( 'BluelineCCCBRDataBundle:Associations' );
		$searchVariables = empty( $searchVariables )? $associationsRepository->requestToSearchVariables( $request ) : $searchVariables;
		
		if( $isLayout ) {
			return $this->render( 'BluelineCCCBRDataBundle:Associations:search.layout.'.$format.'.twig', compact( 'searchVariables' ) );
		}
		else {
			$associations = $associationsRepository->search( $searchVariables );
			$count = (count( $associations ) > 0)? $associationsRepository->searchCount( $searchVariables ) : 0;
			$pageActive = max( 1, ceil( ($searchVariables['offset']+1)/$searchVariables['count'] ) );
			$pageCount =  max( 1, ceil( $count / $searchVariables['count'] ) );
			return $this->render( 'BluelineCCCBRDataBundle:Associations:search.'.$format.'.twig', compact( 'searchVariables', 'count', 'pageActive', 'pageCount', 'associations' ) );
		}
	}
	
	public function methodsAction() {
	}
	
	public function towersAction() {
	}
}
