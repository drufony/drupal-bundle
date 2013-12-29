<?php

namespace Bangpound\Bundle\DrupalBundle\Composer;

use Composer\Script\CommandEvent;
use Symfony\Component\Filesystem\Filesystem;

class ScriptHandler
{
    /**
     * Installs Drupal under the web root directory.
     *
     * @param $event CommandEvent A instance
     */
    public static function installDrupal(CommandEvent $event)
    {
        $options = self::getOptions($event);
        $webDir = $options['symfony-web-dir'];
        $composer = $event->getComposer();
        $filesystem = new Filesystem();

        $drupal_root = $composer->getConfig()->get('vendor-dir') . DIRECTORY_SEPARATOR .
            $composer->getPackage()->getRequires()['drupal/drupal']->getTarget();

        $directories = array(
            'includes',
            'misc',
            'modules',
            'profiles',
            'themes',
        );

        foreach ($directories as $directory) {
            $originDir = '../'. $drupal_root .'/'. $directory;
            $targetDir = $webDir.'/'.$directory;
            echo sprintf('Creating symlink for Drupal\'s \'%s\' directory', $directory) . PHP_EOL;
            $filesystem->symlink($originDir, $targetDir);
        }

        $directory = 'sites';
        $targetDir = $webDir.'/'.$directory;

        if (!$filesystem->exists($targetDir)) {
            $originDir = $drupal_root .'/'. $directory;
            echo sprintf('Creating new sites directory', $directory) . PHP_EOL;
            $filesystem->mirror($originDir, $targetDir);
        }
    }

    protected static function getOptions(CommandEvent $event)
    {
        $options = array_merge(array(
            'symfony-web-dir' => 'web',
            'symfony-drupal-install' => 'relative',
        ), $event->getComposer()->getPackage()->getExtra());

        $options['symfony-drupal-install'] = getenv('SYMFONY_DRUPAL_INSTALL') ?: $options['symfony-drupal-install'];

        return $options;
    }
}
