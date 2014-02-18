<?php
namespace Blueline\BluelineBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function resourceAction($page)
    {
        $request = $this->getRequest();
        $format = $request->getRequestFormat();

        $response = $this->render( 'BluelineBundle:Resources:'.$page.'.'.$format.'.twig' );

        // Set caching headers differently for manifest
        if ( $this->container->getParameter( 'kernel.environment') == 'prod' ) {
            if ($format == 'manifest') {
                $response->setMaxAge( 21600 );
            } else {
                $response->setMaxAge( 129600 );
            }
            $response->setPublic();
        }

        return $response;
    }
}
