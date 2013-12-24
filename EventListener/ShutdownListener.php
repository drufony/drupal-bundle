<?php
namespace Bangpound\Bundle\DrupalBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\EventDispatcher\Event;
use Bangpound\Bundle\DrupalBundle\HttpKernel\ShutdownableInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ShutdownListener
 * @package Bangpound\Drupal\EventListener
 */
class ShutdownListener implements ContainerAwareInterface
{
    private $container;
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
            drupal_register_shutdown_function([$this, 'shutdownFunction']);
        }
    }

    /**
     * All kernel events after KernelEvents::CONTROLLER should remind the shutdown
     * controller that it is not needed because the request is being handled correctly.
     *
     * @param Event $event
     */
    public function onKernelPostController(Event $event)
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
        if ($this->shutdown) {
            /**
             * @var Request               $request
             * @var Response              $response
             * @var ShutdownableInterface $kernel
             */
            $request  = $this->container->get('request_stack')->getCurrentRequest();
            $response = $this->container->get('bangpound_drupal.response');
            $kernel   = $this->container->get('http_kernel');

            // If we're at this point, we know the callback result is a text string that
            // needs to be turned into a Response. No need to trigger KernelEvents::VIEW.
            $page_callback_result = ob_get_clean();
            $response->setContent($page_callback_result);

            $kernel->shutdown($request, $response, $this->requestType);
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
