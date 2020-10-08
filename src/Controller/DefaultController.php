<?php
namespace Blueline\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
* @Cache(maxage="129600", public=true, lastModified="asset_update")
*/
class DefaultController extends AbstractController
{
    public function resource($page, Request $request)
    {
        return $this->render('Resources/'.$page.'.'.$request->getRequestFormat().'.twig');
    }

    public function page($page, Request $request)
    {
        return $this->render('Pages/'.$page.'.'.$request->getRequestFormat().'.twig');
    }

    /**
    * @Cache(maxage="21600", public=true, lastModified="asset_update")
    */
    public function manifest()
    {
        return $this->render('Resources/site.manifest.twig');
    }
}
