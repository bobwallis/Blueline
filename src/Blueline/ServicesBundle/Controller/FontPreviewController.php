<?php
namespace Blueline\ServicesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FontPreviewController extends Controller
{
    public function previewAction(Request $request)
    {
        $face = escapeshellarg('normal 16px '.$request->get('typeface'));
        $text = escapeshellarg($request->get('text'));

        $response = new Response();
        if ($this->container->getParameter('kernel.environment') == 'prod') {
            $response->setMaxAge(31536000);
            $response->setPublic();
        }

        $process = new Process('phantomjs --disk-cache=true --load-images=false "'.__DIR__.'/../Resources/phantomjs/preview_font.js" '.$face.' '.$text.' 2>&1');
        $process->mustRun();
        $response->setContent($process->getOutput());

        return $response;
    }
}
