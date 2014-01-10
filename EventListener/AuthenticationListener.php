<?php

namespace Bangpound\Bundle\DrupalBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;

/**
 * Class AuthenticationListener
 * @package Bangpound\Bundle\DrupalBundle\EventListener
 */
class AuthenticationListener
{
    private $requestStack;

    /**
     * @param RequestStack $stack Request stack
     */
    public function __construct(RequestStack $stack)
    {
        $this->requestStack = $stack;
    }

    /**
     * @param AuthenticationFailureEvent $event
     */
    public function onAuthenticationFailure(AuthenticationFailureEvent $event)
    {
        if (is_a($event->getAuthenticationException(), 'Symfony\\Component\\Security\\Core\\Exception\\BadCredentialsException')) {
            drupal_set_message(t('Sorry, unrecognized username or password. <a href="@password">Have you forgotten your password?</a>', array('@password' => url('user/password', array('query' => array('name' => $event->getAuthenticationToken()->getUser()))))), 'error');
        }
    }

    /**
     * @param AuthenticationEvent $event Authentication success event
     */
    public function onAuthenticationSuccess(AuthenticationEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();
        if (is_a($user, 'Bangpound\\Bundle\\DrupalBundle\\Security\\User\\User')) {

            /** @var \Bangpound\Bundle\DrupalBundle\Security\User\User $user */
            $GLOBALS['user'] = $user->getDrupalUser();

            $edit = $this->requestStack->getCurrentRequest()->request->all();

            user_login_finalize($edit);
        }
    }
}
