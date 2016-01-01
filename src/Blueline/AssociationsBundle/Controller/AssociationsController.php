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
        $em = $this->getDoctrine()->getManager();
        $associationsRepository = $em->getRepository('BluelineAssociationsBundle:Association');
        $associationsMetadata   = $em->getClassMetadata('BluelineAssociationsBundle:Association');

        // Parse search variables
        $searchVariables = Search::requestToSearchVariables($request, array_values($associationsMetadata->fieldNames));
        $searchVariables['fields'] = array_values(array_unique(empty($searchVariables['fields'])? array('id', 'name') : array_merge($searchVariables['fields'], ($request->getRequestFormat()=='html')?array('id', 'name'):array())));
        $searchVariables['sort']   = empty($searchVariables['sort'])? 'id' : $searchVariables['sort'];

        // Create query
        $query = Search::searchVariablesToBasicQuery($searchVariables, $associationsRepository, $associationsMetadata);
        if (isset($searchVariables['q'])) {
            if (strpos($searchVariables['q'], '/') === 0 && strlen($searchVariables['q']) > 1) {
                if (@preg_match($searchVariables['q'].'/', ' ') === false) {
                    throw new BadRequestHttpException('Invalid regular expression');
                }
                $query->andWhere('REGEXP(e.name, :qRegexp) = TRUE')
                    ->setParameter('qRegexp', trim($searchVariables['q'], '/'));
            } else {
                $query->andWhere('LOWER(e.name) LIKE :qLike OR LOWER(e.id) LIKE :qLike')
                    ->setParameter('qLike', Search::prepareStringForLike($searchVariables['q']));
            }
        }

        // Execute
        $associations = $query->getQuery()->getResult();
        $count = (count($associations) < $searchVariables['count'])? count($associations) : Search::queryToCountQuery($query, $associationsMetadata)->getQuery()->getSingleScalarResult();
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
