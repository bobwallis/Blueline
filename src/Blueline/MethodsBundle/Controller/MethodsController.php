<?php
namespace Blueline\MethodsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Blueline\BluelineBundle\Helpers\Search;
use Blueline\BluelineBundle\Helpers\Text;
use Blueline\MethodsBundle\Entity\Method;
use Blueline\MethodsBundle\Helpers\Stages;
use Blueline\MethodsBundle\Helpers\Classifications;
use Blueline\MethodsBundle\Helpers\PlaceNotation;

class MethodsController extends Controller
{
    public function welcomeAction()
    {
        $request = $this->getRequest();
        $format = $request->getRequestFormat();

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

        return $this->render('BluelineMethodsBundle::welcome.'.$format.'.twig', array(), $response);
    }

    public function searchAction($searchVariables = array())
    {
        $request = $this->getRequest();
        $format = $request->getRequestFormat();

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

        $methodRepository = $this->getDoctrine()->getManager()->getRepository('BluelineMethodsBundle:Method');
        $searchVariables = empty($searchVariables) ? Search::requestToSearchVariables($request, array( 'title', 'stage', 'classification', 'notation', 'leadHeadCode', 'leadHead', 'fchGroups', 'rwRef', 'bnRef', 'tdmmRef', 'pmmRef', 'lengthOfLead', 'numberOfHunts', 'little', 'differential', 'plain', 'trebleDodging', 'palindromic', 'doubleSym', 'rotational' )) : $searchVariables;

        $methods = $methodRepository->findBySearchVariables($searchVariables);
        $count = (count($methods) > 0) ? $methodRepository->findCountBySearchVariables($searchVariables) : 0;

        $pageActive = max(1, ceil(($searchVariables['offset']+1)/$searchVariables['count']));
        $pageCount =  max(1, ceil($count / $searchVariables['count']));

        return $this->render('BluelineMethodsBundle::search.'.$format.'.twig', compact('searchVariables', 'count', 'pageActive', 'pageCount', 'methods'), $response);
    }

    public function viewAction($title)
    {
        $request = $this->getRequest();
        $format = $request->getRequestFormat();

        // If the title is empty redirect to version without slash
        if( empty($title) ) {
            return $this->redirect( $this->generateUrl('Blueline_Methods_welcome', array(
                'chromeless' => (($format == 'html') ? intval($request->query->get('chromeless')) ?: null : null)
            ), 301) );
        }

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
                if ($section == 'numbers') {
                    $url = $this->generateUrl('Blueline_Methods_view', array(
                        'scale'   => intval($request->query->get('scale')) ?: null,
                        'title'   => implode('|', array_map(function ($m) { return $m['url']; }, $methodsCheck)),
                        '_format' => 'png'
                    ) );
                    return $this->redirect($url, 301);
                } elseif (!$section) {
                    $section = 'numbers';
                }
                if (!in_array($section, ['numbers', 'line', 'grid'])) {
                    throw $this->createAccessDeniedException("Style must be unset, or one of 'numbers', 'line' or 'grid'.");
                }
                $process = new Process('phantomjs --disk-cache=true --load-images=false "'.__DIR__.'/../Resources/phantomjs/render.js" "'.$this->generateUrl('Blueline_Methods_view', array( 'title' => implode('|', array_map(function ($m) { return $m['url']; }, $methodsCheck)) ), true).'" "'.$section.'" '.(intval($request->query->get('scale')) ?: 1).' 2>&1');
                $process->mustRun();
                $response->setContent($process->getOutput());

                return $response;
            default:
                return $this->render('BluelineMethodsBundle::view.'.$format.'.twig', compact('pageTitle', 'methods'), $response);
        }
    }

    public function viewCustomAction()
    {
        $request = $this->getRequest();
        $format = $request->getRequestFormat();

        // Create basic response object
        $response = new Response();
        if ($this->container->getParameter('kernel.environment') == 'prod') {
            $response->setMaxAge(129600);
            $response->setPublic();
        }

        // Collect passed in variables that are permissible
        $vars = array();
        foreach (array( 'notation', 'title', 'stage', 'ruleOffs' ) as $key) {
            $value = trim($request->query->get($key));
            if (!empty($value)) {
                $vars[$key] = $value;
            }
        }

        // Check we have the bare minimum of information required
        if (!isset($vars['notation'], $vars['stage'])) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException("Request requires at least 'notation' and 'stage' to be set");
        }

        // Do some basic conversion
        $vars['stage'] == intval($vars['stage']);
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

        // Other wise create and display the custom method
        $methods = array( new Method($vars) );
        $pageTitle = $vars['title'];

        return $this->render('BluelineMethodsBundle::view.'.$format.'.twig', compact('pageTitle', 'methods'), $response);
    }

    public function exportAction()
    {
        $request = $this->getRequest();
        $format = $request->getRequestFormat();

        // Create basic response object
        $response = new Response();
        if ($this->container->getParameter('kernel.environment') == 'prod') {
            $response->setMaxAge(129600);
            $response->setPublic();
        }

        return $this->render('BluelineMethodsBundle::export.html.twig', array(), $response);
    }

    public function sitemapAction()
    {
        $request = $this->getRequest();
        $format = $request->getRequestFormat();

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

        $methods = $this->getDoctrine()->getManager()->createQuery('SELECT partial m.{title,url} FROM BluelineMethodsBundle:Method m')->getArrayResult();

        return $this->render('BluelineMethodsBundle::sitemap.'.$format.'.twig', compact('methods'), $response);
    }
}
