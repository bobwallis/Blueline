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
        $towerRepository = $this->getDoctrine()->getManager()->getRepository('BluelineTowersBundle:Tower');
        $searchVariables = empty($searchVariables) ? Search::requestToSearchVariables($request, array( 'id', 'gridReference', 'postcode', 'country', 'county', 'diocese', 'place', 'dedication', 'note', 'contractor' )) : $searchVariables;

        $towers = $towerRepository->findBySearchVariables($searchVariables);
        $count = (count($towers) > 0) ? $towerRepository->findCountBySearchVariables($searchVariables) : 0;

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
