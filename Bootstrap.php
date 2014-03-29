<?php

namespace Bangpound\Bundle\DrupalBundle;

use Bangpound\Bridge\Drupal\Bootstrap as BaseBootstrap;
use Symfony\Component\ClassLoader\MapClassLoader;

/**
 * Class Bootstrap
 * @package Bangpound\Bundle\DrupalBundle
 */
class Bootstrap extends BaseBootstrap
{
    /**
     * @param array $values
     */
    public function __construct(array $values = array())
    {
        // If this bootstrap object is used in a service, bootstrap.inc may
        // not have been included yet. If the file is not included, the
        // Drupal bootstrap constants are not available.
        require_once $values['DRUPAL_ROOT'] .'/includes/bootstrap.inc';

        parent::__construct($values);

        /**
         * Sets up the script environment and loads settings.php.
         *
         * @see _drupal_bootstrap_configuration()
         */
        $this[DRUPAL_BOOTSTRAP_CONFIGURATION] = $this->share(function () {
            // Start a page timer:
            timer_start('page');

            // Initialize the configuration, including variables from settings.php.
            // drupal_settings_initialize();
            global $base_url, $base_path, $base_root;

            // Export these settings.php variables to the global namespace.
            global $databases, $cookie_domain, $conf, $installed_profile, $update_free_access, $db_url, $db_prefix, $drupal_hash_salt, $is_https, $base_secure_url, $base_insecure_url;
            $conf = array();

            if (file_exists(DRUPAL_ROOT . '/' . conf_path() . '/settings.php')) {
                include_once DRUPAL_ROOT . '/' . conf_path() . '/settings.php';
            }
            $is_https = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on';

            if (isset($base_url)) {
                // Parse fixed base URL from settings.php.
                $parts = parse_url($base_url);
                if (!isset($parts['path'])) {
                    $parts['path'] = '';
                }
                $base_path = $parts['path'] . '/';
                // Build $base_root (everything until first slash after "scheme://").
                $base_root = substr($base_url, 0, strlen($base_url) - strlen($parts['path']));
            } else {
                // Create base URL.
                $http_protocol = $is_https ? 'https' : 'http';
                $base_root = $http_protocol . '://' . $_SERVER['HTTP_HOST'];

                $base_url = $base_root;

                // $_SERVER['SCRIPT_NAME'] can, in contrast to $_SERVER['PHP_SELF'], not
                // be modified by a visitor.
                if ($dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '\/')) {
                    $base_path = $dir;
                    $base_url .= $base_path;
                    $base_path .= '/';
                } else {
                    $base_path = '/';
                }
            }
            $base_secure_url = str_replace('http://', 'https://', $base_url);
            $base_insecure_url = str_replace('https://', 'http://', $base_url);

            // We do not mess with cookie or session settings in Drupal at all.
        });


        $this[DRUPAL_BOOTSTRAP_VARIABLES] = $this->share($this->extend(DRUPAL_BOOTSTRAP_VARIABLES, function () {
            if (isset($GLOBALS['service_container']) && is_a($GLOBALS['service_container'], 'Symfony\\Component\\DependencyInjection\\ContainerInterface')) {
                /** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
                $container = $GLOBALS['service_container'];

                $GLOBALS['conf']['session_inc'] = $container->getParameter('bangpound_drupal.conf.session_inc');
                $GLOBALS['conf']['mail_system']['default-system'] = $container->getParameter('bangpound_drupal.conf.mail_system.default_system');
            }
        }));

        // DRUPAL_BOOTSTRAP_SESSION - in base class.

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

            // Detect string handling method
            unicode_check();
            // Undo magic quotes
            fix_gpc_magic();
            // Load all enabled modules
            module_load_all();
            // Make sure all stream wrappers are registered.
            file_get_stream_wrappers();
            // Ensure mt_rand is reseeded, to prevent random values from one page load
            // being exploited to predict random values in subsequent page loads.
            $seed = unpack("L", drupal_random_bytes(4));
            mt_srand($seed[1]);

            $test_info = &$GLOBALS['drupal_test_info'];
            if (!empty($test_info['in_child_site'])) {
                // Running inside the simpletest child site, log fatal errors to test
                // specific file directory.
                ini_set('log_errors', 1);
                ini_set('error_log', 'public://error.log');
            }

            // Initialize $_GET['q'] prior to invoking hook_init().
            drupal_path_initialize();

            // Remaining function calls from this phase of bootstrap must happen after
            // the user is authenticated because they initialize the theme and call
            // menu_get_item().
        });
    }
}
