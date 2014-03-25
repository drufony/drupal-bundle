<?php

namespace Bangpound\Bundle\DrupalBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class RequestListener
 * @package Bangpound\Drupal\EventListener
 */
class RequestListener
{
    /**
     * @var RequestMatcherInterface Matches Drupal routes.
     */
    private $matcher;

    /**
     * @param RequestMatcherInterface $matcher
     */
    public function __construct(RequestMatcherInterface $matcher)
    {
        $this->matcher = $matcher;
    }

    /**
     * This method is based on menu_execute_active_handler() which is called
     * in Drupal 7's front controller (index.php).
     *
     * @param GetResponseEvent $event
     *
     * @throws AccessDeniedException if the Drupal route is prohibited for
     *                               logged in user.
     *
     * @see menu_execute_active_handler() for analogous function.
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if ($this->matcher->matches($request)) {
            $router_item = menu_get_item();
            $request->attributes->set('_router_item', $router_item);
            if (!$router_item['access']) {
                throw new AccessDeniedException;
            } elseif ($router_item['include_file']) {
                require_once DRUPAL_ROOT .'/'. $router_item['include_file'];
            }
        }
    }
}
