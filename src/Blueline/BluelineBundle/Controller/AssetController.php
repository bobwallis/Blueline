<?php
namespace Blueline\BluelineBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
* @Cache(maxage="604800", public=true, lastModified="asset_update")
*/
class AssetController extends Controller
{
    public function fontAction($font, Request $request)
    {
        $fontPath = __DIR__.'/../Resources/public/fonts/'.$font.'.'.$request->getRequestFormat();
        if (!file_exists($fontPath)) {
            throw $this->createNotFoundException('The font does not exist');
        }
        return new Response(file_get_contents($fontPath));
    }

    public function faviconAction(Request $request)
    {
        $format  = $request->getRequestFormat();
        $size =    intval($request->get('size') ?: 32);

        // Create basic response object
        $response = new Response();

        // Create the image
        switch ($format) {
            case 'svg':
                $response->setContent(file_get_contents(__DIR__.'/../Resources/public/images/favicon.svg'));
                break;
            case 'png':
            case 'gif':
            case 'jpg':
            case 'bmp':
                $image = new \Imagick();
                $image->setResolution($size*1.2, $size*1.2);
                $image->setBackgroundColor(($format == 'jpg' || $format == 'bmp') ? new \ImagickPixel('white') : new \ImagickPixel('transparent'));
                $image->readImage(__DIR__.'/../Resources/public/images/favicon.svg');
                $image->scaleImage($size, $size);
                switch ($format) {
                    case 'png':
                        $image->setImageFormat('png32');
                        break;
                    case 'gif':
                        $image->setImageFormat('gif');
                        break;
                    case 'jpg':
                        $image->setImageFormat('jpeg');
                        $image->setCompression(\Imagick::COMPRESSION_JPEG);
                        $image->setImageCompressionQuality(90);
                        $image->setImageFormat('jpeg');
                        break;
                    case 'bmp':
                        $image->setImageFormat('bmp');
                        break;
                }
                $image->stripImage();
                $response->setContent($image);
                $image->destroy();
                break;
            case 'ico':
                $response->setContent(file_get_contents(__DIR__.'/../Resources/public/images/favicon.ico'));
                break;
        }

        return $response;
    }

    public function iOSiconAction($size, Request $request)
    {
        $size = intval($size);
        $image = new \Imagick();
        $image->setResolution($size*1.2, $size*1.2);
        $image->readImage(__DIR__.'/../Resources/public/images/iosicon.svg');
        $image->scaleImage($size, $size);
        $image->setImageFormat('png32');
        $image->stripImage();

        return new Response($image);
    }

    public function iOSstartupAction($size, $ratio, Request $request)
    {
        $sizes = array_map(function ($s) { return intval($s); }, explode('x', $size));
        $image = new \Imagick();
        $image->newImage($sizes[0], $sizes[1], '#002856');
        $image->setGravity(\Imagick::GRAVITY_NORTHWEST);
        $draw = new \ImagickDraw();
        $draw->setFont(__DIR__.'/../Resources/public/fonts/logo.ttf');
        $draw->setFontSize(20*$ratio);
        $draw->setFillColor(new \ImagickPixel('white'));
        $drawSize = $image->queryFontMetrics($draw, "BLUELINE");
        $image->annotateImage($draw, intval(($sizes[0]-$drawSize['textWidth'])/2), 100*$ratio, 0, 'BLUELINE');
        $image->setImageFormat('png32');
        $image->stripImage();

        return new Response($image);
    }

    public function imageAction($image, Request $request)
    {
        $format  = $request->getRequestFormat();
        $file    = false;

        switch ($image) {
            case 'database':
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
                $file = ($format == 'gif') ? __DIR__.'/../Resources/public/images/loading.gif' : false;
                break;
        }

        if ($file) {
            return $this->createImageResponse($request, $file, $format);
        } else {
            throw $this->createNotFoundException('Image not found');
        }
    }

    private function createImageResponse($request, $imagePath, $format, $width = null, $height = null)
    {
        // Check we actually need to convert
        $pathInfo = pathinfo($imagePath);
        if ($pathInfo['extension'] == $format) {
            return new Response(file_get_contents($imagePath));
        }

        // Output the image
        $image = new \Imagick();
        if (is_int($width) && is_int($height)) {
            $image->setResolution($width, $height);
        }
        $image->setBackgroundColor(($format == 'jpg' || $format == 'bmp') ? new \ImagickPixel('white') : new \ImagickPixel('transparent'));
        $image->readImage($imagePath);
        if (is_int($width) && is_int($height)) {
            $image->scaleImage($width, $height);
        }
        $image->stripImage();
        switch ($format) {
            case 'svg':
                $image->setImageFormat('svg');
                break;
            case 'png':
                $image->setImageFormat('png32');
                break;
            case 'gif':
                $image->setImageFormat('gif');
                break;
            case 'jpg':
                $image->setImageFormat('jpeg');
                $image->setCompression(\Imagick::COMPRESSION_JPEG);
                $image->setImageCompressionQuality(90);
                $image->setImageFormat('jpeg');
                break;
            case 'bmp':
                $image->setImageFormat('bmp');
                break;
        }

        return new Response($image);
    }
}
