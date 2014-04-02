<?php

/**
 * @file
 * Contains Drufony.
 */

use Bangpound\Bridge\Drupal\DrupalInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Static Service Container wrapper.
 *
 * @see \Drupal
 */
class Drufony implements ContainerAwareInterface, DrupalInterface
{
    /**
     * The current system version.
     */
    const VERSION = '0.0-dev';

    /**
     * Core API compatibility.
     */
    const CORE_COMPATIBILITY = '7.x';

    /**
     * Core minimum schema version.
     */
    const CORE_MINIMUM_SCHEMA_VERSION = 7000;

    /**
     * The currently active container object.
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected static $container;

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        static::$container = $container;
    }

    /**
     * Returns the currently active global container.
     *
     * @deprecated This method is only useful for the testing environment. It
     * should not be used otherwise.
     *
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    public static function getContainer()
    {
        return static::$container;
    }

    /**
     * Returns true if the service id is defined.
     *
     * @param string $id The service id
     *
     * @return Boolean true if the service id is defined, false otherwise
     */
    public static function has($id)
    {
        return static::$container->has($id);
    }

    /**
     * Gets a service by id.
     *
     * @param string $id The service id
     *
     * @return object The service
     */
    public static function get($id)
    {
        return static::$container->get($id);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public static function getResponse()
    {
        return static::$container->get('legacy.response');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Session\Session
     */
    public static function getSession()
    {
        return static::$container->get('session');
    }
}
