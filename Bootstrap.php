<?php

namespace Bangpound\Bundle\DrupalBundle;

use Bangpound\Bridge\Drupal\Bootstrap as BaseBootstrap;

/**
 * Class Bootstrap
 * @package Bangpound\Bundle\DrupalBundle
 */
class Bootstrap extends BaseBootstrap
{
    private $cwd;
    private $root;
    private $uri;

    /**
     * @return mixed
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @param mixed $root
     */
    public function setRoot($root)
    {
        $this->root = $root;
    }

    /**
     * @return mixed
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param mixed $uri
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * @return mixed
     */
    public function getCwd()
    {
        return $this->cwd;
    }

    /**
     * @param mixed $cwd
     */
    public function setCwd($cwd)
    {
        $this->cwd = $cwd;
    }

    public function boot()
    {
        if (!defined('DRUPAL_ROOT')) {
            if (getcwd() !== $this->getRoot()) {
                $this->setCwd(getcwd());
                chdir($this->getRoot());
            }
            define('DRUPAL_ROOT', getcwd());
        }
        require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
        drupal_override_server_variables(array('url' => $this->uri));
        drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL, TRUE, $this);
    }
}
