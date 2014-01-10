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
        $this[DRUPAL_BOOTSTRAP_CONFIGURATION] = function () {
            $this['drupal_environment_initialize'];
            // Start a page timer:
            timer_start('page');
            // Initialize the configuration, including variables from settings.php.
            $this['drupal_settings_initialize'];
        };

        $this['drupal_environment_initialize'] = function () {
            // When clean URLs are enabled, emulate ?q=foo/bar using REQUEST_URI. It is
            // not possible to append the query string using mod_rewrite without the B
            // flag (this was added in Apache 2.2.8), because mod_rewrite unescapes the
            // path before passing it on to PHP. This is a problem when the path contains
            // e.g. "&" or "%" that have special meanings in URLs and must be encoded.
            $_GET['q'] = request_path();
        };

        /**
         * Sets the base URL, cookie domain, and session name from configuration.
         *
         * @see drupal_settings_initialize()
         */
        $this['drupal_settings_initialize'] = function () {
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
        };

        $this[DRUPAL_BOOTSTRAP_VARIABLES] = $this->extend(DRUPAL_BOOTSTRAP_VARIABLES, function () {
            if (!isset($GLOBALS['service_container'])) {
                return;
            }

            /** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
            $container = $GLOBALS['service_container'];

            $GLOBALS['conf']['session_inc'] = $container->getParameter('bangpound_drupal.conf.session_inc');
            $GLOBALS['conf']['mail_system']['default-system'] = $container->getParameter('bangpound_drupal.conf.mail_system.default_system');
        });

        $this[DRUPAL_BOOTSTRAP_SESSION] = $this->extend(DRUPAL_BOOTSTRAP_SESSION, function () {
            if (empty($GLOBALS['user'])) {
                $GLOBALS['user'] = drupal_anonymous_user();
                date_default_timezone_set(drupal_get_user_timezone());
            }
        });

        $this[DRUPAL_BOOTSTRAP_PAGE_CACHE] = function () {
            // Allow specifying special cache handlers in settings.php, like
            // using memcached or files for storing cache information.
            require_once DRUPAL_ROOT . '/includes/cache.inc';
            foreach (variable_get('cache_backends', array()) as $include) {
                require_once DRUPAL_ROOT . '/' . $include;
            }
            drupal_block_denied(ip_address());
        };

        $this[DRUPAL_BOOTSTRAP_PAGE_HEADER] = function () {
            bootstrap_invoke_all('boot');

            if (!drupal_is_cli()) {
                drupal_page_header();
            }
        };
    }
}
