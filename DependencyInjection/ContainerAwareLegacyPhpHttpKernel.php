<?php

namespace Bangpound\Bundle\DrupalBundle\DependencyInjection;

use Bangpound\Bundle\DrupalBundle\HttpKernel\ShutdownableInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DependencyInjection\ContainerAwareHttpKernel;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\DependencyInjection\Scope;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds a managed request scope.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ContainerAwareLegacyPhpHttpKernel extends ContainerAwareHttpKernel implements ShutdownableInterface
{

    /**
     * {@inheritdoc}
     */
    public function shutdown(Request $request, Response $response, $type = HttpKernelInterface::MASTER_REQUEST)
    {
        $response = $this->filterResponse($response, $request, $type);
        $response->send();
        $this->terminate($request, $response);
    }

    /**
     * {@inheritdoc}
     */
    private function filterResponse(Response $response, Request $request, $type)
    {
        $event = new FilterResponseEvent($this, $request, $type, $response);

        $this->dispatcher->dispatch(KernelEvents::RESPONSE, $event);

        $this->finishRequest($request, $type);

        return $event->getResponse();
    }

    /**
     * {@inheritdoc}
     */
    private function finishRequest(Request $request, $type)
    {
        $this->dispatcher->dispatch(KernelEvents::FINISH_REQUEST, new FinishRequestEvent($this, $request, $type));
        $this->requestStack->pop();
    }
}
