<?php

namespace Bangpound\Bundle\DrupalBundle\Security\User;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username)
    {
        $account = db_query("SELECT * FROM {users} WHERE name = :name", array(':name' => $username))->fetchObject();
        if ($account) {

            // This is done to unserialize the data member of $user.
            $account->data = unserialize($account->data);

            // Add roles element to $user.
            $account->roles = array();
            $account->roles[DRUPAL_AUTHENTICATED_RID] = 'authenticated user';
            $account->roles += db_query("SELECT r.rid, r.name FROM {role} r INNER JOIN {users_roles} ur ON ur.rid = r.rid WHERE ur.uid = :uid", array(':uid' => $account->uid))->fetchAllKeyed(0, 1);

            return new User($account);
        }
        throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        $GLOBALS['user'] = $user->getDrupalUser();

        date_default_timezone_set(drupal_get_user_timezone());

        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return $class === 'Bangpound\Bundle\DrupalBundle\Security\User\User';
    }

    /**
     * @see user_pass_reset()
     * @param $uid
     * @param $timestamp
     * @param $hashed_pass
     * @internal param $token
     * @return User
     */
    public function getUsernameForHashedPassword($uid, $timestamp, $hashed_pass)
    {
        // Time out, in seconds, until login URL expires. Defaults to 24 hours =
        // 86400 seconds.
        $timeout = variable_get('user_password_reset_timeout', 86400);
        $current = REQUEST_TIME;
        // Some redundant checks for extra security ?
        $users = user_load_multiple(array($uid), array('status' => '1'));
        if ($timestamp <= $current && $account = reset($users)) {
            // No time out for first time login.
            if ($account->login && $current - $timestamp > $timeout) {
            } elseif ($account->uid && $timestamp >= $account->login && $timestamp <= $current && $hashed_pass == user_pass_rehash($account->pass, $timestamp, $account->login)) {
                return $account->name;
            }
        }
    }
}
