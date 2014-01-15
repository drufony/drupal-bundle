<?php

namespace Bangpound\Bundle\DrupalBundle\HttpKernel;

use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Interface PseudoKernelInterface
 * @package Bangpound\LegacyPhpHttpKernel
 */
interface PseudoKernelInterface extends HttpKernelInterface
{
    /**
     * Boots the current kernel.
     *
     * @api
     */
    public function boot();

    /**
     * Shutdowns the kernel.
     *
     * This method is mainly useful when doing functional testing.
     *
     * @api
     */
    public function shutdown();

    /**
     * Gets the name of the kernel
     *
     * @return string The kernel name
     *
     * @api
     */
    public function getName();

    /**
     * Gets the environment.
     *
     * @return string The current environment
     *
     * @api
     */
    public function getEnvironment();

    /**
     * Checks if debug mode is enabled.
     *
     * @return Boolean true if debug mode is enabled, false otherwise
     *
     * @api
     */
    public function isDebug();

    /**
     * Gets the application root dir.
     *
     * @return string The application root dir
     *
     * @api
     */
    public function getWorkingDir();

    /**
     * Gets the request start time (not available if debug is disabled).
     *
     * @return integer The request start timestamp
     *
     * @api
     */
    public function getStartTime();

    /**
     * Gets the charset of the application.
     *
     * @return string The charset
     *
     * @api
     */
    public function getCharset();
}
