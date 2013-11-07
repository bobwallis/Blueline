<?php
namespace Blueline\AssociationsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Blueline\BluelineBundle\Helpers\Search;
use Blueline\BluelineBundle\Helpers\Text;

class AssociationsController extends Controller
{

    public function welcomeAction()
    {
        $request = $this->getRequest();
        $format = $request->getRequestFormat();

        $associations = $this->getDoctrine()->getManager()->getRepository( 'BluelineAssociationsBundle:Association')->findAll();

        $response = $this->render( 'BluelineAssociationsBundle::welcome.'.$format.'.twig', compact( 'associations' ) );

        if ( $this->container->getParameter( 'kernel.environment') == 'prod' ) {
            $response->setMaxAge( 129600 );
            $response->setSharedMaxAge( 129600 );
            $response->setPublic();
        }

        return $response;
    }

    public function searchAction( $searchVariables = array() )
    {
        $request = $this->getRequest();
        $format = $request->getRequestFormat();

        $associationsRepository = $this->getDoctrine()->getManager()->getRepository( 'BluelineAssociationsBundle:Association' );
        $searchVariables = empty( $searchVariables )? Search::requestToSearchVariables( $request, array( 'abbreviation', 'name' ) ) : $searchVariables;

        $associations = $associationsRepository->findBySearchVariables( $searchVariables );
        $count = (count( $associations ) > 0)? $associationsRepository->findCountBySearchVariables( $searchVariables ) : 0;

        $pageActive = max( 1, ceil( ($searchVariables['offset']+1)/$searchVariables['count'] ) );
        $pageCount =  max( 1, ceil( $count / $searchVariables['count'] ) );
        $response = $this->render( 'BluelineAssociationsBundle::search.'.$format.'.twig', compact( 'searchVariables', 'count', 'pageActive', 'pageCount', 'associations' ) );

        // Caching headers
        if ( $this->container->getParameter( 'kernel.environment') == 'prod' ) {
            $response->setMaxAge( 129600 );
            $response->setSharedMaxAge( 129600 );
            $response->setPublic();
        }

        return $response;
    }

    public function viewAction( $abbreviation )
    {
        $request = $this->getRequest();
        $format = $request->getRequestFormat();
        $chromeless = 0;
        if ($format == 'html') {
            $chromeless = intval( $request->query->get( 'chromeless' ) );
        }

        $abbreviations = explode( '|', $abbreviation );

        $em = $this->getDoctrine()->getManager();

        // Check we are at the canonical URL for the content
        $associations = $em->createQuery( '
            SELECT partial a.{id,abbreviation,name} FROM BluelineAssociationsBundle:Association a
            WHERE a.abbreviation IN (:abbreviations)' )
            ->setParameter( 'abbreviations', $abbreviations )
            ->setMaxResults( count( $abbreviations ) )
            ->getArrayResult();
        if ( empty( $associations ) || count( $associations ) < count( $abbreviations ) ) {
            throw $this->createNotFoundException( 'The association does not exist' );
        }
        $url = $this->generateUrl( 'Blueline_Associations_view', array( 'chromeless' => ($chromeless?:null), 'abbreviation' => implode( '|', array_map( function( $a ) { return $a['abbreviation']; }, $associations ) ), '_format' => $format ) );

        if ( $request->getRequestUri() !== $url && $request->getRequestUri() !== urldecode( $url ) ) {
            return $this->redirect( $url, 301 );
        }

        $pageTitle = Text::toList( array_map( function( $a ) { return $a['name']; }, $associations ) );
        $associations = array();

        foreach ($abbreviations as $abbreviation) {
            // Get information about the association and its towers
            $associations[] = $em->createQuery( '
                SELECT a, partial t.{id,place,dedication} FROM BluelineAssociationsBundle:Association a
                LEFT JOIN a.towers t
                WHERE a.abbreviation = :abbreviation' )
            ->setParameter( 'abbreviation', $abbreviation )
            ->getSingleResult();
        }

        // Get the bounding box for the tower map if needed
        $bbox = array();
        if ($format == 'html') {
            $bbox = $em->createQuery( '
                SELECT MAX(t.latitude) as lat_max, MIN(t.latitude) as lat_min, MAX(t.longitude) as long_max, MIN(t.longitude) as long_min FROM BluelineAssociationsBundle:Association a
                JOIN a.towers t
                WHERE a.abbreviation IN (:abbreviations)' )
            ->setParameter( 'abbreviations', $abbreviations )
            ->getOneOrNullResult();
        }

        // Create response
        $response = $this->render( 'BluelineAssociationsBundle::view.'.$format.'.twig', compact( 'pageTitle', 'associations', 'bbox' ) );

        // Caching headers
        if ( $this->container->getParameter( 'kernel.environment') == 'prod' ) {
            $response->setMaxAge( 129600 );
            $response->setSharedMaxAge( 129600 );
            $response->setPublic();
        }

        return $response;
    }

    public function sitemapAction()
    {
        $request = $this->getRequest();
        $format = $request->getRequestFormat();

        $associations = $this->getDoctrine()->getManager()->createQuery( 'SELECT partial a.{id,abbreviation} FROM BluelineAssociationsBundle:Association a' )->getArrayResult();

        $response = $this->render( 'BluelineAssociationsBundle::sitemap.'.$format.'.twig', compact( 'associations' ) );

        // Caching headers
        if ( $this->container->getParameter( 'kernel.environment') == 'prod' ) {
            $response->setPublic();
            $response->setMaxAge( 129600 );
            $response->setSharedMaxAge( 129600 );
        }

        return $response;
    }
}
