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
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new LegacyPhpCompilerPass());
    }
}
