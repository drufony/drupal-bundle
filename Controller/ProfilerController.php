<?php

namespace Bangpound\Bundle\DrupalBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ProfilerController
 * @package Bangpound\Bundle\DrupalBundle\Controller
 */
class ProfilerController extends ContainerAware
{
    /**
     * Renders the profiler panel for the given token.
     *
     * @param string  $token          The profiler token
     * @param string  $connectionName
     * @param integer $query
     *
     * @return Response A Response instance
     */
    public function explainAction($token, $connectionName, $query)
    {
        /** @var $profiler \Symfony\Component\HttpKernel\Profiler\Profiler */
        $profiler = $this->container->get('profiler');
        $profiler->disable();

        $profile = $profiler->loadProfile($token);
        $queries = $profile->getCollector('drupal')->getQueries();

        if (!isset($queries[$connectionName][$query])) {
            return new Response('This query does not exist.');
        }

        $query = $queries[$connectionName][$query];

        try {
            $results = db_query('EXPLAIN ' . $query['query'], (array) $query['args'])->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return new Response('This query cannot be explained.');
        }

        return $this->container->get('templating')->renderResponse('BangpoundDrupalBundle:Collector:explain.html.twig', array(
            'data' => $results,
            'query' => $query,
        ));
    }
}
