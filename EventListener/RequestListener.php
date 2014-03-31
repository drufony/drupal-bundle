<?php

namespace Bangpound\Bundle\DrupalBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
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
            $q = $request->get('q');
            $router_item = menu_get_item($q);
            $request->attributes->set('_router_item', $router_item);
        }
    }
}
