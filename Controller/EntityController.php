<?php

namespace Bangpound\Bundle\DrupalBundle\Controller;

use Bangpound\Bundle\DrupalBundle\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class EntityController
 * @Route("/entity")
 * @package Bangpound\Bundle\DrupalBundle\Controller
 */
class EntityController extends Controller
{
    /**
     * @Route("/{entity_type}/{entity}", name="entity_view", defaults={"view_mode" = "full", "langcode" = null, "page" = null})
     * @Method("GET")
     * @ParamConverter("entity", converter="drupal.entity")
     * @Template
     */
    public function viewAction(Request $request, $entity_type, $entity)
    {
        $view_mode = $request->get('view_mode');
        $langcode = $request->get('langcode');
        $page = $request->get('page');

        list($id, $vid, $bundle) = entity_extract_ids($entity_type, $entity);
        $entities = entity_view($entity_type, array($id => $entity), $view_mode, $langcode, $page);

        return array(
            'label' => entity_label($entity_type, $entity),
            'uri' => entity_uri($entity_type, $entity),
            'entity_type' => $entity_type,
            'id' => $id,
            'vid' => $vid,
            'bundle' => $bundle,
            'entity' => $entity,
            'content' => reset($entities[$entity_type]),
        );
    }
}
