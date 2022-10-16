<?php
namespace Blueline\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Blueline\Helpers\Search;
use Blueline\Entity\Method;
use Blueline\Entity\Performance;
use Blueline\Helpers\URL;
use Blueline\Helpers\Stages;
use Blueline\Helpers\Classifications;
use Blueline\Helpers\PlaceNotation;

/**
* @Cache(maxage="129600", public=true, lastModified="asset_update")
*/
class MethodsController extends AbstractController
{
    public function welcome(Request $request)
    {
        return $this->render('Methods/welcome.'.$request->getRequestFormat().'.twig');
    }

    /**
    * @Cache(maxage="129600", public=true, lastModified="database_update")
    */
    public function search(Request $request, EntityManagerInterface $em)
    {
        $methodRepository = $em->getRepository(Method::class);
        $methodMetadata   = $em->getClassMetadata(Method::class);

        // Parse search variables
        $searchVariables = Search::requestToSearchVariables($request, array_values($methodMetadata->fieldNames));
        $searchVariables['fields'] = implode(',', array_values(array_unique(empty($searchVariables['fields'])? array('title', 'abbreviation', 'url', 'classification', 'stage', 'notation', 'ruleOffs', 'calls', 'callingPositions') : array_merge($searchVariables['fields'], ($request->getRequestFormat()=='html')?array('title', 'url'):array()))));
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

        return $this->render('Methods/search.'.$request->getRequestFormat().'.twig', compact('searchVariables', 'count', 'pageActive', 'pageCount', 'methods'));
    }

    /**
    * @Cache(maxage="129600", public=true, lastModified="database_update")
    */
    public function view($url, Request $request, EntityManagerInterface $em)
    {
        $format = $request->getRequestFormat();
        $methodRepository = $em->getRepository(Method::class);

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
            if ((intval($request->query->get('scale')) ?: 1) > 4) {
                throw $this->createAccessDeniedException('Maximum scale is 4.');
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
            $peformanceRepository = $em->getRepository(Performance::class);
            $renamedUrl = $peformanceRepository->findURLByRungURL($url);
            if ($renamedUrl !== null) {
                $redirect = $this->generateUrl('Blueline_Methods_view', array(
                    'chromeless' => (($format == 'html') ? intval($request->query->get('chromeless')) ?: null : null),
                    'scale'      => intval($request->query->get('scale')) ?: null,
                    'style'      => strtolower($request->query->get('style')) ?: null,
                    'url'        => $renamedUrl,
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
                    'url'        => $capitalisedMethod->getUrl(),
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
                return $this->redirect($this->getParameter('blueline.image_endpoint').'?url='.$this->generateUrl('Blueline_Methods_view', array('url' => $url), UrlGeneratorInterface::ABSOLUTE_URL).'&scale='.$request->query->get('scale').'&style='.$request->query->get('style'), 302);
            default:
                return $this->render('Methods/view.'.$format.'.twig', compact('method', 'similarMethods'));
        }
    }

    /**
    * @Cache(maxage="129600", public=true, lastModified="database_update")
    */
    public function viewCustom(Request $request, EntityManagerInterface $em)
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
        $methodsCheck = $em->createQuery('
            SELECT partial m.{title,url} FROM Blueline\Entity\Method m
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
                if ((intval($request->query->get('scale')) ?: 1) > 4) {
                    throw $this->createAccessDeniedException('Maximum scale is 4.');
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
                return $this->redirect($this->getParameter('blueline.image_endpoint').'?url='.urlencode($this->generateUrl('Blueline_Methods_custom_view', array(
                    'stage'    => $vars['stage'],
                    'notation' => $vars['notation']
                ), UrlGeneratorInterface::ABSOLUTE_URL)).'&scale='.$request->query->get('scale').'&style='.$request->query->get('style'), 302);
            default:
                return $this->render('Methods/view.'.$format.'.twig', compact('method', 'custom', 'similarMethods'));
        }
    }

    /**
    * @Cache(maxage="604800", public=true, lastModified="database_update")
    */
    public function sitemap($page, EntityManagerInterface $em)
    {
        $methods = $em->createQuery('SELECT partial m.{title,url} FROM Blueline\Entity\Method m ORDER BY m.url')
                    ->setMaxResults(12500)
                    ->setFirstResult(($page-1)*12500)
                    ->getArrayResult();
        return $this->render('Methods/sitemap.xml.twig', compact('methods'));
    }
}
