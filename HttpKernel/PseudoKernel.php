<?php

namespace Bangpound\Bundle\DrupalBundle\HttpKernel;

abstract class PseudoKernel implements PseudoKernelInterface
{
    /**
     * @var string
     */
    protected $workingDir;

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var bool
     */
    protected $debug;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var mixed
     */
    protected $startTime;

    /**
     * @var bool
     */
    protected $booted = false;

    /**
     * Constructor.
     *
     * @param string  $environment The environment
     * @param Boolean $debug       Whether to enable debugging or not
     *
     * @api
     */
    public function __construct($environment, $debug)
    {
        $this->environment = $environment;
        $this->debug = (Boolean) $debug;
        $this->workingDir = $this->getWorkingDir();
        $this->name = $this->getName();

        if ($this->debug) {
            $this->startTime = microtime(true);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getName()
    {
        if (null === $this->name) {
            $this->name = preg_replace('/[^a-zA-Z0-9_]+/', '', basename($this->workingDir));
        }

        return $this->name;
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getWorkingDir()
    {
        if (null === $this->workingDir) {
            $r = new \ReflectionObject($this);
            $this->workingDir = str_replace('\\', '/', dirname($r->getFileName()));
        }

        return $this->workingDir;
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getStartTime()
    {
        return $this->debug ? $this->startTime : -INF;
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getCharset()
    {
        return 'UTF-8';
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function boot()
    {
        $this->booted = true;
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function shutdown()
    {
        if (false === $this->booted) {
            return;
        }

        $this->booted = false;
    }
}
