<?php

namespace Bangpound\Bundle\DrupalBundle;

/**
 * Class BootstrapEvents
 * @package Bangpound\Bundle\DrupalBundle
 */
final class BootstrapEvents
{
    const PRE_CONFIGURATION = 'drupal_bootstrap.pre.configuration';
    const POST_CONFIGURATION = 'drupal_bootstrap.post.configuration';
    const PRE_PAGE_CACHE = 'drupal_bootstrap.pre.page_cache';
    const POST_PAGE_CACHE = 'drupal_bootstrap.post.page_cache';
    const PRE_DATABASE = 'drupal_bootstrap.pre.database';
    const POST_DATABASE = 'drupal_bootstrap.post.database';
    const PRE_VARIABLES = 'drupal_bootstrap.pre.variables';
    const POST_VARIABLES = 'drupal_bootstrap.post.variables';
    const PRE_SESSION = 'drupal_bootstrap.pre.session';
    const POST_SESSION = 'drupal_bootstrap.post.session';
    const PRE_PAGE_HEADER = 'drupal_bootstrap.pre.page_header';
    const POST_PAGE_HEADER = 'drupal_bootstrap.post.page_header';
    const PRE_LANGUAGE = 'drupal_bootstrap.pre.language';
    const POST_LANGUAGE = 'drupal_bootstrap.post.language';
    const PRE_FULL = 'drupal_bootstrap.pre.full';
    const POST_FULL = 'drupal_bootstrap.post.full';

    /**
     * @param $phase
     * @return mixed
     */
    public static function preEvent($phase)
    {
        $events = array(
            DRUPAL_BOOTSTRAP_CONFIGURATION => self::PRE_CONFIGURATION,
            DRUPAL_BOOTSTRAP_PAGE_CACHE => self::PRE_PAGE_CACHE,
            DRUPAL_BOOTSTRAP_DATABASE => self::PRE_DATABASE,
            DRUPAL_BOOTSTRAP_VARIABLES => self::PRE_VARIABLES,
            DRUPAL_BOOTSTRAP_SESSION => self::PRE_SESSION,
            DRUPAL_BOOTSTRAP_PAGE_HEADER => self::PRE_PAGE_HEADER,
            DRUPAL_BOOTSTRAP_LANGUAGE => self::PRE_LANGUAGE,
            DRUPAL_BOOTSTRAP_FULL => self::PRE_FULL,
        );

        return $events[$phase];
    }

    /**
     * @param $phase
     * @return mixed
     */
    public static function postEvent($phase)
    {
        $events = array(
            DRUPAL_BOOTSTRAP_CONFIGURATION => self::POST_CONFIGURATION,
            DRUPAL_BOOTSTRAP_PAGE_CACHE => self::POST_PAGE_CACHE,
            DRUPAL_BOOTSTRAP_DATABASE => self::POST_DATABASE,
            DRUPAL_BOOTSTRAP_VARIABLES => self::POST_VARIABLES,
            DRUPAL_BOOTSTRAP_SESSION => self::POST_SESSION,
            DRUPAL_BOOTSTRAP_PAGE_HEADER => self::POST_PAGE_HEADER,
            DRUPAL_BOOTSTRAP_LANGUAGE => self::POST_LANGUAGE,
            DRUPAL_BOOTSTRAP_FULL => self::POST_FULL,
        );

        return $events[$phase];
    }
}
