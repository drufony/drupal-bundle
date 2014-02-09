<?php

namespace Bangpound\Bundle\DrupalBundle\ParamConverter;

use Bangpound\Bundle\DrupalBundle\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class EntityParamConverter
 * @package Bangpound\Bundle\DrupalBundle\ParamConverter
 */
class EntityParamConverter implements ParamConverterInterface
{
    /**
     * {@inheritDoc}
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        $param = $configuration->getName();

        if (!$request->attributes->has($param) || !$request->attributes->has('entity_type')) {
            return false;
        }

        $entityType = $request->attributes->get('entity_type');

        $value   = $request->attributes->get($param);

        if (!$value && $configuration->isOptional()) {
            return false;
        }

        $entities = entity_load($entityType, array($value));
        $entity = reset($entities);

        if (empty($entity)) {
            throw new NotFoundHttpException('Entity not found.');
        }

        $request->attributes->set($param, $entity);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(ParamConverter $configuration)
    {
        if (null === $configuration->getConverter()) {
            return false;
        }

        return 'drupal.entity' === $configuration->getConverter();
    }
}
