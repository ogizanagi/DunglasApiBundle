<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Doctrine\Orm\Filter;

use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Mapping\ClassMetadataRegistry;
use Dunglas\ApiBundle\Mapping\ClassMetadataRegistryFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * {@inheritdoc}
 *
 * Abstract class with helpers for easing the implementation of a filter.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
abstract class AbstractFilter implements FilterInterface
{
    /**
     * @var array|null
     */
    protected $properties;

    /**
     * @var ClassMetadataRegistryFactory
     */
    protected $classMetadataRegistryFactory;

    public function __construct(
        ClassMetadataRegistryFactory $classMetadataRegistryFactory,
        array $properties = null
    )
    {
        $this->classMetadataRegistryFactory = $classMetadataRegistryFactory;
        $this->properties = $properties;
    }

    /**
     * Gets class metadata for the given resource.
     *
     * @param ResourceInterface $resource
     *
     * @return ClassMetadataRegistry
     */
    protected function getClassMetadataRegistry(ResourceInterface $resource)
    {
        return $this
            ->classMetadataRegistryFactory
            ->getMetadataRegistryFor(
                $resource->getEntityClass(),
                $resource->getNormalizationGroups(),
                $resource->getDenormalizationGroups(),
                $resource->getValidationGroups()
            );
    }

    /**
     * Is the given property enabled?
     *
     * @param string $property
     *
     * @return bool
     */
    protected function isPropertyEnabled($property)
    {
        return null === $this->properties || array_key_exists($property, $this->properties);
    }

    /**
     * Extracts properties to filter from the request.
     *
     * @param Request $request
     *
     * @return array
     */
    protected function extractProperties(Request $request)
    {
        return $request->query->all();
    }
}
