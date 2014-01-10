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
}
