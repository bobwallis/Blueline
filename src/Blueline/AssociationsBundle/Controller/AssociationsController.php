<?php
namespace Blueline\AssociationsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Blueline\BluelineBundle\Helpers\Search;
use Blueline\BluelineBundle\Helpers\Text;

class AssociationsController extends Controller
{

    public function welcomeAction()
    {
        $request = $this->getRequest();
        $format = $request->getRequestFormat();

        // Create basic response object
        $response = new Response();
        if ( $this->container->getParameter( 'kernel.environment') == 'prod' ) {
            $response->setMaxAge( 129600 );
            $response->setPublic();
        }
        $response->setLastModified( new \DateTime( '@'.$this->container->getParameter('asset_update') ) );
        if ( $response->isNotModified( $request ) ) { return $response; }

        return $this->render( 'BluelineAssociationsBundle::welcome.'.$format.'.twig', array(), $response );
    }

    public function searchAction( $searchVariables = array() )
    {
        $request = $this->getRequest();
        $format = $request->getRequestFormat();

        // Create basic response object
        $response = new Response();
        if ( $this->container->getParameter( 'kernel.environment') == 'prod' ) {
            $response->setMaxAge( 129600 );
            $response->setPublic();
        }
        $response->setLastModified( new \DateTime( '@'.$this->container->getParameter('asset_update') ) );
        if ( $response->isNotModified( $request ) ) { return $response; }

        $associationsRepository = $this->getDoctrine()->getManager()->getRepository( 'BluelineAssociationsBundle:Association' );
        $searchVariables = empty( $searchVariables )? Search::requestToSearchVariables( $request, array( 'id', 'name' ) ) : $searchVariables;

        $associations = $associationsRepository->findBySearchVariables( $searchVariables );
        $count = (count( $associations ) > 0)? $associationsRepository->findCountBySearchVariables( $searchVariables ) : 0;
        
        $pageActive = max( 1, ceil( ($searchVariables['offset']+1)/$searchVariables['count'] ) );
        $pageCount =  max( 1, ceil( $count / $searchVariables['count'] ) );

        return $this->render( 'BluelineAssociationsBundle::search.'.$format.'.twig', compact( 'searchVariables', 'count', 'pageActive', 'pageCount', 'associations' ), $response );
    }

    public function viewAction($id)
    {
        $request = $this->getRequest();
        $format = $request->getRequestFormat();

        // Create basic response object
        $response = new Response();
        if ( $this->container->getParameter( 'kernel.environment') == 'prod' ) {
            $response->setMaxAge( 129600 );
            $response->setPublic();
        }
        $response->setLastModified( new \DateTime( '@'.$this->container->getParameter('asset_update') ) );
        if ( $response->isNotModified( $request ) ) { return $response; }

        $ids = explode( '|', $id );

        $em = $this->getDoctrine()->getManager();

        // Check we are at the canonical URL for the content
        $associations = $em->createQuery( '
            SELECT partial a.{id,id,name} FROM BluelineAssociationsBundle:Association a
            WHERE a.id IN (:ids)' )
            ->setParameter( 'ids', $ids )
            ->setMaxResults( count( $ids ) )
            ->getArrayResult();
        if ( empty( $associations ) || count( $associations ) < count( $ids ) ) {
            throw $this->createNotFoundException( 'The association does not exist' );
        }
        $url = $this->generateUrl( 'Blueline_Associations_view', array( 'chromeless' => (($format == 'html')? intval( $request->query->get( 'chromeless' ) )?:null : null), 'id' => implode( '|', array_map( function ($a) { return $a['id']; }, $associations ) ), '_format' => $format ) );

        if ( $request->getRequestUri() !== $url && $request->getRequestUri() !== urldecode( $url ) ) {
            return $this->redirect( $url, 301 );
        }

        $pageTitle = Text::toList( array_map( function ($a) { return $a['name']; }, $associations ) );
        $associations = array();

        foreach ($ids as $id) {
            // Get information about the association and its towers
            $associations[] = $em->createQuery( '
                SELECT a, partial t.{id,place,dedication} FROM BluelineAssociationsBundle:Association a
                LEFT JOIN a.towers t
                WHERE a.id = :id' )
            ->setParameter( 'id', $id )
            ->getSingleResult();
        }

        // Get the bounding box for the tower map if needed
        $bbox = array();
        if ($format == 'html') {
            $bbox = $em->createQuery( '
                SELECT MAX(t.latitude) as lat_max, MIN(t.latitude) as lat_min, MAX(t.longitude) as long_max, MIN(t.longitude) as long_min FROM BluelineAssociationsBundle:Association a
                JOIN a.towers t
                WHERE a.id IN (:ids)' )
            ->setParameter( 'ids', $ids )
            ->getOneOrNullResult();
        }

        // Create response
        return $this->render( 'BluelineAssociationsBundle::view.'.$format.'.twig', compact( 'pageTitle', 'associations', 'bbox' ), $response );
    }

    public function sitemapAction()
    {
        $request = $this->getRequest();
        $format = $request->getRequestFormat();

        // Create basic response object
        $response = new Response();
        if ( $this->container->getParameter( 'kernel.environment') == 'prod' ) {
            $response->setMaxAge( 129600 );
            $response->setPublic();
        }
        $response->setLastModified( new \DateTime( '@'.$this->container->getParameter('asset_update') ) );
        if ( $response->isNotModified( $request ) ) { return $response; }

        $associations = $this->getDoctrine()->getManager()->createQuery( 'SELECT a.id FROM BluelineAssociationsBundle:Association a' )->getArrayResult();

        return $this->render( 'BluelineAssociationsBundle::sitemap.'.$format.'.twig', compact( 'associations' ), $response );
    }
}
