<?php
namespace Blueline\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Blueline\Helpers\PlaceNotation;

class NotationController extends AbstractController
{
    /**
    * @Cache(maxage="129600", public=true)
    */
    public function parse(Request $request)
    {
        // Collect passed in variables that are permissible
        $vars = array();
        foreach (array( 'notation', 'stage' ) as $key) {
            $value = trim($request->query->get($key));
            if (!empty($value)) {
                $vars[$key] = $value;
            }
        }

        // Check we have the bare minimum of information required
        if (!isset($vars['notation'])) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException("Request requires at least 'notation' to be set");
        }

        // Convert
        $vars['stage'] = isset($vars['stage'])? intval($vars['stage']) : max(array_map(function ($c) { return PlaceNotation::bellToInt($c); }, array_filter(str_split($vars['notation']), function ($c) { return preg_match('/[0-9A-Z]/', $c); })));
        $vars['expanded'] = PlaceNotation::expand($vars['notation'], $vars['stage']);
        $vars['siril'] = PlaceNotation::siril($vars['notation'], $vars['stage']);

        $response = new Response();
        switch($request->getRequestFormat()) {
            case 'txt':
                $response->setContent($vars['expanded']."\n".$vars['siril']);
                break;
            case 'json':
                $response->setContent(json_encode(array( 'stage' => $vars['stage'], 'expanded' => $vars['expanded'], 'siril' => $vars['siril'])));
                break;

        }

        return $response;
    }
}
