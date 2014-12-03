<?php
namespace Blueline\BluelineBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class AssetController extends Controller
{
    private $cacheTime = 604800;

    public function fontAction($font)
    {
        $request = $this->getRequest();
        $format = $request->getRequestFormat();
        $fontPath = __DIR__.'/../Resources/public/fonts/'.$font.'.'.$format;
        if (!file_exists($fontPath)) {
            throw $this->createNotFoundException('The font does not exist');
        }

        // Create basic response object
        $response = new Response();
        if ($this->container->getParameter('kernel.environment') == 'prod') {
            $response->setMaxAge($this->cacheTime);
            $response->setPublic();
        }
        $response->setLastModified(new \DateTime('@'.$this->container->getParameter('asset_update')));
        if ($response->isNotModified($request)) {
            return $response;
        }

        $response->setContent(file_get_contents($fontPath));

        return $response;
    }

    public function faviconAction()
    {
        $request = $this->getRequest();
        $format  = $request->getRequestFormat();
        $size =    intval($request->get('size') ?: 32);

        // Create basic response object
        $response = new Response();
        if ($this->container->getParameter('kernel.environment') == 'prod') {
            $response->setMaxAge($this->cacheTime);
            $response->setPublic();
        }
        $response->setLastModified(new \DateTime('@'.$this->container->getParameter('asset_update')));
        if ($response->isNotModified($request)) {
            return $response;
        }

        // Create the image
        switch ($format) {
            case 'svg' :
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
                    case 'png' :
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

    public function iOSiconAction($size)
    {
        $size = intval($size);
        $image = new \Imagick();
        $image->setResolution($size*1.2, $size*1.2);
        $image->readImage(__DIR__.'/../Resources/public/images/iosicon.svg');
        $image->scaleImage($size, $size);
        $image->setImageFormat('png32');
        $image->stripImage();

        // Create basic response object
        $response = new Response();
        if ($this->container->getParameter('kernel.environment') == 'prod') {
            $response->setMaxAge($this->cacheTime);
            $response->setPublic();
        }
        $response->setLastModified(new \DateTime('@'.$this->container->getParameter('asset_update')));
        if ($response->isNotModified($this->getRequest())) {
            return $response;
        }

        $response->setContent($image);
        $image->destroy();

        return $response;
    }

    public function iOSstartupAction($size, $ratio)
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

        // Create basic response object
        $response = new Response();
        if ($this->container->getParameter('kernel.environment') == 'prod') {
            $response->setMaxAge($this->cacheTime);
            $response->setPublic();
        }
        $response->setLastModified(new \DateTime('@'.$this->container->getParameter('asset_update')));
        if ($response->isNotModified($this->getRequest())) {
            return $response;
        }

        $response->setContent($image);
        $image->destroy();

        return $response;
    }

    public function imageAction($image)
    {
        $request = $this->getRequest();
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
        // Create basic response object
        $response = new Response();
        if ($this->container->getParameter('kernel.environment') == 'prod') {
            $response->setMaxAge($this->cacheTime);
            $response->setPublic();
        }
        $response->setLastModified(new \DateTime('@'.$this->container->getParameter('asset_update')));
        if ($response->isNotModified($request)) {
            return $response;
        }

        // Check we actually need to convert
        $pathInfo = pathinfo($imagePath);
        if ($pathInfo['extension'] == $format) {
            $response->setContent(file_get_contents($imagePath));

            return $response;
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
            case 'svg' :
                $image->setImageFormat('svg');
                $response->setContent($image);
                break;
            case 'png':
                $image->setImageFormat('png32');
                $response->setContent($image);
                break;
            case 'gif':
                $image->setImageFormat('gif');
                $response->setContent($image);
                break;
            case 'jpg':
                $image->setImageFormat('jpeg');
                $image->setCompression(\Imagick::COMPRESSION_JPEG);
                $image->setImageCompressionQuality(90);
                $image->setImageFormat('jpeg');
                $response->setContent($image);
                break;
            case 'bmp':
                $image->setImageFormat('bmp');
                $response->setContent($image);
                break;
        }
        $image->destroy();

        return $response;
    }
}
