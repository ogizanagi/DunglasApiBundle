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

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Mapping\AttributeMetadataInterface;
use Dunglas\ApiBundle\Mapping\ClassMetadataFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Order the collection by given properties.
 *
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class OrderFilter extends AbstractFilter
{
    /**
     * @var string Keyword used to retrieve the value.
     */
    private $orderParameter;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param string          $orderParameter  Keyword used to retrieve the value.
     * @param array|null      $properties      List of property names on which the filter will be enabled.
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        ClassMetadataFactoryInterface $classMetadataFactory,
        $orderParameter,
        array $properties = null
    )
    {
        parent::__construct($managerRegistry, $classMetadataFactory, $properties);

        $this->orderParameter = $orderParameter;
    }

    /**
     * {@inheritdoc}
     *
     * Orders collection by properties. The order of the ordered properties is the same as the order specified in the
     * query.
     * For each property passed, if the resource does not have such property or if the order value is different from
     * `asc` or `desc` (case insensitive), the property is ignored.
     */
    public function apply(ResourceInterface $resource, QueryBuilder $queryBuilder, Request $request)
    {
        $doctrineMetadata = $this->getClassMetadata($resource);
        $fieldNames = array_flip($doctrineMetadata->getFieldNames());

        $mappingMetadata = $this->getMappingMetadata($resource);
        /** @var AttributeMetadataInterface[] $metadataByConvertedName */
        $metadataByConvertedName = [];
        foreach ($mappingMetadata->getAttributes() as $attributeMetadata) {
            $metadataByConvertedName[$attributeMetadata->getConvertedName()] = $attributeMetadata;
        }

        foreach ($this->extractProperties($request) as $paramName => $order) {
            if (!isset($metadataByConvertedName[$paramName])) {
                continue;
            }
            $property = $metadataByConvertedName[$paramName]->getName();
            $order = strtoupper($order);

            if ($this->isPropertyEnabled($property) && isset($fieldNames[$property]) && ('ASC' === $order || 'DESC' === $order)) {
                $queryBuilder->addOrderBy(sprintf('o.%s', $property), $order);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(ResourceInterface $resource)
    {
        $description = [];
        $mappingMetadata = $this->getMappingMetadata($resource);

        foreach ($mappingMetadata->getAttributes() as $attributeMetadata) {
            if ($this->isPropertyEnabled($attributeMetadata->getName())) {
                $description[sprintf('%s[%s]', $this->orderParameter, $attributeMetadata->getConvertedName())] = [
                    'property' => $attributeMetadata->getName(),
                    'type' => 'string',
                    'required' => false,
                ];
            }
        }

        return $description;
    }

    /**
     * {@inheritdoc}
     */
    protected function extractProperties(Request $request)
    {
        return $request->query->get($this->orderParameter, []);
    }
}
