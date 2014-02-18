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
        $response = new Response();
        // Set caching
        if ( $this->container->getParameter( 'kernel.environment') == 'prod' ) {
            $response->setMaxAge( 129600 );
            $response->setPublic();
        }
        $response->setETag( $this->container->getParameter('asset_update') );
        if ( $response->isNotModified( $request ) ) { return $response; }

        return $this->render( 'BluelinePagesBundle::'.$page.'.'.$format.'.twig', array(), $response );
    }
}
