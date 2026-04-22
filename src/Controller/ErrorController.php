<?php

namespace Blueline\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Controller for generating HTTP errors.
 *
 * Routes:
 * - GET /towers and /towers/{del}: Returns HTTP 410 for deleted towers section
 * - GET /associations and /associations/{del}: Returns HTTP 410 for deleted associations section
 *
 * Used by routing configuration to handle requests to removed sections of the site.
 */
class ErrorController extends AbstractController
{
    public function httpError(Request $request, $code, $message = null)
    {
        throw new HttpException((int) $code, $message ?? '');
    }
}
