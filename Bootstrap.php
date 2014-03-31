<?php

namespace Bangpound\Bundle\DrupalBundle;

use Bangpound\Bridge\Drupal\Bootstrap as BaseBootstrap;
use Drupal\Core\BootstrapPhases;

/**
 * Class Bootstrap
 * @package Bangpound\Bundle\DrupalBundle
 */
class Bootstrap extends BaseBootstrap
{
    private $cwd;

    /**
     * @param string $root
     * @param string $uri
     */
    public function __construct($root, $uri = null)
    {
        if (!defined('DRUPAL_ROOT')) {
            if (realpath($root) != getcwd()) {
                $this->cwd = getcwd();
                chdir($root);
            }

            /**
             * Root directory of Drupal installation.
             */
            define('DRUPAL_ROOT', getcwd());

            require_once DRUPAL_ROOT . '/includes/bootstrap.inc';

            if (BootstrapPhases::NEVER_STARTED === drupal_get_bootstrap_phase()) {
                drupal_bootstrap(null, TRUE, $this);
                drupal_override_server_variables(array('url' => $uri));
            }
        }
    }
}
