<?php

namespace Bangpound\Bundle\DrupalBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * Class UserListener
 * @package Bangpound\Bundle\DrupalBundle\EventListener
 */
class UserListener
{
    private $context;

    /**
     * @param SecurityContextInterface $context
     */
    public function __construct(SecurityContextInterface $context = null)
    {
        $this->context = $context;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $token = $this->context->getToken();
        if ($token) {
            $user = $token->getUser();
            if (is_a($user, 'Bangpound\\Bundle\\DrupalBundle\\Security\\User\\User')) {
                /** @var \Bangpound\Bundle\DrupalBundle\Security\User\User $user */
                $GLOBALS['user'] = $user->getDrupalUser();
                date_default_timezone_set(drupal_get_user_timezone());
            }
        }
    }
}
