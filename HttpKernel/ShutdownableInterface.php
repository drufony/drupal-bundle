<?php

namespace Bangpound\Bundle\DrupalBundle\HttpKernel;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Interface ShutdownableInterface
 * @package Bangpound\Bundle\DrupalBundle\HttpKernel
 */
interface ShutdownableInterface
{

    /**
     * Completes a request/response cycle that has been interrupted with PHP shutdown.
     *
     * Should be called from a shutdown method.
     *
     * @param Request  $request  A Request instance
     * @param Response $response A Response instance
     * @param integer  $type     The type of the request (one of HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST)
     *
     * @api
     */
    public function shutdown(Request $request, Response $response, $type = HttpKernelInterface::MASTER_REQUEST);
}
