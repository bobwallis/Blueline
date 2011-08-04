<?php

namespace Blueline\CCCBRDataBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class PagesController extends Controller {

	public function welcomeAction() {
		$format = $this->getRequest()->getRequestFormat();
		
		return $this->render( 'BluelineCCCBRDataBundle:Pages:welcome.'.$format.'.twig' );
	}
	
	public function pageAction( $page ) {
		$format = $this->getRequest()->getRequestFormat();
		
		return $this->render( 'BluelineCCCBRDataBundle:Pages:'.$page.'.'.$format.'.twig' );
	}
}
