<?php
namespace Blueline\PagesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PagesController extends Controller
{
    public function pageAction($page)
    {
        $request = $this->getRequest();
        $format = $request->getRequestFormat();
        $response = $this->render( 'BluelinePagesBundle::'.$page.'.'.$format.'.twig' );

        // Set caching
        if ( $this->container->getParameter( 'kernel.environment') == 'prod' ) {
            $response->setMaxAge( 129600 );
            $response->setPublic();
        }

        return $response;
    }
}
