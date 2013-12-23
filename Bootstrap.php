<?php

namespace Bangpound\Bundle\DrupalBundle;

use Bangpound\Drupal\Bootstrap\AutoloadBootstrap;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class Bootstrap
 * @package Bangpound\Bundle\DrupalBundle
 */
class Bootstrap extends AutoloadBootstrap {

    /**
     * @param array $values
     */
    public function __construct(array $values = array())
    {
        parent::__construct($values);

        $this['drupal_environment_initialize'] = function () {

        };

        $this[DRUPAL_BOOTSTRAP_PAGE_CACHE] = function () {
            $this['_drupal_bootstrap_page_cache__plugins'];
        };
    }
}