<?php

namespace Blueline\CCCBRDataBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class PagesController extends Controller {

	public function welcomeAction() {
		return $this->render( 'BluelineCCCBRDataBundle:Pages:welcome.layout.html.twig' );
	}
	
	public function pageAction( $page ) {
		$request = $this->getRequest();
		$format = $request->getRequestFormat();
		$isLayout = $format == 'html' && !$request->query->get( 'snippet' );
		
		return $this->render( 'BluelineCCCBRDataBundle:Pages:'.$page.($isLayout?'.layout':'').'.'.$format.'.twig' );
	}
}
