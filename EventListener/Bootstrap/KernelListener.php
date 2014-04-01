<?php

namespace Bangpound\Bundle\DrupalBundle\EventListener\Bootstrap;

use Bangpound\Bundle\DrupalBundle\Globals;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class KernelListener
 * @package Bangpound\Bundle\DrupalBundle\EventListener\Bootstrap
 */
class KernelListener implements EventSubscriberInterface
{
    /**
     * @var string Current working directory
     */
    private $cwd;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array(
                array('onKernelRequestEarly', 512),
                array('onKernelRequestBeforeSession', 129),
                array('onKernelRequestAfterSession', 127),
                array('onKernelRequestBeforeRouter', 33),
                array('onKernelRequestAfterLocale', 15),
            ),
            KernelEvents::FINISH_REQUEST => array(
                array('onKernelPostController', -512),
            ),
        );
    }

    /**
     * Listener bootstraps Drupal to DRUPAL_BOOTSTRAP_VARIABLES
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequestEarly(GetResponseEvent $event)
    {
//        $request = $event->getRequest();

        if ($event->isMasterRequest()) {
            drupal_bootstrap(DRUPAL_BOOTSTRAP_VARIABLES);
        }

//        // @see drupal_environment_initialize();
//        $path = $_GET['q'] = urldecode(substr($request->getPathInfo(), 1));
//        $request->query->set('q', $path);
//
//        $GLOBALS['base_url'] = $request->getSchemeAndHttpHost();
    }

    /**
     * Listener bootstraps Drupal to DRUPAL_BOOTSTRAP_SESSION
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequestBeforeSession(GetResponseEvent $event)
    {
        if ($event->isMasterRequest()) {
            drupal_bootstrap(DRUPAL_BOOTSTRAP_SESSION);
        }
    }

    /**
     * Listener bootstraps Drupal to DRUPAL_BOOTSTRAP_PAGE_HEADER
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequestAfterSession(GetResponseEvent $event)
    {
        if ($event->isMasterRequest()) {
            if (empty($GLOBALS['user'])) {
                $GLOBALS['user'] = drupal_anonymous_user();
                date_default_timezone_set(drupal_get_user_timezone());
            }

            // This is basically noop.
            drupal_bootstrap(DRUPAL_BOOTSTRAP_PAGE_HEADER);
        }
    }

    /**
     * Listener searches for URL aliased Drupal paths.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequestBeforeRouter(GetResponseEvent $event)
    {
        if ($event->isMasterRequest()) {
            drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
        }
    }

    /**
     * Listener bootstraps Drupal to DRUPAL_BOOTSTRAP_LANGUAGE
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequestAfterLocale(GetResponseEvent $event)
    {
        if ($event->isMasterRequest()) {
            drupal_bootstrap(DRUPAL_BOOTSTRAP_LANGUAGE);
        }
    }

    /**
     * Listener resets cwd to its value prior to drupal_bootstrap.
     *
     * This is probably unnecessary because the cwd for Symfony web processes
     * is already the web root.
     *
     * @param FinishRequestEvent $event
     */
    public function onKernelPostController(FinishRequestEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            chdir($this->cwd);
        }
    }
}
