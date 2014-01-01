<?php

namespace Bangpound\Bundle\DrupalBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

/**
 * Class ViewListener
 * @package Bangpound\Drupal\EventListener
 */
class ViewListener
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     *
     */
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->container->get('bangpound_drupal.response');
        if ($request->attributes->get('_drupal', false)) {
            $router_item = $request->attributes->get('_router_item', array());
            $default_delivery_callback = (isset($router_item) && $router_item) ? $router_item['delivery_callback'] : NULL;
            $page_callback_result = $event->getControllerResult();
            drupal_deliver_page($page_callback_result, $default_delivery_callback);
            $response->setContent(ob_get_clean());
            $event->setResponse($response);
        }
    }

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
