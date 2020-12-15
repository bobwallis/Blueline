<?php
namespace Blueline\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Regex as RegexConstraint;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class OembedController extends AbstractController
{
    /**
    * @Cache(maxage="129600", public=true, lastModified="asset_update")
    */
    public function index(Request $request)
    {
        $url = $request->query->get('url');

        // Throw correct error with non JSON requests
        if ($request->getRequestFormat() != 'json') {
            throw new HttpException(501, 'Not implemented');
        }

        // Check it's a valid URL
        $allowedURLs = array(
            'methods_view' => '^'.str_replace( array('/','.','TITLE'), array('\/','\.','.+'), $this->generateUrl('Blueline_Methods_view', array('url' => 'TITLE'), UrlGeneratorInterface::ABSOLUTE_URL))
        );
        $urlConstraint = new RegexConstraint(array('pattern' => '/'.implode('|', array_values($allowedURLs)).'/', 'message' => 'Invalid URL'));
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
                'provider_url' => $this->generateUrl('Blueline_welcome', array(), UrlGeneratorInterface::ABSOLUTE_URL),
                'url' => $url.'.png?scale=1&style=numbers',
                'width' => $imageSize[0],
                'height' => $imageSize[1]
            ));
        }
        else {
            throw $this->createNotFoundException('URL not supported');
        }
        return $response;
    }
}
