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
            $router_item = $request->attributes->get('_router_item', false);
            if (!$router_item['access']) {
                throw new AccessDeniedHttpException;
            } elseif ($router_item['include_file']) {
                require_once DRUPAL_ROOT . '/' . $router_item['include_file'];
            }
        }
    }
}
