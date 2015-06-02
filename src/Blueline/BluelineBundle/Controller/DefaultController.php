<?php
namespace Blueline\BluelineBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function resourceAction($page, Request $request)
    {
        $format = $request->getRequestFormat();

        // Create basic response object
        $response = new Response();
        if ($this->container->getParameter('kernel.environment') == 'prod') {
            if ($format == 'manifest') {
                $response->setMaxAge(21600);
            } else {
                $response->setMaxAge(129600);
            }
            $response->setPublic();
        }
        $response->setLastModified(new \DateTime('@'.$this->container->getParameter('asset_update')));
        if ($response->isNotModified($request)) {
            return $response;
        }

        return $this->render('BluelineBundle:Resources:'.$page.'.'.$format.'.twig', array(), $response);
    }
}
