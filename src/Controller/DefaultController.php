<?php
namespace Blueline\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;

/**
 * Controller for static page rendering.
 *
 * Routes:
 * - GET /: Welcome page (mapped to page("welcome"))
 * - GET /{page}.{_format}: Static pages via page() (page: about|privacy-app|methods/notation, _format: html)
 * - GET /{page}.{_format}: Static resources via resource() (page: robots|humans|sitemap|sitemap_root|manifest, _format: json|txt|xml)
 *
 * Renders Twig templates from Resources/ and Pages/ directories with format
 * negotiation (HTML, JSON, etc.) based on request.format parameter.
 *
 * Long caching (3+ days) suitable for rarely-updated static content.
 */
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
