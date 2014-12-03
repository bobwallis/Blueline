<?php
namespace Blueline\TowersBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Blueline\BluelineBundle\Helpers\Search;
use Blueline\BluelineBundle\Helpers\Text;

class TowersController extends Controller
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

        return $response = $this->render('BluelineTowersBundle::welcome.'.$format.'.twig', array(), $response);
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

        $towerRepository = $this->getDoctrine()->getManager()->getRepository('BluelineTowersBundle:Tower');
        $searchVariables = empty($searchVariables) ? Search::requestToSearchVariables($request, array( 'id', 'gridReference', 'postcode', 'country', 'county', 'diocese', 'place', 'dedication', 'note', 'contractor' )) : $searchVariables;

        $towers = $towerRepository->findBySearchVariables($searchVariables);
        $count = (count($towers) > 0) ? $towerRepository->findCountBySearchVariables($searchVariables) : 0;

        $pageActive = max(1, ceil(($searchVariables['offset']+1)/$searchVariables['count']));
        $pageCount =  max(1, ceil($count / $searchVariables['count']));

        return $this->render('BluelineTowersBundle::search.'.$format.'.twig', compact('searchVariables', 'count', 'pageActive', 'pageCount', 'towers'), $response);
    }

    public function viewAction($id)
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

        $ids = array_map('strtoupper', explode('|', $id));

        $towersRepository = $this->getDoctrine()->getManager()->getRepository('BluelineTowersBundle:Tower');
        $em = $this->getDoctrine()->getManager();

        // Check we are at the canonical URL for the content
        $towers = $em->createQuery('
            SELECT partial t.{id,place,dedication} FROM BluelineTowersBundle:Tower t
            LEFT JOIN t.oldpks t2
            WHERE t.id IN (:id) OR t2.oldpk IN (:id)')
            ->setParameter('id', $ids)
            ->setMaxResults(count($ids))
            ->getArrayResult();
        if (empty($towers) || count($towers) < count($ids)) {
            throw $this->createNotFoundException('The tower does not exist');
        }
        $url = $this->generateUrl('Blueline_Towers_view', array( 'chromeless' => (($format == 'html') ? intval($request->query->get('chromeless')) ?: null : null), 'id' => implode('|', array_map(function ($t) { return $t['id']; }, $towers)), '_format' => $format ));

        if ($request->getRequestUri() !== $url) {
            return $this->redirect($url, 301);
        }

        $pageTitle = Text::toList(array_map(function ($t) { return $t['place'].(($t['dedication'] != 'Unknown') ? ' ('.$t['dedication'].')' : ''); }, $towers));
        $towers = array();
        $nearbyTowers = array();

        foreach ($ids  as $id) {
            // Get information about the tower, its affiliations, and first pealed methods
            $tower = $em->createQuery('
                SELECT t, partial a.{id,name}, partial p.{id,type,date}, partial m.{title,url} FROM BluelineTowersBundle:Tower t
                LEFT JOIN t.associations a
                LEFT JOIN t.performances p WITH p.type = :performanceType
                LEFT JOIN p.method       m
                WHERE t.id = :id
                ORDER BY p.date DESC')
            ->setParameter('id', $id)
            ->setParameter('performanceType', 'firstTowerbellPeal')
            ->getSingleResult();

            $nearbyTowers[] = array_slice($towersRepository->findNearbyTowers($tower->getLatitude(), $tower->getLongitude(), 7), 1);
            $towers[] = $tower;
        }

        $bbox = array();
        if (count($towers) > 1 && $format == 'html') {
            $bbox['lat_min'] = min(array_map(function ($t) { return $t->getLatitude(); }, $towers));
            $bbox['long_min'] = min(array_map(function ($t) { return $t->getLongitude(); }, $towers));
            $bbox['lat_max'] = max(array_map(function ($t) { return $t->getLatitude(); }, $towers));
            $bbox['long_max'] = max(array_map(function ($t) { return $t->getLongitude(); }, $towers));
        }

        // Create response
        return $this->render('BluelineTowersBundle::view.'.$format.'.twig', compact('pageTitle', 'towers', 'nearbyTowers', 'bbox'), $response);
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

        $towers = $this->getDoctrine()->getManager()->createQuery('SELECT partial t.{id} FROM BluelineTowersBundle:Tower t')->getArrayResult();

        return $this->render('BluelineTowersBundle::sitemap.'.$format.'.twig', compact('towers'), $response);
    }
}
