<?php
namespace Blueline\MethodsBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Blueline\BluelineBundle\Helpers\Search;
use Blueline\BluelineBundle\Helpers\Text;
use Blueline\MethodsBundle\Entity\Method;
use Blueline\MethodsBundle\Helpers\Stages;
use Blueline\MethodsBundle\Helpers\Classifications;
use Blueline\MethodsBundle\Helpers\PlaceNotation;

/**
* @Cache(maxage="129600", public=true, lastModified="asset_update")
*/
class MethodsController extends Controller
{
    public function welcomeAction(Request $request)
    {
        return $this->render('BluelineMethodsBundle::welcome.'.$request->getRequestFormat().'.twig');
    }

    /**
    * @Cache(maxage="129600", public=true, lastModified="database_update")
    */
    public function searchAction($searchVariables = array(), Request $request)
    {
        $methodRepository = $this->getDoctrine()->getManager()->getRepository('BluelineMethodsBundle:Method');
        $searchVariables = empty($searchVariables) ? Search::requestToSearchVariables($request, array( 'title', 'stage', 'classification', 'notation', 'leadHeadCode', 'leadHead', 'fchGroups', 'rwRef', 'bnRef', 'tdmmRef', 'pmmRef', 'lengthOfLead', 'numberOfHunts', 'little', 'differential', 'plain', 'trebleDodging', 'palindromic', 'doubleSym', 'rotational' )) : $searchVariables;

        $methods = $methodRepository->findBySearchVariables($searchVariables);
        $count = (count($methods) > 0) ? $methodRepository->findCountBySearchVariables($searchVariables) : 0;

        $pageActive = max(1, ceil(($searchVariables['offset']+1)/$searchVariables['count']));
        $pageCount =  max(1, ceil($count / $searchVariables['count']));

        return $this->render('BluelineMethodsBundle::search.'.$request->getRequestFormat().'.twig', compact('searchVariables', 'count', 'pageActive', 'pageCount', 'methods'));
    }

