<?php
namespace Blueline\PagesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class PagesController extends Controller
{
    public function pageAction($page)
    {
        $request = $this->getRequest();
        $format = $request->getRequestFormat();

        // Create basic response object
        $response = new Response();
        if ( $this->container->getParameter( 'kernel.environment') == 'prod' ) {
            $response->setMaxAge( 129600 );
            $response->setPublic();
        }
        $response->setLastModified( new \DateTime( '@'.$this->container->getParameter('asset_update') ) );
        if ( $response->isNotModified( $request ) ) { return $response; }

        return $this->render( 'BluelinePagesBundle::'.$page.'.'.$format.'.twig', array(), $response );
    }
}
