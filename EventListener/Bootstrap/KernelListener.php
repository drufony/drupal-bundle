<?php

namespace Bangpound\Bundle\DrupalBundle\EventListener\Bootstrap;

use Bangpound\Bridge\Drupal\BootstrapEvents;
use Bangpound\Bridge\Drupal\Event\GetCallableForPhase;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class KernelListener
 * @package Bangpound\Bundle\DrupalBundle\EventListener\Bootstrap
 */
class KernelListener implements EventSubscriberInterface
{
    /**
     * @var Current working directory.
     */
    private $cwd;

    /**
     * @var Drupal root directory.
     */
    private $drupalRoot;

    /**
     * @param $drupalRoot
     * @param \Drufony $drufony
     */
    public function __construct($drupalRoot, \Drufony $drufony)
    {
        $this->drupalRoot = $drupalRoot;
        // Only need to inject the Drufony object to instantiated it correctly.
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            BootstrapEvents::GET_CONFIGURATION => array(
                array('onBootstrapConfiguration', 512),
            ),
            KernelEvents::REQUEST => array(
                array('onKernelRequestEarly', 512),
                array('onKernelRequestBeforeSession', 129),
                array('onKernelRequestAfterSession', 127),
                array('onKernelRequestBeforeRouter', 33),
                array('onKernelRequestAfterLocale', 15),
            ),
            KernelEvents::FINISH_REQUEST => array(
                array('restoreWorkingDirectory', -512),
            ),
            ConsoleEvents::EXCEPTION => 'restoreWorkingDirectory',
            ConsoleEvents::TERMINATE => 'restoreWorkingDirectory',
        );
    }

    /**
     * Before bootstrapping, change the working directory.
     *
     * This is restored in restoreWorkingDirectory().
     *
     */
    public function onBootstrapConfiguration(GetCallableForPhase $event)
    {
        $this->cwd = getcwd();
        chdir($this->drupalRoot);
    }

    /**
     * Listener bootstraps Drupal to DRUPAL_BOOTSTRAP_VARIABLES
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequestEarly(GetResponseEvent $event)
    {
        drupal_bootstrap(DRUPAL_BOOTSTRAP_VARIABLES);
        $path = $_GET['q'] = urldecode(substr($event->getRequest()->getPathInfo(), 1));
        $event->getRequest()->query->set('q', $path);
        $GLOBALS['base_url'] = $event->getRequest()->getSchemeAndHttpHost();
    }

    /**
     * Listener bootstraps Drupal to DRUPAL_BOOTSTRAP_SESSION
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequestBeforeSession(GetResponseEvent $event)
    {
        drupal_bootstrap(DRUPAL_BOOTSTRAP_SESSION);
    }

    /**
     * Listener bootstraps Drupal to DRUPAL_BOOTSTRAP_PAGE_HEADER
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequestAfterSession(GetResponseEvent $event)
    {
        if (empty($GLOBALS['user'])) {
            $GLOBALS['user'] = drupal_anonymous_user();
            date_default_timezone_set(drupal_get_user_timezone());
        }
        drupal_bootstrap(DRUPAL_BOOTSTRAP_PAGE_HEADER);
    }

    /**
     * Listener searches for URL aliased Drupal paths.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequestBeforeRouter(GetResponseEvent $event)
    {
        drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
    }

    /**
     * Listener bootstraps Drupal to DRUPAL_BOOTSTRAP_LANGUAGE
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequestAfterLocale(GetResponseEvent $event)
    {
        drupal_bootstrap(DRUPAL_BOOTSTRAP_LANGUAGE);
    }

    /**
     * Listener resets cwd to its value prior to drupal_bootstrap.
     *
     * This is probably unnecessary because the cwd for Symfony web processes
     * is already the web root.
     *
     * @param Event $event
     */
    public function restoreWorkingDirectory(Event $event)
    {
        if ($this->cwd) {
            chdir($this->cwd);
        }
    }
}
