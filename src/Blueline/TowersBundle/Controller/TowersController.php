<?php
namespace Blueline\TowersBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Blueline\BluelineBundle\Helpers\Search;
use Blueline\BluelineBundle\Helpers\Text;

class TowersController extends Controller
{
    /**
    * @Cache(maxage="129600", public=true, lastModified="asset_update")
    */
    public function welcomeAction(Request $request)
    {
        return $response = $this->render('BluelineTowersBundle::welcome.'.$request->getRequestFormat().'.twig');
    }

    /**
    * @Cache(maxage="129600", public=true, lastModified="database_update")
    */
    public function searchAction($searchVariables = array(), Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $towerRepository = $em->getRepository('BluelineTowersBundle:Tower');
        $towerMetadata   = $em->getClassMetadata('BluelineTowersBundle:Tower');

        // Parse search variables
        $searchVariables = Search::requestToSearchVariables($request, array_values($towerMetadata->fieldNames));
        $searchVariables['fields'] = implode(',', array_values(array_unique(empty($searchVariables['fields'])? array('id', 'place', 'dedication', 'county', 'country') : array_merge($searchVariables['fields'], ($request->getRequestFormat()=='html')?array('id', 'place', 'dedication', 'county', 'country'):array()))));
        $searchVariables['sort']   = empty($searchVariables['sort'])? 'id' : $searchVariables['sort'];

        // Create query
        $query = Search::searchVariablesToBasicQuery($searchVariables, $towerRepository, $towerMetadata);
        if (isset($searchVariables['q'])) {
            if (strpos($searchVariables['q'], ' ') !== false) {
                $query->andWhere("CONCAT_WS(' ', LOWER(e.dedication), LOWER(e.place) ,LOWER(e.dedication)) LIKE :qLike");
            } else {
                $query->andWhere('LOWER(e.place) LIKE :qLike');
            }
            $query->setParameter('qLike', Search::prepareStringForLike($searchVariables['q']));
        }

        // Execute
        $towers = $query->getQuery()->getResult();
        $count = (count($towers)+$searchVariables['offset'] < $searchVariables['count'])? count($towers) : Search::queryToCountQuery($query, $towerMetadata)->getQuery()->getSingleScalarResult();
        $pageActive = max(1, ceil(($searchVariables['offset']+1)/$searchVariables['count']));
        $pageCount =  max(1, ceil($count / $searchVariables['count']));

        return $this->render('BluelineTowersBundle::search.'.$request->getRequestFormat().'.twig', compact('searchVariables', 'count', 'pageActive', 'pageCount', 'towers'));
    }

    /**
    * @Cache(maxage="129600", public=true, lastModified="database_update")
    */
    public function viewAction($id, Request $request)
    {
        $format = $request->getRequestFormat();

        $towersRepository = $this->getDoctrine()->getManager()->getRepository('BluelineTowersBundle:Tower');
        $em = $this->getDoctrine()->getManager();

        $tower = $towersRepository->findOneByIdJoiningBasicAssociationAndPerformanceInformation($id);

        if (!$tower) {
            // See if the request is an old primary key
            $oldpk = $em->createQuery('
                SELECT partial t.{id} FROM BluelineTowersBundle:Tower t
                LEFT JOIN t.oldpks t2
                WHERE t2.oldpk IN (:id)')
                ->setParameter('id', $id)
                ->getArrayResult();
            if (!$oldpk) {
                throw $this->createNotFoundException('The tower does not exist');
            } else {
                $url = $this->generateUrl('Blueline_Towers_view', array( 'chromeless' => (($format == 'html') ? intval($request->query->get('chromeless')) ?: null : null), 'id' => $oldpk[0]['id'], '_format' => $format ));
                return $this->redirect($url, 301);
            }
        }

        // Get nearby towers
        $nearbyTowers = array_filter($towersRepository->findNearbyTowers($tower->getLatitude(), $tower->getLongitude(), 7), function ($e) use ($id) {
            return strcmp($id, $e['id']) != 0;
        });

        // Create response
        return $this->render('BluelineTowersBundle::view.'.$format.'.twig', compact('tower', 'nearbyTowers'));
    }

    /**
    * @Cache(maxage="604800", public=true, lastModified="database_update")
    */
    public function sitemapAction()
    {
        $towers = $this->getDoctrine()->getManager()->createQuery('SELECT partial t.{id} FROM BluelineTowersBundle:Tower t')->getArrayResult();
        return $this->render('BluelineTowersBundle::sitemap.xml.twig', compact('towers'));
    }
}
