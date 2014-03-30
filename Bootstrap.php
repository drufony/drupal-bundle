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

        $this[DRUPAL_BOOTSTRAP_VARIABLES] = $this->share($this->extend(DRUPAL_BOOTSTRAP_VARIABLES, function () {
            if (isset($GLOBALS['service_container']) && is_a($GLOBALS['service_container'], 'Symfony\\Component\\DependencyInjection\\ContainerInterface')) {
                /** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
                $container = $GLOBALS['service_container'];

                $GLOBALS['conf']['session_inc'] = $container->getParameter('bangpound_drupal.conf.session_inc');
                $GLOBALS['conf']['mail_system']['default-system'] = $container->getParameter('bangpound_drupal.conf.mail_system.default_system');
            }
        }));
    }
}
