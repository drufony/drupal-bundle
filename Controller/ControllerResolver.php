<?php
namespace Bangpound\Bundle\DrupalBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\ControllerResolver as BaseControllerResolver;
use Symfony\Component\HttpFoundation\Request;

class ControllerResolver extends BaseControllerResolver
{
    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getArguments(Request $request, $controller)
    {
        if ($request->attributes->get('_drupal', false)) {
            $router_item = $request->attributes->get('_router_item', array());

            return isset($router_item['page_arguments']) ? $router_item['page_arguments'] : array();
        } else {
            return parent::getArguments($request, $controller);
        }
    }
}
