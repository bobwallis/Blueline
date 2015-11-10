<?php
namespace Blueline\BluelineBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
* @Cache(maxage="129600", public=true, lastModified="asset_update")
*/
class DefaultController extends Controller
{
    public function resourceAction($page, Request $request)
    {
        return $this->render('BluelineBundle:Resources:'.$page.'.'.$request->getRequestFormat().'.twig');
    }

    public function pageAction($page, Request $request)
    {
        return $this->render('BluelineBundle:Pages:'.$page.'.'.$request->getRequestFormat().'.twig');
    }

    /**
    * @Cache(maxage="21600", public=true, lastModified="asset_update")
    */
    public function manifestAction(Request $request)
    {
        return $this->render('BluelineBundle:Resources:site.manifest.twig');
    }
}
