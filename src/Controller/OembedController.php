<?php

namespace Blueline\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Regex as RegexConstraint;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Controller for oEmbed protocol support.
 *
 * Routes:
 * - GET /services/oembed.{_format}: oEmbed discovery endpoint (_format route allows json|xml)
 *
 * Query parameters:
 * - url (required): URL to embed (validated against allowlist)
 * - format (optional): Response format (implementation currently supports JSON responses only)
 *
 * Implements oEmbed 1.0 specification for embedding method detail pages
 * in third-party sites. Validates requested URLs against allowlist and
 * returns metadata including image URL and dimensions.
 *
 * Reference: https://oembed.com/
 */
class OembedController extends AbstractController
{
    #[Cache(maxage: 129600, public: true)]
    public function index(Request $request, ParameterBagInterface $params)
    {
        $url = $request->query->get('url');

        // Throw correct error with non JSON requests
        if ($request->getRequestFormat() != 'json') {
            throw new HttpException(501, 'Not implemented');
        }

        // Check it's a valid URL
        $allowedURLs = array(
            'methods_view' => '^'.str_replace(array('/','.','TITLE'), array('\/','\.','.+'), $params->get('blueline.endpoint') . $this->generateUrl('Blueline_Methods_view', array('url' => 'TITLE')))
        );
        $urlConstraint = new RegexConstraint(pattern: '/'.implode('|', array_values($allowedURLs)).'/', message: 'Invalid URL');
        $validator = Validation::createValidator();
        $errors = $validator->validate($url, $urlConstraint);
        if ($errors->offsetExists(0)) {
            throw new \Exception($errors[0]);
        }

        // Create basic response object
        $response = new JsonResponse();

        // Method view
        if (preg_match('/'.$allowedURLs['methods_view'].'/', $url) == 1) {
            // Get method details
            $file_headers = @get_headers($url.'.json');
            if ($file_headers[0] == 'HTTP/1.1 404 Not Found') {
                throw $this->createNotFoundException('Method not found');
            }
            $method = json_decode(file_get_contents($url.'.json'));
            $imageSize = getimagesize($url.'.png?scale=1&style=numbers');
            $response->setData(array(
                'type' => 'photo',
                'version' => '1.0',
                'title' => $method[0]->title,
                'provider_name' => 'Blueline',
                'provider_url' => $params->get('blueline.endpoint') . $this->generateUrl('Blueline_welcome'),
                'url' => $url.'.png?scale=1&style=numbers',
                'width' => $imageSize[0] ?? 0,
                'height' => $imageSize[1] ?? 0
            ));
        } else {
            throw $this->createNotFoundException('URL not supported');
        }
        return $response;
    }
}
