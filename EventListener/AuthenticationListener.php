<?php

namespace Bangpound\Bundle\DrupalBundle\EventListener;

use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;

/**
 * Class AuthenticationListener
 * @package Bangpound\Bundle\DrupalBundle\EventListener
 */
class AuthenticationListener
{

    /**
     * @param AuthenticationFailureEvent $event
     */
    public function onAuthenticationFailure(AuthenticationFailureEvent $event)
    {
        if (is_a($event->getAuthenticationException(), 'Symfony\\Component\\Security\\Core\\Exception\\BadCredentialsException')) {
            drupal_set_message(t('Sorry, unrecognized username or password. <a href="@password">Have you forgotten your password?</a>', array('@password' => url('user/password', array('query' => array('name' => $event->getAuthenticationToken()->getUser()))))), 'error');
        }
    }
}
