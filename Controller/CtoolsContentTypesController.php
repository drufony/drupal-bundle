<?php

namespace Bangpound\Bundle\DrupalBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class CtoolsContentTypesController
 * @Route("/ctools")
 * @package Bangpound\Bundle\DrupalBundle\Controller
 */
class CtoolsContentTypesController extends Controller
{
    public function __construct()
    {
        ctools_include('content');
    }

    /**
     * @Route("/{type}/{subtype}", name="ctools_content_render")
     * @Method({"GET", "POST"})
     * @Template
     * @param  string                  $type
     * @param  string                  $subtype
     * @param  array                   $conf
     * @param  array                   $keywords
     * @param  array                   $args
     * @param  array                   $context
     * @param  string                  $incoming_content
     * @throws BadRequestHttpException
     * @return array
     */
    public function renderAction($type, $subtype, $conf = array(), $keywords = array(), $args = array(), $context = array(), $incoming_content = '')
    {
        $block = ctools_content_render($type, $subtype, $conf, $keywords, $args, $context, $incoming_content);
        if ($block) {
            return (array) $block;
        } else {
            throw new BadRequestHttpException(sprintf('Cannot render ctools content type %s, %s', $type, $subtype));
        }
    }
}
