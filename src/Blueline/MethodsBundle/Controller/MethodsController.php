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
use Blueline\MethodsBundle\Entity\Performance;
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
                $reducedQ = $searchVariables['q'];
                $qExplode = explode(' ', $reducedQ);
                if (count($qExplode) > 1) {
                    $last = array_pop($qExplode);
                    // If the search ends in a number then use that to filter by stage and remove it from the title search
                    $lastStage = Stages::toInt($last);
                    if ($lastStage > 0) {
                        $query->andWhere('e.stage = :stageFromQ')
                            ->setParameter('stageFromQ', $lastStage);
                        $reducedQ = implode(' ', $qExplode);
                        $last = array_pop($qExplode);
                    } else {
                        $reducedQ = implode(' ', $qExplode).($last ? ' '.$last : '');
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
                    $query->andWhere('LOWER(e.title) LIKE :qLikeR')
                        ->setParameter('qLikeR', Search::prepareStringForLike($reducedQ));
                } else {
                    $query->andWhere($query->expr()->orx('LOWER(e.title) LIKE :qLikeR', 'LEVENSHTEIN_RATIO( :qMetaphone, e.nameMetaphone ) > 90'))
                       ->setParameter('qLikeR', Search::prepareStringForLike($reducedQ))
                       ->setParameter('qMetaphone', $nameMetaphone);
                }

                // This is commented out as it breaks the ability to filter searches by stage, classification, etc (using &stage=6&classification=Surprise and similar)
                // TODO: Remember why I added this in the first place
                // Never exclude a basic match
                //$query->orWhere('LOWER(e.title) LIKE :qLike')
                //    ->setParameter('qLike', Search::prepareStringForLike($searchVariables['q']));
            }
        }

        // Execute
        $methods = $query->getQuery()->getResult();
        $count = (count($methods)+$searchVariables['offset'] < $searchVariables['count'])? count($methods) : Search::queryToCountQuery($query, $methodMetadata)->getQuery()->getSingleScalarResult();
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

        // Redirect to the canonical URL if we're at a wrong one
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
            if (isset($section) && !in_array($section, ['numbers', 'lines', 'diagrams', 'grid'])) {
                throw $this->createAccessDeniedException("Style must be unset, or one of 'numbers', 'lines', 'diagrams' or 'grid'.");
            }
            // Normalise
            if (!isset($section) || intval($request->query->get('scale')) === 0) {
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
            // Try and find a renamed method
            $peformanceRepository = $this->getDoctrine()->getManager()->getRepository('BluelineMethodsBundle:Performance');
            $renamedUrl = $peformanceRepository->findURLByRungURL($url);
            if ($renamedUrl !== null) {
                $redirect = $this->generateUrl('Blueline_Methods_view', array(
                    'chromeless' => (($format == 'html') ? intval($request->query->get('chromeless')) ?: null : null),
                    'scale'      => intval($request->query->get('scale')) ?: null,
                    'style'      => strtolower($request->query->get('style')) ?: null,
                    'url'      => $renamedUrl,
                    '_format'    => $format
                ));
                return $this->redirect($redirect, 301);
            }
            // Try and find a version with fixed capitalisation
            $capitalisedMethod = $methodRepository->findByURL(preg_replace_callback('/(^|_)(.)/', function($w) { return $w[1].strtoupper($w[2]); }, $url));
            if ($capitalisedMethod !== null) {
                $redirect = $this->generateUrl('Blueline_Methods_view', array(
                    'chromeless' => (($format == 'html') ? intval($request->query->get('chromeless')) ?: null : null),
                    'scale'      => intval($request->query->get('scale')) ?: null,
                    'style'      => strtolower($request->query->get('style')) ?: null,
                    'url'      => $capitalisedMethod->getUrl(),
                    '_format'    => $format
                ));
                return $this->redirect($redirect, 301);
            }
            // Otherwise fail
            throw $this->createNotFoundException('The method does not exist');
        }
        
        $similarMethods = array(
            'differentOnlyAtLeadEnd' => $methodRepository->similarMethodsDifferentOnlyAtTheLeadEnd($method->getUrl()),
            'differentOnlyAtHalfLead' => $methodRepository->similarMethodsDifferentOnlyAtTheHalfLead($method->getUrl()),
            'differentOnlyAtHalfLeadAndLeadEnd' => $methodRepository->similarMethodsDifferentOnlyAtTheHalfLeadAndLeadEnd($method->getUrl()),
            'other' => $methodRepository->similarMethodsExcludingThoseOnlyDifferentAtTheLeadEndOrHalfLead($method->getUrl())
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
                $vars[$key] = urldecode($value);
            }
        }

        // Check we have the bare minimum of information required
        if (!isset($vars['notation'])) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException("Request requires at least 'notation' to be set");
        }

        // Do some basic conversion
        $vars['stage'] = isset($vars['stage'])? intval($vars['stage']) : PlaceNotation::guessStage($vars['notation']);
        $vars['notationExpanded'] = PlaceNotation::expand($vars['notation'], $vars['stage']);

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
        $similarMethods = array(
            'differentOnlyAtLeadEnd' => null,
            'differentOnlyAtHalfLead' => null,
            'differentOnlyAtHalfLeadAndLeadEnd' => null,
            'other' => null
        );

        // Create response
        switch ($format) {
            case 'png':
                if ((intval($request->query->get('scale')) ?: 1) > 4 && $this->container->getParameter('kernel.environment') == 'prod') {
                    throw $this->createAccessDeniedException('Maximum scale is 4 unless in developer mode.');
                }
                $section = $request->query->get('style');
                if (!in_array($section, ['numbers', 'lines', 'diagrams', 'grid'])) {
                    throw $this->createAccessDeniedException("Style must be unset, or one of 'numbers', 'lines', 'diagrams' or 'grid'.");
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
                return new Response($process->getOutput());
            default:
                return $this->render('BluelineMethodsBundle::view.'.$format.'.twig', compact('method', 'custom', 'similarMethods'));
        }
    }

    /**
    * @Cache(maxage="129600", public=true, lastModified="asset_update")
    */
    public function printAction(Request $request)
    {
        return $this->render('BluelineMethodsBundle::print.html.twig');
    }

    /**
    * @Cache(maxage="129600", public=true, lastModified="asset_update")
    */
    public function exportAction(Request $request)
    {
        // Get options
        $options = array(
            'methods' => array(),
            'paper_size' => strtoupper($request->query->get('paper_size', 'A4')),
            'paper_orientation' => strtolower($request->query->get('paper_orientation', 'landscape')),
            'paper_columns' => $request->query->getInt('paper_columns', 1),
            'paper_rows' => $request->query->getInt('paper_rows', 1),
            'show_title' => $request->query->getBoolean('show_title', true),
            'show_title_position' => $request->query->get('show_title_position', 'top'),
            'show_notation' => $request->query->getBoolean('show_notation', 'false'),
            'show_placestarts' => $request->query->getBoolean('show_placestarts', true),
            'style' => $request->query->get('style', 'numbers')
        );
        $optionsForURL = $options;
        for ($i = 0; $i < $options['paper_rows']*$options['paper_columns']; ++$i) {
            $options['methods'][$i] = array(
                'title'    => $request->query->get('m'.$i.'_title', 'Untitled Method'),
                'stage'    => $request->query->getInt('m'.$i.'_stage', 4),
                'notation' => $request->query->get('m'.$i.'_notation', 'x'),
            );
            $optionsForURL['m'.$i.'_title'] = $request->query->get('m'.$i.'_title', 'Untitled Method');
            $optionsForURL['m'.$i.'_stage'] = $request->query->getInt('m'.$i.'_stage', 4);
            $optionsForURL['m'.$i.'_notation'] = $request->query->get('m'.$i.'_notation', 'x');
        }

        switch ($request->getRequestFormat()) {
            case 'html':
                return $this->render('BluelineMethodsBundle::export.html.twig', compact('options'));
            case 'pdf':
                $processUrl = $this->generateUrl('Blueline_Methods_export', $optionsForURL + array( '_format' => 'html' ), true);
                $process = new Process('phantomjs --disk-cache=true --load-images=false "'.__DIR__.'/../Resources/phantomjs/export.js" "'.$processUrl.'" "'.$options['paper_size'].'" "'.$options['paper_orientation'].'" 2>&1');
                $process->mustRun();
                return new Response($process->getOutput());
        }
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
