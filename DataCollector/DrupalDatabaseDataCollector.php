<?php

namespace Bangpound\Bundle\DrupalBundle\DataCollector;

use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DrupalDatabaseDataCollector
 * @package Bangpound\Bundle\DrupalBundle\DataCollector
 */
class DrupalDatabaseDataCollector extends DataCollector
{
    private $loggers;

    public function __construct()
    {
        @include_once DRUPAL_ROOT . '/includes/database/log.inc';
        foreach (array_keys($GLOBALS['databases']) as $key) {
            \Database::startLog('devel', $key);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = array(
            'queries' => array(),
        );
        foreach (array_keys($GLOBALS['databases']) as $key) {
            $this->data['queries'][$key] = \Database::getLog('devel', $key);
        }
    }

    public function getQueryCount()
    {
        return array_sum(array_map('count', $this->data['queries']));
    }

    public function getQueries()
    {
        return $this->data['queries'];
    }

    public function getTime()
    {
        $time = 0;
        foreach ($this->data['queries'] as $queries) {
            foreach ($queries as $query) {
                $time += $query['time'];
            }
        }

        return $time;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'drupal_db';
    }
}
