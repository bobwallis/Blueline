<?php

namespace Blueline\CCCBRDataBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class PagesController extends Controller {

	public function welcomeAction() {
		$format = $this->getRequest()->getRequestFormat();
		
		return $this->render( 'BluelineCCCBRDataBundle:Pages:welcome.layout.'.$format.'.twig' );
	}
	
	public function pageAction( $page ) {
		$request = $this->getRequest();
		$format = $request->getRequestFormat();
		$isSnippet = $format == 'html' && $request->query->get( 'snippet' );
		
		return $this->render( 'BluelineCCCBRDataBundle:Pages:'.$page.($isSnippet?'':'.layout').'.'.$format.'.twig' );
	}
}
