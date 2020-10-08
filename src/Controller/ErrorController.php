<?php
namespace Blueline\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ErrorController extends AbstractController
{
    public function httpError(Request $request, $code, $message = null)
    {
        throw new HttpException($code, $message);
    }
}
