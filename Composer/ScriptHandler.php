<?php

namespace Bangpound\Bundle\DrupalBundle\Composer;

use Composer\Script\CommandEvent;

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

        $drupal_root = $composer->getConfig()->get('vendor-dir') . DIRECTORY_SEPARATOR .
            $composer->getPackage()->getRequires()['drupal/drupal']->getTarget();

        $directories = array(
            'includes',
            'misc',
            'modules',
            'profiles',
            'sites',
            'themes',
        );

        foreach ($directories as $directory) {
            $target = '../'. $drupal_root .'/'. $directory;
            $link = $webDir.'/'.$directory;
            if (is_link($link) || file_exists($link)) {
                unlink($link);
            }
            symlink($target, $link);
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
