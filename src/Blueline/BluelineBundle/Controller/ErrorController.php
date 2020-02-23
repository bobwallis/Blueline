<?php
namespace Blueline\BluelineBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ErrorController extends Controller
{
    public function httpErrorAction(Request $request, $code, $message = null)
    {
        throw new HttpException($code, $message);
    }
}
