<?php
namespace Bangpound\Bundle\DrupalBundle\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class DrupalRouter
 * @package Bangpound\Bundle\DrupalBundle\Routing
 */
class DrupalRouter implements RouterInterface
{
    private $context;

    /**
     * {@inheritDocs}
     */
    public function setContext(RequestContext $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritDocs}
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * {@inheritDocs}
     */
    public function getRouteCollection()
    {
        return new RouteCollection();
    }

    /**
     * {@inheritDocs}
     */
    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        $options = array(
            'absolute' => $referenceType === self::ABSOLUTE_URL ? true : false,
        );

        return url($name, $options);
    }

    /**
     * {@inheritDocs}
     */
    public function match($pathinfo)
    {
        // The 'q' variable is pervasive in Drupal, so it's best to just keep
        // it even though it's very un-Symfony.
        $path = drupal_get_normal_path(substr($pathinfo, 1));

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

            $page_callback = $router_item['page_callback'];

            // The requested path is an unalaised Drupal route.
            return array(
                '_drupal' => true,
                '_controller' => function (Request $request) use ($page_callback) {
                        $router_item = $request->attributes->get('_router_item', array(
                                'page_callback' => $page_callback,
                                'page_arguments' => array(),
                            )
                        );

                        if (!$router_item['access']) {
                            throw new AccessDeniedException;
                        } elseif ($router_item['include_file']) {
                            require_once DRUPAL_ROOT .'/'. $router_item['include_file'];
                        }

                        return call_user_func_array($router_item['page_callback'], $router_item['page_arguments']);
                    },
                '_route' => $router_item['path'],
            );
        }
        throw new ResourceNotFoundException();
    }
}
