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
        $associationsRepository = $this->getDoctrine()->getManager()->getRepository('BluelineAssociationsBundle:Association');

        // Get association information
        $association = $associationsRepository->findOneByIdJoiningBasicTowerInformation($id);
        if (!$association) {
            throw $this->createNotFoundException('The association does not exist');
        }
        // Get the bounding box for the tower map, and details of any enclaved associations
        $enclaved = $associationsRepository->findContainedAssociations($id);
        $bbox = $associationsRepository->findBoundingBox($id);

        // Create response
        return $this->render('BluelineAssociationsBundle::view.'.$request->getRequestFormat().'.twig', compact('association', 'enclaved', 'bbox'));
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
