<?php
namespace Blueline\BluelineBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class AssetController extends Controller
{
    private $cacheTime = 604800;

    public function fontAction($font)
    {
        $format   = $this->getRequest()->getRequestFormat();
        $fontPath = __DIR__.'/../Resources/public/fonts/'.$font.'.'.$format;
        if ( !file_exists( $fontPath ) ) {
            throw $this->createNotFoundException( 'The font does not exist' );
        }

        $response = new Response();
        if ( $this->container->getParameter( 'kernel.environment' ) == 'prod' ) {
            $response->setMaxAge( $this->cacheTime );
            $response->setSharedMaxAge( $this->cacheTime );
            $response->setPublic();
        }
        $response->setContent( file_get_contents( $fontPath ) );

        return $response;
    }

    public function faviconAction()
    {
        $request = $this->getRequest();
        $format  = $request->getRequestFormat();
        $size =    intval( $request->get( 'size' )?:32 );

        $response = new Response();
        if ( $this->container->getParameter( 'kernel.environment' ) == 'prod' ) {
            $response->setMaxAge( $this->cacheTime );
            $response->setSharedMaxAge( $this->cacheTime );
            $response->setPublic();
        }

        // Create the image
        switch ($format) {
            case 'svg':
                $response->setContent( file_get_contents( __DIR__.'/../Resources/public/images/favicon.svg' ) );
                break;
            case 'png':
            case 'gif':
            case 'jpg':
            case 'bmp':
                $image = new \Imagick();
                $image->setResolution( $size*1.2, $size*1.2 );
                $image->setBackgroundColor( ($format == 'jpg' || $format == 'bmp')? new \ImagickPixel( 'white' ) : new \ImagickPixel( 'transparent' ) );;
                $image->readImage( __DIR__.'/../Resources/public/images/favicon.svg' );
                $image->scaleImage($size, $size);
                switch ($format) {
                    case 'png':
                        $image->setImageFormat( 'png32' );
                        break;
                    case 'gif':
                        $image->setImageFormat( 'gif' );
                        break;
                    case 'jpg':
                        $image->setImageFormat( 'jpeg' );
                        $image->setCompression( \Imagick::COMPRESSION_JPEG );
                        $image->setImageCompressionQuality( 90 );
                        $image->setImageFormat( 'jpeg' );
                        break;
                    case 'bmp':
                        $image->setImageFormat( 'bmp' );
                        break;
                }
                $image->stripImage();
                $response->setContent( $image );
                $image->destroy();
                break;
            case 'ico':
                $response->setContent( file_get_contents( __DIR__.'/../Resources/public/images/favicon.ico' ) );
                break;
        }

        return $response;
    }

    public function iOS_iconAction($size)
    {
        $size = intval( $size );
        $image = new \Imagick();
        $image->setBackgroundColor( new \ImagickPixel( '#EFE5BD' ) );
        $image->setResolution( $size*1.2, $size*1.2 );
        $image->readImage( __DIR__.'/../Resources/public/images/favicon.svg' );
        $image->scaleImage($size, $size);
        $image->setImageFormat( 'png32' );
        $image->stripImage();

        // Create response
        $response = new Response();
        if ( $this->container->getParameter( 'kernel.environment') == 'prod' ) {
            $response->setMaxAge( $this->cacheTime );
            $response->setSharedMaxAge( $this->cacheTime );
            $response->setPublic();
        }
        $response->setContent( $image );
        $image->destroy();

        return $response;
    }

    public function imageAction($image)
    {
        $format  = $this->getRequest()->getRequestFormat();
        $file = false;

        switch ($image) {
            case 'external':
            case 'search':
            case 'select':
            case 'more':
            case 'welcome_associations':
            case 'welcome_methods':
            case 'welcome_towers':
                $file = __DIR__.'/../Resources/public/images/'.$image.'.svg';
                break;
            case 'loading':
                $file = ($format == 'gif')? __DIR__.'/../Resources/public/images/loading.gif' : false;
                break;
        }

        if ($file) {
            return $this->createImageResponse( $file, $format );
        } else {
            throw $this->createNotFoundException( 'Image not found' );
        }
    }

    private function createImageResponse($imagePath, $format, $width = null, $height = null)
    {
        // Create response
        $response = new Response();

        // Set cache headers
        if ( $this->container->getParameter( 'kernel.environment') == 'prod' ) {
            $response->setMaxAge( $this->cacheTime );
            $response->setSharedMaxAge( $this->cacheTime );
            $response->setPublic();
        }

        // Check we actually need to convert
        $pathInfo = pathinfo( $imagePath );
        if ($pathInfo['extension'] == $format) {
            $response->setContent( file_get_contents( $imagePath ) );

            return $response;
        }

        // Output the image
        $image = new \Imagick();
        if ( is_int( $width ) && is_int( $height ) ) {
            $image->setResolution($width, $height);
        }
        $image->setBackgroundColor( ($format == 'jpg' || $format == 'bmp')? new \ImagickPixel( 'white' ) : new \ImagickPixel( 'transparent' ) );
        $image->readImage( $imagePath );
        if ( is_int( $width ) && is_int( $height ) ) {
            $image->scaleImage($width, $height);
        }
        $image->stripImage();
        switch ($format) {
            case 'svg':
                $image->setImageFormat( 'svg' );
                $response->setContent( $image );
                break;
            case 'png':
                $image->setImageFormat( 'png32' );
                $response->setContent( $image );
                break;
            case 'gif':
                $image->setImageFormat( 'gif' );
                $response->setContent( $image );
                break;
            case 'jpg':
                $image->setImageFormat( 'jpeg' );
                $image->setCompression( \Imagick::COMPRESSION_JPEG );
                $image->setImageCompressionQuality( 90 );
                $image->setImageFormat( 'jpeg' );
                $response->setContent( $image );
                break;
            case 'bmp':
                $image->setImageFormat( 'bmp' );
                $response->setContent( $image );
                break;
        }
        $image->destroy();

        return $response;
    }
}
