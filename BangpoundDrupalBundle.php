<?php

namespace Bangpound\Bundle\DrupalBundle;

use Bangpound\Bundle\DrupalBundle\DependencyInjection\Compiler\LegacyPhpCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class BangpoundDrupalBundle
 * @package Bangpound\Bundle\DrupalBundle
 */
class BangpoundDrupalBundle extends Bundle
{
    /**
     * Boots the Bundle.
     */
    public function boot()
    {
        // Console applications boot twice, which leads to harmless but noisy PHP warnings.
        if (!defined('DRUPAL_ROOT')) {
            define('DRUPAL_ROOT', realpath($this->container->get('kernel')->getRootDir() .'/../web'));
        }

        require_once DRUPAL_ROOT . '/includes/bootstrap.inc';

        drupal_override_server_variables(array('url' => $this->container->getParameter('bangpound_drupal.url')));
        drupal_bootstrap(NULL, TRUE, $this->container->getParameter('bangpound_drupal.bootstrap.class'));
    }

    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new LegacyPhpCompilerPass());
    }
}
