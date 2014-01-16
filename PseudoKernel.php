<?php

namespace Bangpound\Bundle\DrupalBundle;

use Bangpound\Bundle\DrupalBundle\HttpKernel\PseudoKernel as BasePseudoKernel;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PseudoKernel
 * @package Drupal
 */
class PseudoKernel extends BasePseudoKernel
{
    /**
     * @var
     */
    private $root;

    /**
     * @var
     */
    private $uri;

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function boot()
    {
        drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
        $this->booted = true;
    }

    /**
     * @param $uri
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param Bootstrap $bootstrap
     */
    public function setBootstrap(Bootstrap $bootstrap)
    {
        drupal_bootstrap(NULL, TRUE, $bootstrap);
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        if (false === $this->booted) {
            $this->boot();
        }

        return menu_execute_active_handler();
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getWorkingDir()
    {
        return $this->root;
    }

    /**
     * @param $root
     */
    public function setWorkingDir($root)
    {
        $this->root = $root;
    }
}
