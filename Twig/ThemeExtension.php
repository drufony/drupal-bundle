<?php

namespace Bangpound\Bundle\DrupalBundle\Twig;

/**
 * Class ThemeExtension
 * @package Bangpound\Bundle\DrupalBundle\Twig
 */
class ThemeExtension extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('drupal_theme', 'theme', array('is_safe' => array('html'))),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'drupal_theme_extension';
    }
}
