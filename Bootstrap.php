<?php

namespace Bangpound\Bundle\DrupalBundle;

use Bangpound\Drupal\Bootstrap\AutoloadBootstrap;

/**
 * Class Bootstrap
 * @package Bangpound\Bundle\DrupalBundle
 */
class Bootstrap extends AutoloadBootstrap
{
    /**
     * @param array $values
     */
    public function __construct(array $values = array())
    {
        parent::__construct($values);

        /**
         * Sets up the script environment and loads settings.php.
         *
         * @see _drupal_bootstrap_configuration()
         */
        $this[DRUPAL_BOOTSTRAP_CONFIGURATION] = $this->share(function () {
        });

        $this[DRUPAL_BOOTSTRAP_PAGE_CACHE] = $this->share(function () {
            // Allow specifying special cache handlers in settings.php, like
            // using memcached or files for storing cache information.
            require_once DRUPAL_ROOT . '/includes/cache.inc';
            foreach (variable_get('cache_backends', array()) as $include) {
                require_once DRUPAL_ROOT . '/' . $include;
            }
        });

        // DRUPAL_BOOTSTRAP_DATABASE - in parent class.
        // DRUPAL_BOOTSTRAP_VARIABLES - in base class.
        // DRUPAL_BOOTSTRAP_SESSION - in base class.

        $this[DRUPAL_BOOTSTRAP_PAGE_HEADER] = $this->share(function () {
        });

        // DRUPAL_BOOTSTRAP_LANGUAGE

        $this[DRUPAL_BOOTSTRAP_FULL] = $this->share(function () {
            require_once DRUPAL_ROOT . '/includes/common.inc';
            require_once DRUPAL_ROOT . '/' . variable_get('path_inc', 'includes/path.inc');
            require_once DRUPAL_ROOT . '/includes/theme.inc';
            require_once DRUPAL_ROOT . '/includes/pager.inc';
            require_once DRUPAL_ROOT . '/' . variable_get('menu_inc', 'includes/menu.inc');
            require_once DRUPAL_ROOT . '/includes/tablesort.inc';
            require_once DRUPAL_ROOT . '/includes/file.inc';
            require_once DRUPAL_ROOT . '/includes/unicode.inc';
            require_once DRUPAL_ROOT . '/includes/image.inc';
            require_once DRUPAL_ROOT . '/includes/form.inc';
            require_once DRUPAL_ROOT . '/includes/mail.inc';
            require_once DRUPAL_ROOT . '/includes/actions.inc';
            require_once DRUPAL_ROOT . '/includes/ajax.inc';
            require_once DRUPAL_ROOT . '/includes/token.inc';
            require_once DRUPAL_ROOT . '/includes/errors.inc';
        });
    }
}
