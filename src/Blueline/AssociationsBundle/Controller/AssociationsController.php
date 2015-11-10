<?php
namespace Blueline\AssociationsBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Blueline\BluelineBundle\Helpers\Search;
use Blueline\BluelineBundle\Helpers\Text;

class AssociationsController extends Controller
{
    /**
    * @Cache(maxage="129600", public=true, lastModified="asset_update")
    */
    public function welcomeAction(Request $request)
    {
        return $this->render('BluelineAssociationsBundle::welcome.'.$request->getRequestFormat().'.twig');
    }

    /**
    * @Cache(maxage="129600", public=true, lastModified="database_update")
    */
    public function searchAction($searchVariables = array(), Request $request)
    {
        $associationsRepository = $this->getDoctrine()->getManager()->getRepository('BluelineAssociationsBundle:Association');
        $searchVariables = empty($searchVariables) ? Search::requestToSearchVariables($request, array( 'id', 'name' )) : $searchVariables;

        $associations = $associationsRepository->findBySearchVariables($searchVariables);
        $count = (count($associations) > 0) ? $associationsRepository->findCountBySearchVariables($searchVariables) : 0;

        $pageActive = max(1, ceil(($searchVariables['offset']+1)/$searchVariables['count']));
        $pageCount =  max(1, ceil($count / $searchVariables['count']));

        return $this->render('BluelineAssociationsBundle::search.'.$request->getRequestFormat().'.twig', compact('searchVariables', 'count', 'pageActive', 'pageCount', 'associations'));
    }

    /**
    * @Cache(maxage="129600", public=true, lastModified="database_update")
    */
    public function viewAction($id, Request $request)
    {
        $format = $request->getRequestFormat();

        $ids = explode('|', $id);

        $associationsRepository = $this->getDoctrine()->getManager()->getRepository('BluelineAssociationsBundle:Association');
        $em = $this->getDoctrine()->getManager();

        // Check we are at the canonical URL for the content
        $associations = $em->createQuery('
            SELECT partial a.{id,id,name} FROM BluelineAssociationsBundle:Association a
            WHERE a.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->setMaxResults(count($ids))
            ->getArrayResult();
        if (empty($associations) || count($associations) < count($ids)) {
            throw $this->createNotFoundException('The association does not exist');
        }
        $url = $this->generateUrl('Blueline_Associations_view', array( 'chromeless' => (($format == 'html') ? intval($request->query->get('chromeless')) ?: null : null), 'id' => implode('|', array_map(function ($a) { return $a['id']; }, $associations)), '_format' => $format ));

        if ($request->getRequestUri() !== $url && $request->getRequestUri() !== urldecode($url)) {
            return $this->redirect($url, 301);
        }

        $pageTitle = Text::toList(array_map(function ($a) { return $a['name']; }, $associations));
        $associations = array();
        $associationsContains = array();

        foreach ($ids as $id) {
            // Get information about the association and its towers
            $associations[] = $em->createQuery('
                SELECT a, partial t.{id,place,dedication} FROM BluelineAssociationsBundle:Association a
                LEFT JOIN a.towers t
                WHERE a.id = :id')
            ->setParameter('id', $id)
            ->getSingleResult();
            $associationsContains[] = $associationsRepository->findContainedAssociations($id);
        }

        // Get the bounding box for the tower map
        $bbox = array();
        if ($format == 'html') {
            $bbox = $em->createQuery('
                SELECT MAX(t.latitude) as lat_max, MIN(t.latitude) as lat_min, MAX(t.longitude) as long_max, MIN(t.longitude) as long_min FROM BluelineAssociationsBundle:Association a
                JOIN a.towers t
                WHERE a.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getOneOrNullResult();
        }

        // Create response
        return $this->render('BluelineAssociationsBundle::view.'.$format.'.twig', compact('pageTitle', 'associations', 'associationsContains', 'bbox'));
    }

    /**
    * @Cache(maxage="604800", public=true, lastModified="database_update")
    */
    public function sitemapAction()
    {
        $associations = $this->getDoctrine()->getManager()->createQuery('SELECT a.id FROM BluelineAssociationsBundle:Association a')->getArrayResult();
        return $this->render('BluelineAssociationsBundle::sitemap.xml.twig', compact('associations'));
    }
}
