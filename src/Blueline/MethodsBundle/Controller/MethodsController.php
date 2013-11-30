<?php
namespace Blueline\MethodsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Blueline\BluelineBundle\Helpers\Search;
use Blueline\BluelineBundle\Helpers\Text;

class MethodsController extends Controller
{

    public function welcomeAction()
    {
        $request = $this->getRequest();
        $format = $request->getRequestFormat();
        $response = $this->render( 'BluelineMethodsBundle::welcome.'.$format.'.twig' );

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

        $methodRepository = $this->getDoctrine()->getManager()->getRepository( 'BluelineMethodsBundle:Method' );
        $searchVariables = empty( $searchVariables )? Search::requestToSearchVariables( $request, array( 'title', 'stage', 'classification', 'notation', 'leadHeadCode', 'leadHead', 'fchGroups', 'rwRef', 'bnRef', 'tdmmRef', 'pmmRef', 'lengthOfLead', 'numberOfHunts', 'little', 'differential', 'plain', 'trebleDodging', 'palindromic', 'doubleSym', 'rotational', 'firstTowerbellPeal_date', 'firstTowerbellPeal_location', 'firstHandbellPeal_date', 'firstHandbellPeal_location' ) ) : $searchVariables;

        $methods = $methodRepository->findBySearchVariables( $searchVariables );
        $count = (count( $methods ) > 0)? $methodRepository->findCountBySearchVariables( $searchVariables ) : 0;

        $pageActive = max( 1, ceil( ($searchVariables['offset']+1)/$searchVariables['count'] ) );
        $pageCount =  max( 1, ceil( $count / $searchVariables['count'] ) );
        $response = $this->render( 'BluelineMethodsBundle::search.'.$format.'.twig', compact( 'searchVariables', 'count', 'pageActive', 'pageCount', 'methods' ) );

        // Caching headers
        if ( $this->container->getParameter( 'kernel.environment') == 'prod' ) {
            $response->setMaxAge( 129600 );
            $response->setSharedMaxAge( 129600 );
            $response->setPublic();
        }

        return $response;
    }

    public function viewAction( $title )
    {
        $request = $this->getRequest();
        $format = $request->getRequestFormat();
        $chromeless = 0;
        if ($format == 'html') {
            $chromeless = intval( $request->query->get( 'chromeless' ) );
        }

        $titlesUnderscore = explode( '|', $title );
        $titles = array_map( function( $t ) { return str_replace( '_', ' ', $t ); }, $titlesUnderscore );

        $methodRepository = $this->getDoctrine()->getManager()->getRepository( 'BluelineMethodsBundle:Method' );
        $em = $this->getDoctrine()->getManager();

        // Check we are at the canonical URL for the content
        $methods = $em->createQuery( '
            SELECT partial m.{title} FROM BluelineMethodsBundle:Method m
            WHERE m.title IN (:titles)' )
            ->setParameter( 'titles', $titles )
            ->setMaxResults( count( $titles ) )
            ->getArrayResult();
        if ( empty( $methods ) || count( $methods ) < count( $methods ) ) {
            throw $this->createNotFoundException( 'The method does not exist' );
        }
        $url = $this->generateUrl( 'Blueline_Methods_view', array( 'chromeless' => ($chromeless?:null), 'title' => implode( '|', array_map( function( $m ) { return str_replace( ' ', '_', $m['title'] ); }, $methods ) ), '_format' => $format ) );
        if ( $request->getRequestUri() !== urldecode( $url ) ) {
            return $this->redirect( $url, 301 );
        }

        $pageTitle = Text::toList( array_map( function( $m ) { return $m['title']; }, $methods ) );
        $methods = array();

        foreach ($titles  as $title) {
            // Get information about the method
            $method = $em->createQuery( '
                SELECT m FROM BluelineMethodsBundle:Method m
                WHERE m.title = :title' )
            ->setParameter( 'title', $title )
            ->getSingleResult();

            $methods[] = $method;
        }

        // Create response
        $response = $this->render( 'BluelineMethodsBundle::view.'.$format.'.twig', compact( 'pageTitle', 'methods' ) );

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

        $methods = $this->getDoctrine()->getManager()->createQuery( 'SELECT partial m.{title} FROM BluelineMethodsBundle:Method m' )->getArrayResult();

        $response = $this->render( 'BluelineMethodsBundle::sitemap.'.$format.'.twig', compact( 'methods' ) );

        // Caching headers
        if ( $this->container->getParameter( 'kernel.environment') == 'prod' ) {
            $response->setPublic();
            $response->setMaxAge( 129600 );
            $response->setSharedMaxAge( 129600 );
        }

        return $response;
    }
}