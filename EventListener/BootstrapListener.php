<?php

namespace Bangpound\Bundle\DrupalBundle\EventListener;

use Bangpound\Bundle\DrupalBundle\Globals;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class BootstrapListener extends ContainerAware
{
    // This looks dumb.
    private $globalz;

    public function __construct(Globals $globalz)
    {
        require_once DRUPAL_ROOT . '/includes/bootstrap.inc';

        $this->globalz = $globalz;
    }

    public function onKernelRequestEarly(GetResponseEvent $event)
    {
        chdir(DRUPAL_ROOT);

        // Original bootstrap phases mostly take care of including files.
        drupal_bootstrap(DRUPAL_BOOTSTRAP_CONFIGURATION);

        $request = $event->getRequest();

        // When clean URLs are enabled, emulate ?q=foo/bar using REQUEST_URI. It is
        // not possible to append the query string using mod_rewrite without the B
        // flag (this was added in Apache 2.2.8), because mod_rewrite unescapes the
        // path before passing it on to PHP. This is a problem when the path contains
        // e.g. "&" or "%" that have special meanings in URLs and must be encoded.
        //
        // @see drupal_environment_initialize();
        $path = $_GET['q'] = urldecode(substr($request->getPathInfo(), 1));
        $request->query->set('q', $path);

        $GLOBALS['base_url'] = $request->getSchemeAndHttpHost();

        drupal_bootstrap(DRUPAL_BOOTSTRAP_VARIABLES);
    }

    public function onKernelRequestBeforeSession(GetResponseEvent $event)
    {
        drupal_bootstrap(DRUPAL_BOOTSTRAP_SESSION);
    }

    public function onKernelRequestAfterSession(GetResponseEvent $event)
    {
        if (empty($GLOBALS['user'])) {
            $GLOBALS['user'] = drupal_anonymous_user();
            date_default_timezone_set(drupal_get_user_timezone());
        }

        // This is basically noop.
        drupal_bootstrap(DRUPAL_BOOTSTRAP_PAGE_HEADER);

    }
     public function onKernelRequestBeforeRouter(GetResponseEvent $event)
    {
        drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

        $request = $event->getRequest();
        if (!$request->attributes->get('_drupal', false)) {

            // The 'q' variable is pervasive in Drupal, so it's best to just keep
            // it even though it's very un-Symfony.
            $path = drupal_get_normal_path($_GET['q']);

            if (variable_get('menu_rebuild_needed', FALSE) || !variable_get('menu_masks', array())) {
                menu_rebuild();
            }
            $original_map = arg(NULL, $path);

            $parts = array_slice($original_map, 0, MENU_MAX_PARTS);
            $ancestors = menu_get_ancestors($parts);
            $router_item = db_query_range('SELECT * FROM {menu_router} WHERE path IN (:ancestors) ORDER BY fit DESC', 0, 1, array(':ancestors' => $ancestors))->fetchAssoc();

            if ($router_item) {
                // Allow modules to alter the router item before it is translated and
                // checked for access.
                drupal_alter('menu_get_item', $router_item, $path, $original_map);

                // The requested path is an unalaised Drupal route.
                $request->attributes->add(array(
                    '_drupal' => true,
                    '_controller' => $router_item['page_callback'],
                    '_route' => $router_item['path'],
                ));
            }
        }
    }

    public function onKernelRequestAfterLocale(GetResponseEvent $event)
    {
        drupal_bootstrap(DRUPAL_BOOTSTRAP_LANGUAGE);
    }

    public function onKernelRequestAfterFirewall(GetResponseEvent $event)
    {
        // Prior to invoking hook_init(), initialize the theme (potentially a custom
        // one for this page), so that:
        // - Modules with hook_init() implementations that call theme() or
        //   theme_get_registry() don't initialize the incorrect theme.
        // - The theme can have hook_*_alter() implementations affect page building
        //   (e.g., hook_form_alter(), hook_node_view_alter(), hook_page_alter()),
        //   ahead of when rendering starts.
        menu_set_custom_theme();
        drupal_theme_initialize();
        module_invoke_all('init');
    }
}
