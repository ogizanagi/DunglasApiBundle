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
use Dunglas\ApiBundle\Mapping\ClassMetadataRegistryFactory;
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
        ClassMetadataRegistryFactory $classMetadataRegistryFactory,
        $orderParameter,
        array $properties = null
    )
    {
        parent::__construct($classMetadataRegistryFactory, $properties);

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
        $registry = $this->getClassMetadataRegistry($resource);
        $fieldNames = array_flip($registry->getDoctrineMetadata()->getFieldNames());

        foreach ($this->extractProperties($request) as $paramName => $order) {
            if (null === $attributeMetadata = $registry->getAttributeMetadata($paramName)) {
                continue;
            }
            $property = $attributeMetadata->getName();
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
        $mappingMetadata = $this->getClassMetadataRegistry($resource)->getMappingMetadata();

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