    /**
    * @Cache(maxage="129600", public=true, lastModified="database_update")
    */
    public function viewAction($title, Request $request)
    {
        $format = $request->getRequestFormat();

        // If the title is empty redirect to version without slash
        if( empty($title) ) {
            return $this->redirect( $this->generateUrl('Blueline_Methods_welcome', array(
                'chromeless' => (($format == 'html') ? intval($request->query->get('chromeless')) ?: null : null)
            ), 301) );
        }

        // Decode and canonicalise the requested URLs
        $urls = array_map(function ($u) {
            // Decode
            $u = urldecode($u);
            // Replace S with Surprise, etc...
            $classificationsInitials = array_map(function ($c) {
                return implode('', array_map(function ($w) { return $w[0]; }, explode(' ', $c)));
            }, Classifications::toArray());
            $matches = array();
            if (preg_match('/_('.implode('|', $classificationsInitials).')_('.implode('|', Stages::toArray()).')$/', $u, $matches)) {
                $initial = $matches[1];
                $classification = str_replace(' ', '_', Classifications::toArray()[array_search($initial, $classificationsInitials)]);
                $u = preg_replace('/'.$initial.'_('.implode('|', Stages::toArray()).')$/', $classification.'_$1', $u);
            }

            return $u;
        }, explode('|', $title));
        // Convert URLs into titles
        $titles = array_map(function ($m) { return str_replace('_', ' ', $m); }, $urls);

        // Create lower case arrays for use in search
        $urlsLower = array_map("strtolower", $urls);
        $titlesLower = array_map("strtolower", $titles);
        
        $methodRepository = $this->getDoctrine()->getManager()->getRepository('BluelineMethodsBundle:Method');
        $em = $this->getDoctrine()->getManager();

        // Check we are at the canonical URL for the content
        // First check for titles
        $methodsCheck = $em->createQuery('
            SELECT partial m.{title,url} FROM BluelineMethodsBundle:Method m
            WHERE LOWER(m.title) IN (:titles) OR LOWER(m.url) IN (:urls) OR LOWER(m.title) IN (:titlesNoSpacesCockUpFix)')
            ->setParameter('urls', $urlsLower)
            ->setParameter('titles', $titlesLower)
            ->setParameter('titlesNoSpacesCockUpFix', array_map(function ($m) { return preg_replace( '/^ /', '', strtolower(preg_replace('/(?<!\ )[A-Z]/', ' $0', $m))); }, $titles))
            ->setMaxResults(count($titlesLower))
            ->getArrayResult();
        if (empty($methodsCheck) || count($methodsCheck) < count($methodsCheck)) {
            // Then check if place notation has been given
            $notationExpander = new PlaceNotation();
            $notationTest = array_map(array( $notationExpander, 'expand' ), $urls);
            $methodsCheck = $em->createQuery('
                SELECT partial m.{title,url} FROM BluelineMethodsBundle:Method m
                WHERE m.notationExpanded IN (:notations)')
                ->setParameter('notations', $notationTest)
                ->setMaxResults(count($notationTest))
                ->getArrayResult();
            if (empty($methodsCheck) || count($methodsCheck) < count($methodsCheck)) {
                throw $this->createNotFoundException('The method does not exist');
            }
        }
        $url = $this->generateUrl('Blueline_Methods_view', array(
            'chromeless' => (($format == 'html') ? intval($request->query->get('chromeless')) ?: null : null),
            'scale'      => intval($request->query->get('scale')) ?: null,
            'style'      => strtolower($request->query->get('style')) ?: null,
            'title'      => implode('|', array_map(function ($m) { return $m['url']; }, $methodsCheck)),
            '_format'    => $format
        ) );
        if ($request->getRequestUri() !== urldecode($url)) {
            return $this->redirect($url, 301);
        }

        $pageTitle = Text::toList(array_map(function ($m) { return $m['title']; }, $methodsCheck));
        $methods = array();

        foreach ($methodsCheck as $methodTitle) {
            // Get information about the method
            $method = $em->createQuery('
                SELECT m FROM BluelineMethodsBundle:Method m
                LEFT JOIN m.performances p
                LEFT JOIN m.collections c
                WHERE m.title = :title')
            ->setParameter('title', $methodTitle['title'])
            ->getSingleResult();
            $methods[] = $method;
        }

        // Create response
        switch ($format) {
            case 'png':
                if ((intval($request->query->get('scale')) ?: 1) > 4 && $this->container->getParameter('kernel.environment') == 'prod') {
                    throw $this->createAccessDeniedException('Maximum scale is 4 unless in developer mode.');
                }
                $section = $request->query->get('style');
                if( !$section || intval($request->query->get('scale')) === 0 ) {
                    $url = $this->generateUrl('Blueline_Methods_view', array(
                        'scale'   => (intval($request->query->get('scale')) ?: 1),
                        'style'   => (!$section)? 'numbers' : $section,
                        'title'   => implode('|', array_map(function ($m) { return $m['url']; }, $methodsCheck)),
                        '_format' => 'png'
                    ) );
                    return $this->redirect($url, 301);
                }
                if (!in_array($section, ['numbers', 'line', 'grid'])) {
                    throw $this->createAccessDeniedException("Style must be unset, or one of 'numbers', 'line' or 'grid'.");
                }
                $process = new Process('phantomjs --disk-cache=true --load-images=false "'.__DIR__.'/../Resources/phantomjs/render.js" "'.$this->generateUrl('Blueline_Methods_view', array( 'title' => implode('|', array_map(function ($m) { return $m['url']; }, $methodsCheck)) ), UrlGeneratorInterface::ABSOLUTE_URL).'" "'.$section.'" '.(intval($request->query->get('scale')) ?: 1).' 2>&1');
                $process->mustRun();
                return new Response($process->getOutput());
            default:
                return $this->render('BluelineMethodsBundle::view.'.$format.'.twig', compact('pageTitle', 'methods'));
        }
    }

    /**
    * @Cache(maxage="129600", public=true, lastModified="database_update")
    */
    public function viewCustomAction(Request $request)
    {
        $format = $request->getRequestFormat();

        // Collect passed in variables that are permissible
        $vars = array();
        foreach (array('notation', 'title', 'stage') as $key) {
            $value = trim($request->query->get($key));
            if (!empty($value)) {
                $vars[$key] = $value;
            }
        }

        // Check we have the bare minimum of information required
        if (!isset($vars['notation'])) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException("Request requires at least 'notation' to be set");
        }

        // Do some basic conversion
        $vars['stage'] = isset($vars['stage'])? intval($vars['stage']) : max(array_map(function ($c) { return PlaceNotation::bellToInt($c); }, array_filter(str_split($vars['notation']), function ($c) { return preg_match('/[0-9A-Z]/', $c); })));
        $vars['notationExpanded'] = PlaceNotation::expand($vars['notation'], $vars['stage']);
        $vars['title'] = isset($vars['title']) ? $vars['title'] : 'Unrung '.Stages::toString($vars['stage']).' Method';

        // Check whether the method already exists and redirect to it if so
        $methodsCheck = $this->getDoctrine()->getManager()->createQuery('
            SELECT partial m.{title,url} FROM BluelineMethodsBundle:Method m
            WHERE m.notationExpanded = (:notation) AND m.stage = (:stage)')
            ->setParameter('notation', $vars['notationExpanded'])
            ->setParameter('stage', $vars['stage'])
            ->getArrayResult();
        if (!empty($methodsCheck)) {
            $url = $this->generateUrl('Blueline_Methods_view', array( 'chromeless' => (($format == 'html') ? intval($request->query->get('chromeless')) ?: null : null), 'title' => implode('|', array_map(function ($m) { return $m['url']; }, $methodsCheck)), '_format' => $format ));
            return $this->redirect($url, 301);
        }

        // Otherwise create and display the custom method
        $methods = array( new Method($vars) );
        $pageTitle = $vars['title'];
        $custom = true;

        // Create response
        switch ($format) {
            case 'png':
                if ((intval($request->query->get('scale')) ?: 1) > 4 && $this->container->getParameter('kernel.environment') == 'prod') {
                    throw $this->createAccessDeniedException('Maximum scale is 4 unless in developer mode.');
                }
                $section = $request->query->get('style');
                if (!$section || intval($request->query->get('scale')) === 0) {
                    $url = $this->generateUrl('Blueline_Methods_custom_view', array(
                        'stage'    => $vars['stage'],
                        'notation' => $vars['notation'],
                        'scale'    => (intval($request->query->get('scale')) ?: 1),
                        'style'    => (!$section)? 'numbers' : $section,
                        '_format'  => 'png'
                    ));
                    return $this->redirect($url, 301);
                }
                if (!in_array($section, ['numbers', 'line', 'grid'])) {
                    throw $this->createAccessDeniedException("Style must be unset, or one of 'numbers', 'line' or 'grid'.");
                }
                $processUrl = $this->generateUrl('Blueline_Methods_custom_view', array(
                    'stage'    => $vars['stage'],
                    'notation' => $vars['notation']
                ), true);
                $process = new Process('phantomjs --disk-cache=true --load-images=false "'.__DIR__.'/../Resources/phantomjs/render.js" "'.$processUrl.'" "'.$section.'" '.(intval($request->query->get('scale')) ?: 1).' 2>&1');
                $process->mustRun();
                return new Reponse($process->getOutput());
            default:
                return $this->render('BluelineMethodsBundle::view.'.$format.'.twig', compact('pageTitle', 'methods', 'custom'));
        }
    }

    /**
    * @Cache(maxage="129600", public=true, lastModified="database_update")
    */
    public function exportAction(Request $request)
    {
        return $this->render('BluelineMethodsBundle::export.html.twig');
    }

    /**
    * @Cache(maxage="604800", public=true, lastModified="database_update")
    */
    public function sitemapAction($page)
    {
        $methods = $this->getDoctrine()->getManager()
                    ->createQuery('SELECT partial m.{title,url} FROM BluelineMethodsBundle:Method m ORDER BY m.url')
                    ->setMaxResults(12500)
                    ->setFirstResult(($page-1)*12500)
                    ->getArrayResult();
        return $this->render('BluelineMethodsBundle::sitemap.xml.twig', compact('methods'));
    }
}
