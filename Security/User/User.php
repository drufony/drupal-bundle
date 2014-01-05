<?php

namespace Bangpound\Bundle\DrupalBundle\Security\User;

use Symfony\Component\Security\Core\User\AdvancedUserInterface;

class User implements AdvancedUserInterface, \Serializable
{
    private $user;

    /**
     * @param \stdClass $user
     */
    public function __construct(\stdClass $user)
    {
        $this->user = $user;
        require_once DRUPAL_ROOT . '/' . variable_get('password_inc', 'includes/password.inc');
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        $roles = array('ROLE_USER');
        if ($this->user->uid == 1) {
            $roles[] = 'ROLE_SUPER_ADMIN';
        }
        if (isset($this->user->roles[variable_get('user_admin_role', 0)])) {
            $roles[] = 'ROLE_ADMIN';
        }

        return $roles;
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword()
    {
        return $this->user->pass;
    }

    /**
     * {@inheritdoc}
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->user->name;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
        // noop
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return $this->user->uid;
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $this->user = user_load($serialized);
    }

    /**
     * {@inheritdoc}
     */
    public function isAccountNonExpired()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isAccountNonLocked()
    {
        return (bool) ($this->user->status == 1);
    }

    /**
     * {@inheritdoc}
     */
    public function isCredentialsNonExpired()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return (bool) ($this->user->status == 1);
    }

    /**
     * Return the Drupal user entity for this Symfony user.
     *
     * @return \stdClass
     */
    public function getDrupalUser()
    {
        return $this->user;
    }
}
