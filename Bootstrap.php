<?php

namespace Bangpound\Bundle\DrupalBundle;

use Bangpound\Bridge\Drupal\Bootstrap as BaseBootstrap;

/**
 * Class Bootstrap
 * @package Bangpound\Bundle\DrupalBundle
 */
class Bootstrap extends BaseBootstrap
{
    /**
     * @param array $values
     */
    public function __construct(array $values = array())
    {
        // If this bootstrap object is used in a service, bootstrap.inc may
        // not have been included yet. If the file is not included, the
        // Drupal bootstrap constants are not available.
        require_once $values['DRUPAL_ROOT'] .'/includes/bootstrap.inc';

        parent::__construct($values);

    }
}
