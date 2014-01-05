<?php

namespace Bangpound\Bundle\DrupalBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class PathListener
 * @package Bangpound\Bundle\DrupalBundle\EventListener
 */
class PathListener
{
    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        // @TODO Strip the route prefix from the path info.
        if ('/' == $request->getPathInfo()) {
            $path = variable_get('site_frontpage', 'node');
        } else {
            $path = urldecode(substr($request->getPathInfo(), 1));
        }

        // The 'q' variable is pervasive in Drupal, so it's best to just keep
        // it even though it's very un-Symfony.
        $_GET['q'] = drupal_get_normal_path($path);

        if (!$request->attributes->get('_drupal', false)) {
            $router_item = menu_get_item();
            if ($router_item) {

                // The requested path is an unalaised Drupal route.
                $request->attributes->add(array(
                    '_drupal' => true,
                    '_controller' => $router_item['page_callback'],
                    '_route' => $router_item['path'],
                ));
            }
        }
    }
}
