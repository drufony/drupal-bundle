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
            return $request->attributes->get('_arguments', array());
        } else {
            return parent::getArguments($request, $controller);
        }
    }
}
