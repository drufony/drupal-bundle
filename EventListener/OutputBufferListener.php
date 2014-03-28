<?php
/**
 * Created by PhpStorm.
 * User: bjd
 * Date: 3/28/14
 * Time: 12:45 PM
 */

namespace Bangpound\Bundle\DrupalBundle\EventListener;

use Bangpound\LegacyPhp\EventListener\OutputBufferListener as BaseListener;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class OutputBufferListener extends BaseListener implements ContainerAwareInterface
{

    private $container;

    protected function getResponse()
    {
        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $this->container->get('bangpound_drupal.response');
        $response->setContent((string) ob_get_clean());

        return $response;
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
