<?php

namespace Bangpound\Bundle\DrupalBundle\EventListener;

use Bangpound\Bridge\Drupal\BootstrapEvents;
use Bangpound\Bridge\Drupal\Event\BootstrapEvent;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class VariablesListener
 * @package Bangpound\Bundle\DrupalBundle\EventListener
 */
class VariablesListener extends ContainerAware implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            BootstrapEvents::FILTER_VARIABLES => array(
                array('onBootstrapVariables'),
            ),
        );
    }

    /**
     * @param BootstrapEvent $event
     */
    public function onBootstrapVariables(BootstrapEvent $event)
    {
        $GLOBALS['conf']['session_inc'] = $this->container->getParameter('bangpound_drupal.conf.session_inc');
        $GLOBALS['conf']['mail_system']['default-system'] = $this->container->getParameter('bangpound_drupal.conf.mail_system.default_system');
    }
}
