<?php
namespace Blueline\ServicesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Blueline\MethodsBundle\Helpers\PlaceNotation;

class SirilController extends Controller
{
    public function indexAction(Request $request)
    {
        // Create basic response object
        $response = new Response();
        if ($this->container->getParameter('kernel.environment') == 'prod') {
            $response->setMaxAge(129600);
            $response->setPublic();
        }
        $response->setLastModified(new \DateTime('@'.$this->container->getParameter('asset_update')));
        if ($response->isNotModified($request)) {
            return $response;
        }

        return $this->render('BluelineServicesBundle::siril.html.twig', array(), $response);
    }
}
