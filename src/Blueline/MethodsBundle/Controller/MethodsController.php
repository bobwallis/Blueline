<?php
namespace Blueline\MethodsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Blueline\BluelineBundle\Helpers\Search;
use Blueline\BluelineBundle\Helpers\Text;
use Blueline\MethodsBundle\Helpers\Stages;
use Blueline\MethodsBundle\Helpers\Classifications;

class MethodsController extends Controller
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

        return $this->render( 'BluelineMethodsBundle::welcome.'.$format.'.twig', array(), $response );
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

        $methodRepository = $this->getDoctrine()->getManager()->getRepository( 'BluelineMethodsBundle:Method' );
        $searchVariables = empty( $searchVariables )? Search::requestToSearchVariables( $request, array( 'title', 'stage', 'classification', 'notation', 'leadHeadCode', 'leadHead', 'fchGroups', 'rwRef', 'bnRef', 'tdmmRef', 'pmmRef', 'lengthOfLead', 'numberOfHunts', 'little', 'differential', 'plain', 'trebleDodging', 'palindromic', 'doubleSym', 'rotational' ) ) : $searchVariables;

        $methods = $methodRepository->findBySearchVariables( $searchVariables );
        $count = (count( $methods ) > 0)? $methodRepository->findCountBySearchVariables( $searchVariables ) : 0;

        $pageActive = max( 1, ceil( ($searchVariables['offset']+1)/$searchVariables['count'] ) );
        $pageCount =  max( 1, ceil( $count / $searchVariables['count'] ) );
        
        return $this->render( 'BluelineMethodsBundle::search.'.$format.'.twig', compact( 'searchVariables', 'count', 'pageActive', 'pageCount', 'methods' ), $response );
    }

    public function viewAction($title)
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

        // Decode and canonicalise the requested URLs
        $urls = array_map( function($u) {
            // Decode
            $u = urldecode( $u );
            // Replace S with Surprise, etc...
            $classificationsInitials = array_map( function( $c ) {
                return implode( '', array_map( function( $w ) { return $w[0]; }, explode( ' ', $c ) ) );
            }, Classifications::toArray() );
            $matches = array();
            if( preg_match( '/('.implode( '|', $classificationsInitials ).')_('.implode( '|', Stages::toArray() ).')$/', $u, $matches ) ) {
                $initial = $matches[1];
                $classification = str_replace( ' ', '_', Classifications::toArray()[array_search( $initial, $classificationsInitials )] );
                $u = preg_replace( '/'.$initial.'_('.implode( '|', Stages::toArray() ).')$/', $classification.'_$1', $u );
            }
            return $u;
        }, explode( '|', $title ) );
        // Convert URLs into titles
        $titles = array_map( function ($m) { return str_replace( '_', ' ', $m ); }, $urls );

        // Create lower case arrays for use in search
        $urlsLower = array_map( "strtolower", $urls );
        $titlesLower = array_map( "strtolower", $titles );

        $methodRepository = $this->getDoctrine()->getManager()->getRepository( 'BluelineMethodsBundle:Method' );
        $em = $this->getDoctrine()->getManager();

        // Check we are at the canonical URL for the content
        $methodsCheck = $em->createQuery( '
            SELECT partial m.{title,url} FROM BluelineMethodsBundle:Method m
            WHERE LOWER(m.title) IN (:titles) OR LOWER(m.url) IN (:urls)' )
            ->setParameter( 'urls', $urlsLower )
            ->setParameter( 'titles', $titlesLower )
            ->setMaxResults( count( $titlesLower ) )
            ->getArrayResult();
        if ( empty( $methodsCheck ) || count( $methodsCheck ) < count( $methodsCheck ) ) {
            throw $this->createNotFoundException( 'The method does not exist' );
        }
        $url = $this->generateUrl( 'Blueline_Methods_view', array( 'chromeless' => (($format == 'html')? intval( $request->query->get( 'chromeless' ) )?:null : null), 'title' => implode( '|', array_map( function ($m) { return $m['url']; }, $methodsCheck ) ), '_format' => $format ) );
        if ( $request->getRequestUri() !== urldecode( $url ) ) {
            return $this->redirect( $url, 301 );
        }

        $pageTitle = Text::toList( array_map( function ($m) { return $m['title']; }, $methodsCheck ) );
        $methods = array();

        foreach ($methodsCheck  as $methodTitle) {
            // Get information about the method
            $method = $em->createQuery( '
                SELECT m FROM BluelineMethodsBundle:Method m
                LEFT JOIN m.performances p
                LEFT JOIN m.collections c
                WHERE m.title = :title' )
            ->setParameter( 'title', $methodTitle['title'] )
            ->getSingleResult();
            $methods[] = $method;
        }

        // Create response
        return $this->render( 'BluelineMethodsBundle::view.'.$format.'.twig', compact( 'pageTitle', 'methods' ), $response );
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

        $methods = $this->getDoctrine()->getManager()->createQuery( 'SELECT partial m.{title,url} FROM BluelineMethodsBundle:Method m' )->getArrayResult();

        return $this->render( 'BluelineMethodsBundle::sitemap.'.$format.'.twig', compact( 'methods' ), $response );
    }
}
