<?php

namespace Bangpound\Bundle\DrupalBundle\EventListener;

use Bangpound\Bridge\Drupal\Event\BootstrapEvent;
use Bangpound\Bundle\DrupalBundle\Globals;
use Bangpound\Bundle\DrupalBundle\PseudoKernel;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class BootstrapListener
 * @package Bangpound\Bundle\DrupalBundle\EventListener
 */
class BootstrapListener
{
    /**
     * @var PseudoKernel
     */
    private $kernel;

    /**
     * @var string Current working directory
     */
    private $cwd;

    /**
     * @var RequestMatcherInterface Matches Drupal routes.
     */
    private $matcher;

    /**
     * @param \Bangpound\Bundle\DrupalBundle\Globals $globalz
     * @param PseudoKernel                           $kernel
     * @param RequestMatcherInterface                $matcher
     */
    public function __construct(Globals $globalz, PseudoKernel $kernel, RequestMatcherInterface $matcher)
    {
        // Abandon the Globals object. It just needs to be instantiated.

        $this->kernel = $kernel;
        $this->matcher = $matcher;
        if (!defined('DRUPAL_ROOT')) {
            define('DRUPAL_ROOT', $this->kernel->getWorkingDir());
        }
    }

    /**
     * Listener prepares Drupal bootstrap environment.
     *
     * @param \Bangpound\Bridge\Drupal\Event\BootstrapEvent $event
     */
    public function onBootstrapConfiguration(BootstrapEvent $event)
    {
        drupal_override_server_variables(array('url' => $this->kernel->getUri()));
    }

    /**
     * Listener bootstraps Drupal to DRUPAL_BOOTSTRAP_VARIABLES
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequestEarly(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            $this->cwd = getcwd();
            chdir(DRUPAL_ROOT);

            // When clean URLs are enabled, emulate ?q=foo/bar using REQUEST_URI. It is
            // not possible to append the query string using mod_rewrite without the B
            // flag (this was added in Apache 2.2.8), because mod_rewrite unescapes the
            // path before passing it on to PHP. This is a problem when the path contains
            // e.g. "&" or "%" that have special meanings in URLs and must be encoded.
            //
            // @see drupal_environment_initialize();
            $path = $_GET['q'] = urldecode(substr($request->getPathInfo(), 1));
            $request->query->set('q', $path);

            $GLOBALS['base_url'] = $request->getSchemeAndHttpHost();

            drupal_bootstrap(DRUPAL_BOOTSTRAP_VARIABLES);
        }
    }

    /**
     * Listener bootstraps Drupal to DRUPAL_BOOTSTRAP_SESSION
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequestBeforeSession(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
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
        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
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
        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
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
        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            drupal_bootstrap(DRUPAL_BOOTSTRAP_LANGUAGE);
        }
    }

    /**
     * Completes the remaining parts of DRUPAL_BOOTSTRAP_FULL that conflict
     * with the Symfony router.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequestAfterFirewall(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            // Prior to invoking hook_init(), initialize the theme (potentially a custom
            // one for this page), so that:
            // - Modules with hook_init() implementations that call theme() or
            //   theme_get_registry() don't initialize the incorrect theme.
            // - The theme can have hook_*_alter() implementations affect page building
            //   (e.g., hook_form_alter(), hook_node_view_alter(), hook_page_alter()),
            //   ahead of when rendering starts.
            menu_set_custom_theme();
            drupal_theme_initialize();
            module_invoke_all('init');
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
