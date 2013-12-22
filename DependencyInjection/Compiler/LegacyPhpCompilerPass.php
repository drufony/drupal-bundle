<?php
namespace Bangpound\Bundle\DrupalBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class LegacyPhpCompilerPass
 * @package Bangpound\Bundle\DrupalBundle\DependencyInjection\Compiler
 */
class LegacyPhpCompilerPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     *
     * @api
     */
    public function process(ContainerBuilder $container)
    {
        $parameter = $container->getParameter('bangpound_drupal.http_kernel.class');
        $container->setParameter('http_kernel.class', $parameter);

        $parameter = $container->getParameter('bangpound_drupal.controller_resolver.class');
        $container->setParameter('controller_resolver.class', $parameter);
    }
}
