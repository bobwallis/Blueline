<?php
namespace Blueline\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;

class DefaultController extends AbstractController
{
    #[Cache(maxage: 129600, public: true)]
    public function resource($page, Request $request)
    {
        return $this->render('Resources/'.$page.'.'.$request->getRequestFormat().'.twig');
    }

    #[Cache(maxage: 129600, public: true)]
    public function page($page, Request $request)
    {
        return $this->render('Pages/'.$page.'.'.$request->getRequestFormat().'.twig');
    }

    #[Cache(maxage: 21600, public: true)]
    public function manifest()
    {
        return $this->render('Resources/site.manifest.twig');
    }
}
