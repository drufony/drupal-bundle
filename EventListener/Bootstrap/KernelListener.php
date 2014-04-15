<?php

namespace Bangpound\Bundle\DrupalBundle\EventListener\Bootstrap;

use Bangpound\Bridge\Drupal\BootstrapEvents;
use Symfony\Component\Console\ConsoleEvents;
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
     * @var string Current working directory.
     */
    private $cwd;

    /**
     * @var string Drupal root directory.
     */
    private $drupalRoot;

    /**
     * @param string   $drupalRoot
     * @param \Drufony $drufony
     */
    public function __construct($drupalRoot, \Drufony $drufony)
    {
        $this->drupalRoot = $drupalRoot;
        $this->cwd = getcwd();
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
            ConsoleEvents::COMMAND => 'saveWorkingDirectory',
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
    public function onBootstrapConfiguration()
    {
        if (!drupal_is_cli()) {
            chdir($this->drupalRoot);
        }
    }

    public function saveWorkingDirectory()
    {
        $this->cwd = getcwd();
    }

    public function __destruct()
    {
        $this->restoreWorkingDirectory();
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
     * @internal param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     */
    public function onKernelRequestBeforeSession()
    {
        drupal_bootstrap(DRUPAL_BOOTSTRAP_SESSION);
    }

    /**
     * Listener bootstraps Drupal to DRUPAL_BOOTSTRAP_PAGE_HEADER
     *
     * @internal param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     */
    public function onKernelRequestAfterSession()
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
     * @internal param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     */
    public function onKernelRequestBeforeRouter()
    {
        drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
    }

    /**
     * Listener bootstraps Drupal to DRUPAL_BOOTSTRAP_LANGUAGE
     *
     * @internal param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     */
    public function onKernelRequestAfterLocale()
    {
        drupal_bootstrap(DRUPAL_BOOTSTRAP_LANGUAGE);
    }

    /**
     * Listener resets cwd to its value prior to drupal_bootstrap.
     *
     * This is probably unnecessary because the cwd for Symfony web processes
     * is already the web root.
     *
     * @internal param \Symfony\Component\EventDispatcher\Event $event
     */
    public function restoreWorkingDirectory()
    {
        if ($this->cwd) {
            chdir($this->cwd);
        }
    }
}
