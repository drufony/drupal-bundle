<?php
namespace Bangpound\Bundle\DrupalBundle\Routing;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class DrupalLoader
 * @package Bangpound\Bundle\DrupalBundle\Routing
 */
class DrupalLoader implements LoaderInterface
{
    private $loaded = false;

    /**
     * Loads a resource.
     *
     * @param  mixed                                      $resource The resource
     * @param  string                                     $type     The resource type
     * @throws \RuntimeException
     * @return \Symfony\Component\Routing\RouteCollection
     */
    public function load($resource, $type = null)
    {
        if (true === $this->loaded) {
            throw new \RuntimeException('Do not add the "drupal" loader twice');
        }

        $routes = new RouteCollection();
        $route = new Route('/', array('_drupal' => true));
        $routes->add('site_frontpage', $route);

        $drupal_router = menu_get_router();
        uasort($drupal_router, function ($a, $b) {
            if ($a['_fit'] == $b['_fit']) {
                return 0;
            }
            if ($a['_fit'] < $b['_fit']) {
                return 1;
            }

            return -1;
        });

        foreach ($drupal_router as $key => $router_item) {
            $index = 0;
            $parts = $router_item['_parts'];
            foreach ($parts as &$part) {
                if ($part == '%') {
                    $part = '{p'. $index++ .'}';
                }
            }

            $route = new Route('/'. implode('/', $parts));

            // Flag this request as Drupal answerable and set the callback.
            $route->setDefaults(array(
                '_drupal' => true,
                '_controller' => $router_item['page callback'],
            ));

            // Special compiler class allows Drupal routes to have optional parameters.
            $route->setOption('compiler_class', 'Bangpound\\Bundle\\DrupalBundle\\Routing\\RouteCompiler');

            // add the new route to the route collection:
            $routes->add($key, $route);
        }

        $this->loaded = true;

        return $routes;
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed  $resource A resource
     * @param string $type     The resource type
     *
     * @return Boolean true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return 'drupal' === $type;
    }

    /**
     * Gets the loader resolver.
     *
     * @return LoaderResolverInterface A LoaderResolverInterface instance
     */
    public function getResolver()
    {
    }

    /**
     * Sets the loader resolver.
     *
     * @param LoaderResolverInterface $resolver A LoaderResolverInterface instance
     */
    public function setResolver(LoaderResolverInterface $resolver)
    {
    }
}
