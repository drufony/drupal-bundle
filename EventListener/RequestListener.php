<?php

namespace Bangpound\Bundle\DrupalBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class RequestListener
 * @package Bangpound\Drupal\EventListener
 */
class RequestListener
{

  /**
   * This method is based on menu_execute_active_handler() which is called
   * in Drupal 7's front controller (index.php).
   *
   * @param GetResponseEvent $event
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if ($request->attributes->get('_drupal', false)) {
            if ('/' == $request->getPathInfo()) {
                $_GET['q'] = variable_get('site_frontpage', 'node');
            } else {
                $_GET['q'] = urldecode(substr($request->getPathInfo(), 1));
            }
            $router_item = menu_get_item();

            if (!$router_item['access']) {
                throw new AccessDeniedHttpException;
            }

            if (isset($router_item['include_file']) && !empty($router_item['include_file'])) {
                require_once DRUPAL_ROOT . '/' . $router_item['include_file'];
            }

            $request->attributes->set('_router_item', $router_item);
        }
    }
}
