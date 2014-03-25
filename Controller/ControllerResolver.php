<?php
namespace Bangpound\Bundle\DrupalBundle\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerResolver as BaseControllerResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

class ControllerResolver extends BaseControllerResolver
{
    /**
     * @var RequestMatcherInterface Matches Drupal routes.
     */
    private $matcher;

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface       $container
     * @param \Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser $parser
     * @param \Psr\Log\LoggerInterface                                        $logger
     * @internal param \Symfony\Component\HttpFoundation\RequestMatcherInterface $matcher
     */
    public function __construct(ContainerInterface $container, ControllerNameParser $parser, LoggerInterface $logger = null)
    {
        parent::__construct($container, $parser, $logger);
        $this->matcher = $container->get('bangpound_drupal.request_matcher');
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getArguments(Request $request, $controller)
    {
        if ($this->matcher->matches($request)) {
            $router_item = $request->attributes->get('_router_item', array());

            return isset($router_item['page_arguments']) ? $router_item['page_arguments'] : array();
        } else {
            return parent::getArguments($request, $controller);
        }
    }
}
