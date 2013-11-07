<?php
namespace Blueline\BluelineBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class AssetController extends Controller
{
    private $cacheTime = 604800;

    public function fontAction( $font )
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
        $format  = $this->getRequest()->getRequestFormat();

        // Output the image
        switch ($format) {
            case 'svg':
            case 'png':
            case 'gif':
            case 'jpg':
            case 'bmp':
                return $this->createImageResponse( __DIR__.'/../Resources/public/images/favicon.svg', $format, 48, 48 );
            case 'ico':
                $response = new Response();
                if ( $this->container->getParameter( 'kernel.environment' ) == 'prod' ) {
                    $response->setMaxAge( $this->cacheTime );
                    $response->setSharedMaxAge( $this->cacheTime );
                    $response->setPublic();
                }
                $response->setContent( passthru( 'convert -background none "'.realpath( __DIR__.'/../Resources/public/images/favicon.svg' ).'" \( -clone 0 -resize 16x16 \) \( -clone 0 -resize 32x32 \) \( -clone 0 -resize 48x48 \) -delete 0 -alpha on -background none -colors 512 ico:-' ) );

                return $response;
        }
    }

    public function iOS_iconAction( $size )
    {
        $size = intval( $size );
        $image = new \Imagick();
        $image->setBackgroundColor( new \ImagickPixel( '#EFE5BD' ) );
        $image->setresolution( $size, $size );
        $image->readImage( __DIR__.'/../Resources/public/images/favicon.svg' );
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

    public function imageAction( $image )
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

    private function createImageResponse( $imagePath, $format, $width = null, $height = null )
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
        $image->setBackgroundColor( ($format == 'jpg' || $format == 'bmp')? new \ImagickPixel( 'white' ) : new \ImagickPixel( 'transparent' ) );
        if ( is_int( $width ) && is_int( $height ) ) {
            $image->setresolution( $width, $height );
        }
        $image->readImage( $imagePath );
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
