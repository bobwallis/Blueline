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
use Blueline\MethodsBundle\Helpers\URL;
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
    public function searchAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $methodRepository = $em->getRepository('BluelineMethodsBundle:Method');
        $methodMetadata   = $em->getClassMetadata('BluelineMethodsBundle:Method');

        // Parse search variables
        $searchVariables = Search::requestToSearchVariables($request, array_values($methodMetadata->fieldNames));
        $searchVariables['fields'] = implode(',', array_values(array_unique(empty($searchVariables['fields'])? array('title', 'url', 'notation') : array_merge($searchVariables['fields'], ($request->getRequestFormat()=='html')?array('title', 'url'):array()))));
        $searchVariables['sort']   = empty($searchVariables['sort'])? 'magic' : $searchVariables['sort'];

        // Create query
        $query = Search::searchVariablesToBasicQuery($searchVariables, $methodRepository, $methodMetadata);
        if (isset($searchVariables['q'])) {
            if (strpos($searchVariables['q'], '/') === 0 && strlen($searchVariables['q']) > 1) {
                if (@preg_match($searchVariables['q'].'/', ' ') === false) {
                    throw new BadRequestHttpException('Invalid regular expression');
                }
                $query->andWhere('REGEXP(e.title, :qRegexp) = TRUE')
                    ->setParameter('qRegexp', trim($searchVariables['q'], '/'));
            } else {
                $qExplode = explode(' ', $searchVariables['q']);
                if (count($qExplode) > 1) {
                    $last = array_pop($qExplode);
                    // If the search ends in a number then use that to filter by stage and remove it from the title search
                    $lastStage = Stages::toInt($last);
                    if ($lastStage > 0) {
                        $query->andWhere('e.stage = :stageFromQ')
                            ->setParameter('stageFromQ', $lastStage);
                        $searchVariables['q'] = implode(' ', $qExplode);
                        $last = array_pop($qExplode);
                    } else {
                        $searchVariables['q'] = implode(' ', $qExplode).($last ? ' '.$last : '');
                    }

                    // Remove non-name parts of the search to test against nameMetaphone
                    if (Classifications::isClass($last)) {
                        $query->andWhere('e.classification = :classificationFromQ')
                            ->setParameter('classificationFromQ', ucwords(strtolower($last)));
                        $last = array_pop($qExplode);
                    }
                    while (1) {
                        switch (strtolower($last)) {
                            case 'little':
                                $query->andWhere('e.little = :littleFromQ')
                                    ->setParameter('littleFromQ', true);
                                $last = array_pop($qExplode);
                                break;
                            case 'differential':
                                $query->andWhere('e.differential = :differentialFromQ')
                                    ->setParameter('differentialFromQ', true);
                                $last = array_pop($qExplode);
                                break;
                            default:
                                break 2;
                        }
                    }
                    // This will be used to test against nameMetaphone
                    $nameMetaphone = metaphone(implode(' ', $qExplode).($last ? ' '.$last : ''));
                } else {
                    $nameMetaphone = metaphone($searchVariables['q']);
                }

                if (empty($nameMetaphone)) {
                    $query->andWhere('LOWER(e.title) LIKE :qLike')
                        ->setParameter('qLike', Search::prepareStringForLike($searchVariables['q']));
                } else {
                    $query->andWhere($query->expr()->orx('LOWER(e.title) LIKE :qLike', 'LEVENSHTEIN_RATIO( :qMetaphone, e.nameMetaphone ) > 90'))
                       ->setParameter('qLike', Search::prepareStringForLike($searchVariables['q']))
                       ->setParameter('qMetaphone', $nameMetaphone);
                }
            }
        }

        // Execute
        $methods = $query->getQuery()->getResult();
        $count = (count($methods) < $searchVariables['count'])? count($methods) : Search::queryToCountQuery($query, $methodMetadata)->getQuery()->getSingleScalarResult();
        $pageActive = max(1, ceil(($searchVariables['offset']+1)/$searchVariables['count']));
        $pageCount =  max(1, ceil($count / $searchVariables['count']));

        return $this->render('BluelineMethodsBundle::search.'.$request->getRequestFormat().'.twig', compact('searchVariables', 'count', 'pageActive', 'pageCount', 'methods'));
    }

    /**
    * @Cache(maxage="129600", public=true, lastModified="database_update")
    */
    public function viewAction($url, Request $request)
    {
        $format = $request->getRequestFormat();
        $methodRepository = $this->getDoctrine()->getManager()->getRepository('BluelineMethodsBundle:Method');

        // If the title is empty redirect to version without slash
        if (empty($url)) {
            return $this->redirect($this->generateUrl('Blueline_Methods_welcome', array(
                'chromeless' => (($format == 'html') ? intval($request->query->get('chromeless')) ?: null : null)
            ), 301));
        }

        // Decode and canonicalise the requested URLs
        $url = URL::canonical($url);

        // Redirect to the right URL if we're at a wrong one
        if ($request->get('url') !== $url) {
            $redirect = $this->generateUrl('Blueline_Methods_view', array(
                'chromeless' => (($format == 'html') ? intval($request->query->get('chromeless')) ?: null : null),
                'scale'      => intval($request->query->get('scale')) ?: null,
                'style'      => strtolower($request->query->get('style')) ?: null,
                'url'      => $url,
                '_format'    => $format
            ));
            return $this->redirect($redirect, 301);
        }
        // Perform extra checks for PNG format requests
        if ($format == 'png') {
            // Validate scale parameter
            if ((intval($request->query->get('scale')) ?: 1) > 4 && $this->container->getParameter('kernel.environment') == 'prod') {
                throw $this->createAccessDeniedException('Maximum scale is 4 unless in developer mode.');
            }
            // Validate section parameter
            $section = $request->query->get('style');
            if (!in_array($section, ['numbers', 'line', 'grid'])) {
                throw $this->createAccessDeniedException("Style must be unset, or one of 'numbers', 'line' or 'grid'.");
            }
            // Normalise
            if (!$section || intval($request->query->get('scale')) === 0) {
                $url = $this->generateUrl('Blueline_Methods_view', array(
                    'scale'   => (intval($request->query->get('scale')) ?: 1),
                    'style'   => (!$section)? 'numbers' : $section,
                    'url'     => $url,
                    '_format' => 'png'
                ));
                return $this->redirect($url, 301);
            }
        }

        $method = $methodRepository->findByURLJoiningPerformancesAndCollections($url);
        
        if (!$method) {
            throw $this->createNotFoundException('The method does not exist');
        }
        
        $similarMethods = array(
            'differentOnlyAtLeadEnd' => $methodRepository->similarMethodsDifferentOnlyAtTheLeadEnd($method->getUrl()),
            'other' => $methodRepository->similarMethodsExcludingThoseOnlyDifferentAtTheLeadEnd($method->getUrl())
        );

        // Create response
        switch ($format) {
            case 'png':
                $process = new Process('phantomjs --disk-cache=true --load-images=false "'.__DIR__.'/../Resources/phantomjs/render.js" "'.$this->generateUrl('Blueline_Methods_view', array('url' => $url), UrlGeneratorInterface::ABSOLUTE_URL).'" "'.$section.'" '.(intval($request->query->get('scale')) ?: 1).' 2>&1');
                $process->mustRun();
                return new Response($process->getOutput());
            default:
                return $this->render('BluelineMethodsBundle::view.'.$format.'.twig', compact('method', 'similarMethods'));
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
            $url = $this->generateUrl('Blueline_Methods_view', array( 'chromeless' => (($format == 'html') ? intval($request->query->get('chromeless')) ?: null : null), 'url' => $methodsCheck[0]['url'], '_format' => $format ));
            return $this->redirect($url, 301);
        }

        // Otherwise create and display the custom method
        $method = new Method($vars);
        $custom = true;

        // Create response
        switch ($format) {
            case 'png':
                if ((intval($request->query->get('scale')) ?: 1) > 4 && $this->container->getParameter('kernel.environment') == 'prod') {
                    throw $this->createAccessDeniedException('Maximum scale is 4 unless in developer mode.');
                }
                $section = $request->query->get('style');
                if (!in_array($section, ['numbers', 'line', 'grid'])) {
                    throw $this->createAccessDeniedException("Style must be unset, or one of 'numbers', 'line' or 'grid'.");
                }
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
                $processUrl = $this->generateUrl('Blueline_Methods_custom_view', array(
                    'stage'    => $vars['stage'],
                    'notation' => $vars['notation']
                ), true);
                $process = new Process('phantomjs --disk-cache=true --load-images=false "'.__DIR__.'/../Resources/phantomjs/render.js" "'.$processUrl.'" "'.$section.'" '.(intval($request->query->get('scale')) ?: 1).' 2>&1');
                $process->mustRun();
                return new Reponse($process->getOutput());
            default:
                return $this->render('BluelineMethodsBundle::view.'.$format.'.twig', compact('method', 'custom'));
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
