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
        define('DRUPAL_ROOT', realpath($this->container->get('kernel')->getRootDir() .'/../web'));

        // This is required to inject the response and other services into the global namespace.
        $globalz = $this->container->get('bangpound_drupal.globals');

        require_once DRUPAL_ROOT . '/includes/bootstrap.inc';

        if (isset($globalz['request'])) {
            chdir(DRUPAL_ROOT);

            /** @var \Symfony\Component\HttpFoundation\Request $request */
            $request = $globalz['request'];
            $globalz['base_url'] = $request->getSchemeAndHttpHost();

            drupal_override_server_variables(array(
                'url' => $request->getSchemeAndHttpHost() .'/'. basename($request->server->get('SCRIPT_FILENAME')),
            ));
        }
        else {
            $globalz['base_url'] = 'http://localhost';
            drupal_override_server_variables(array(
                'url' => 'http://localhost',
            ));
        }

        drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL, TRUE, $this->container->getParameter('bangpound_drupal.bootstrap.class'));
    }

    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new LegacyPhpCompilerPass());
    }
}
