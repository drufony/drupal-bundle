<?php

namespace Bangpound\Bundle\DrupalBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Class ExceptionListener
 * @package Bangpound\Drupal\EventListener
 */
class ExceptionListener
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        $request = $event->getRequest();
        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->container->get('bangpound_drupal.response');

        if (is_a($exception, 'Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException')) {
            if ($request->attributes->get('_drupal', false)) {
                $router_item = $request->attributes->get('_router_item', array());
            }
            $default_delivery_callback = (isset($router_item) && $router_item) ? $router_item['delivery_callback'] : NULL;
            drupal_deliver_page(MENU_NOT_FOUND, $default_delivery_callback);
            $response->setContent(ob_get_clean());
            $event->setResponse($response);
        }

        if (is_a($exception, 'Symfony\\Component\\HttpKernel\\Exception\\AccessDeniedHttpException')) {
            if ($request->attributes->get('_drupal', false)) {
                $router_item = $request->attributes->get('_router_item', array());
            }
            $default_delivery_callback = (isset($router_item) && $router_item) ? $router_item['delivery_callback'] : NULL;
            drupal_deliver_page(MENU_ACCESS_DENIED, $default_delivery_callback);
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
