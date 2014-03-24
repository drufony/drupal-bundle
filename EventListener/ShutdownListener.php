<?php
namespace Bangpound\Bundle\DrupalBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Bangpound\Bundle\DrupalBundle\HttpKernel\ShutdownableInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ShutdownListener
 * @package Bangpound\Drupal\EventListener
 */
class ShutdownListener extends ContainerAware
{
    private $shutdown = false;
    private $requestType = HttpKernelInterface::MASTER_REQUEST;

    /**
     * Request event sets up output buffering after ending all open buffers.
     *
     * This supercedes all ob_ functions in Bootstrap.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->getRequest()->attributes->get('_drupal', false)) {
            if (ob_get_level()) {
                ob_end_clean();
            }
            ob_start();
        }
    }

    /**
     * Prior to calling controller, set shutdown flag to trap exits form controllers.
     *
     * Also capture the request type, though I don't know if it's useful or relevant.
     *
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        if ($event->getRequest()->attributes->get('_drupal', false)) {
            $this->shutdown = true;
            $this->requestType = $event->getRequestType();
            drupal_register_shutdown_function(array($this, 'shutdownFunction'));
        }
    }

    /**
     * Shutdown handler for exceptions
     *
     * An access denied or not found exception might be thrown early, and those
     * should be handled the same way as if the controller exited.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (!$this->shutdown) {
            $exception = $event->getException();
            if (is_a($exception, 'Symfony\\Component\\HttpKernel\\Exception\\AccessDeniedHttpException') ||
                is_a($exception, 'Symfony\\Component\\HttpKernel\\Exception\\NotFoundHttpException'))
            {
                $request = $event->getRequest();
                $request->attributes->set('_drupal', true);
                $this->shutdown = true;
                $this->requestType = $event->getRequestType();
                drupal_register_shutdown_function(array($this, 'shutdownFunction'));
            }
        } elseif ($event->getRequest()->attributes->get('_drupal', false) && $this->shutdown) {
            $this->shutdown = false;
        }
    }

    /**
     * All kernel events after KernelEvents::CONTROLLER should remind the shutdown
     * controller that it is not needed because the request is being handled correctly.
     *
     * @param KernelEvent $event
     */
    public function onKernelPostController(KernelEvent $event)
    {
        if ($event->getRequest()->attributes->get('_drupal', false)) {
            $this->shutdown = false;
        }
    }

    /**
     * All kernel events after KernelEvents::CONTROLLER should remind the shutdown
     * controller that it is not needed because the request is being handled correctly.
     *
     * @param PostResponseEvent $event
     */
    public function onKernelTerminate(PostResponseEvent $event)
    {
        if ($event->getRequest()->attributes->get('_drupal', false)) {
            $this->shutdown = false;
        }
    }

    /**
     * The shutdown function runs HttpKernel::handleRaw() since after the controller was
     * invoked.
     */
    public function shutdownFunction()
    {
        /** @var RequestStack $request_stack */
        $request_stack = $this->container->get('request_stack');
        $request = $request_stack->getCurrentRequest();
        if ($request && $this->shutdown && $request->attributes->get('_drupal', false)) {
            /**
             * @var Request               $request
             * @var Response              $response
             * @var ShutdownableInterface $kernel
             */
            $response = $this->container->get('bangpound_drupal.response');
            $kernel   = $this->container->get('http_kernel');

            // If we're at this point, we know the callback result is a text string that
            // needs to be turned into a Response. No need to trigger KernelEvents::VIEW.
            $page_callback_result = ob_get_clean();
            $response->setContent($page_callback_result);

            $kernel->shutdown($request, $response, $this->requestType);
        }
    }
}
