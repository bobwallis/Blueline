<?php

namespace Blueline;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * Application kernel using Symfony's micro-kernel configuration.
 */
class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}
