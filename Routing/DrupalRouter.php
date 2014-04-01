<?php
namespace Bangpound\Bundle\DrupalBundle\Routing;

use Drupal\Core\BootstrapInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class DrupalRouter
 * @package Bangpound\Bundle\DrupalBundle\Routing
 */
class DrupalRouter implements RouterInterface
{
    private $context;
    private $object;

    public function __construct(BootstrapInterface $object)
    {
        $this->object = $object;
    }

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
        $path = drupal_get_normal_path($name);
        if ($item = menu_get_item($path)) {
            return url($name, $options);
        }
        throw new RouteNotFoundException;
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

            // The requested path is an unalaised Drupal route.
            return array(
                '_legacy' => 'drupal',
                '_controller' => function ($_router_item) {
                        $router_item = $_router_item;

                        if (!$router_item['access']) {
                            return MENU_ACCESS_DENIED;
                        }

                        if ($router_item['include_file']) {
                            require_once DRUPAL_ROOT .'/'. $router_item['include_file'];
                        }

                        return call_user_func_array($router_item['page_callback'], $router_item['page_arguments']);
                    },
                '_route' => $router_item['path'],
            );
        } else {
            throw new ResourceNotFoundException(('Route for '. $path .' not found'));
        }
    }
}
