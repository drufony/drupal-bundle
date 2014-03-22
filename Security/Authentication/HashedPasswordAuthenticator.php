<?php

namespace Bangpound\Bundle\DrupalBundle\Security\Authentication;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

/**
 * Class HashedPasswordAuthenticator
 * @package Bangpound\Bundle\DrupalBundle\Security\Authentication
 */
class HashedPasswordAuthenticator implements SimplePreAuthenticatorInterface, AuthenticationSuccessHandlerInterface
{

    /**
     * {@inheritDocs}
     */
    public function createToken(Request $request, $providerKey)
    {
        if ('user/reset/%/%/%' === $request->attributes->get('_route')) {
            $page_arguments = explode('/', $request->query->get('q'));
            if (isset($page_arguments[5]) && 'login' === $page_arguments[5]) {
                return new PreAuthenticatedToken(
                    'anon.',
                    array($page_arguments[2], $page_arguments[3], $page_arguments[4]),
                    $providerKey
                );
            }
        }
        throw new AuthenticationException();
    }

    /**
     * {@inheritDocs}
     */
    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        list($uid, $timestamp, $hashedPassword) = $token->getCredentials();

        /** @var $userProvider \Bangpound\Bundle\DrupalBundle\Security\User\UserProvider */
        $username = $userProvider->getUsernameForHashedPassword($uid, $timestamp, $hashedPassword);

        if (!$username) {
            throw new AuthenticationException(
                sprintf(' "%s" does not exist.', serialize($token->getCredentials()))
            );
        }

        $user = $userProvider->loadUserByUsername($username);

        return new PreAuthenticatedToken(
            $user,
            $token->getCredentials(),
            $providerKey,
            $user->getRoles()
        );
    }

    /**
     * {@inheritDocs}
     */
    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }

    /**
     * This is called when an interactive authentication attempt succeeds. This
     * is called by authentication listeners inheriting from
     * AbstractAuthenticationListener.
     *
     * @param Request        $request
     * @param TokenInterface $token
     *
     * @return Response never null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        list(, $timestamp, ) = $token->getCredentials();

        $user = $token->getUser();
        $account = $user->getDrupalUser();
        watchdog('user', 'User %name used one-time login link at time %timestamp.', array('%name' => $account->name, '%timestamp' => $timestamp));
        drupal_set_message(t('You have just used your one-time login link. It is no longer necessary to use this link to log in. Please change your password.'));
        // Let the user's password be changed without the current password check.
        $token = drupal_random_key();
        $_SESSION['pass_reset_' . $account->uid] = $token;

        $qs = http_build_query(array('pass-reset-token' => $token), '', '&');

        return new RedirectResponse('/user/' . $account->uid . '/edit?'. $qs);
    }
}
