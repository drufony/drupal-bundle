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
        define('DRUPAL_ROOT', getcwd());

        $this->container->get('bangpound_drupal.globals');

        require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
        drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL, TRUE, $this->container->getParameter('bangpound_drupal.bootstrap.class'));
    }

    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new LegacyPhpCompilerPass());
    }
}
