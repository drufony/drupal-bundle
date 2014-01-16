<?php

namespace Bangpound\Bundle\DrupalBundle\Security\Encoder;

use Bangpound\Bundle\DrupalBundle\HttpKernel\PseudoKernelInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class PasswordEncoder implements PasswordEncoderInterface
{

    public function __construct(PseudoKernelInterface $kernel)
    {
        if (!defined('DRUPAL_ROOT')) {
            define('DRUPAL_ROOT', $kernel->getWorkingDir());
        }
        require_once DRUPAL_ROOT . '/' . variable_get('password_inc', 'includes/password.inc');
    }

    /**
     * Encodes the raw password.
     *
     * @param string $raw  The password to encode
     * @param string $salt The salt
     *
     * @return string The encoded password
     */
    public function encodePassword($raw, $salt)
    {
        return _password_crypt('sha512', $raw, $salt);
    }

    /**
     * Checks a raw password against an encoded password.
     *
     * @param string $encoded An encoded password
     * @param string $raw     A raw password
     * @param string $salt    The salt
     *
     * @return Boolean true if the password is valid, false otherwise
     */
    public function isPasswordValid($encoded, $raw, $salt)
    {
        if (substr($encoded, 0, 2) == 'U$') {
            // This may be an updated password from user_update_7000(). Such hashes
            // have 'U' added as the first character and need an extra md5().
            $stored_hash = substr($encoded, 1);
            $raw = md5($raw);
        } else {
            $stored_hash = $encoded;
        }

        $type = substr($stored_hash, 0, 3);
        switch ($type) {
            case '$S$':
                // A normal Drupal 7 password using sha512.
                $hash = _password_crypt('sha512', $raw, $stored_hash);
                break;
            case '$H$':
                // phpBB3 uses "$H$" for the same thing as "$P$".
            case '$P$':
                // A phpass password generated using md5.  This is an
                // imported password or from an earlier Drupal version.
                $hash = _password_crypt('md5', $raw, $stored_hash);
                break;
            default:
                return FALSE;
        }

        return ($hash && $stored_hash == $hash);
    }
}
