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
use Dunglas\ApiBundle\Api\IriConverterInterface;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Mapping\ClassMetadataFactoryInterface;
use Dunglas\ApiBundle\Mapping\ClassMetadataRegistryFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Filter the collection by given properties.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class SearchFilter extends AbstractFilter
{
    /**
     * @var string Exact matching.
     */
    const STRATEGY_EXACT = 'exact';
    /**
     * @var string The value must be contained in the field.
     */
    const STRATEGY_PARTIAL = 'partial';

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;
    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * @param ManagerRegistry               $managerRegistry
     * @param ClassMetadataFactoryInterface $classMetadataFactory
     * @param IriConverterInterface         $iriConverter
     * @param PropertyAccessorInterface     $propertyAccessor
     * @param null|array                    $properties Null to allow filtering on all properties with the exact strategy or a map of property name with strategy.
     */
    public function __construct(
        ClassMetadataRegistryFactory $classMetadataRegistryFactory,
        IriConverterInterface $iriConverter,
        PropertyAccessorInterface $propertyAccessor,
        array $properties = null
    ) {
        parent::__construct($classMetadataRegistryFactory, $properties);

        $this->iriConverter = $iriConverter;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(ResourceInterface $resource, QueryBuilder $queryBuilder, Request $request)
    {
        $registry = $this->getClassMetadataRegistry($resource);
        $doctrineMetadata = $registry->getDoctrineMetadata();
        $fieldNames = array_flip($doctrineMetadata->getFieldNames());

        foreach ($this->extractProperties($request) as $paramName => $value) {
            if (null === $attributeMetadata = $registry->getAttributeMetadata($paramName)) {
                continue;
            }
            $property = $attributeMetadata->getName();

            if (!is_string($value) || !$this->isPropertyEnabled($property)) {
                continue;
            }

            $partial = null !== $this->properties && self::STRATEGY_PARTIAL === $this->properties[$property];

            if (isset($fieldNames[$property])) {
                if ('id' === $property) {
                    $value = $this->getFilterValueFromUrl($value);
                }

                $queryBuilder
                    ->andWhere(sprintf('o.%1$s LIKE :%1$s', $property))
                    ->setParameter($property, $partial ? sprintf('%%%s%%', $value) : $value)
                ;
            } elseif ($doctrineMetadata->isSingleValuedAssociation($property)
                || $doctrineMetadata->isCollectionValuedAssociation($property)
            ) {
                $value = $this->getFilterValueFromUrl($value);

                $queryBuilder
                    ->join(sprintf('o.%s', $property), $property)
                    ->andWhere(sprintf('%1$s.id = :%1$s', $property))
                    ->setParameter($property, $partial ? sprintf('%%%s%%', $value) : $value)
                ;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(ResourceInterface $resource)
    {
        $description = [];
        $registry = $this->getClassMetadataRegistry($resource);
        $doctrineMetadata = $registry->getDoctrineMetadata();
        $mappingMetadata = $registry->getMappingMetadata();

        foreach ($attributes = $mappingMetadata->getAttributes() as $attributeMetadata) {
            $found = isset($this->properties[$attributeMetadata->getName()]);
            if ($found || null === $this->properties) {
                $description[$attributeMetadata->getConvertedName()] = [
                    'property' => $attributeMetadata->getName(),
                    'type' => $doctrineMetadata->getTypeOfField($attributeMetadata->getName()),
                    'required' => false,
                    'strategy' => $found ? $this->properties[$attributeMetadata->getName()] : self::STRATEGY_EXACT,
                ];
            }
        }

        foreach ($doctrineMetadata->getAssociationNames() as $associationName) {
            if ($this->isPropertyEnabled($associationName)) {
                $attributeMappingMetdata = $registry->getAttributeMetadata($associationName);
                $description[$attributeMappingMetdata->getConvertedName()] = [
                    'property' => $associationName,
                    'type' => 'iri',
                    'required' => false,
                    'strategy' => self::STRATEGY_EXACT,
                ];
            }
        }

        return $description;
    }

    /**
     * Gets the ID from an URI or a raw ID.
     *
     * @param string $value
     *
     * @return string
     */
    private function getFilterValueFromUrl($value)
    {
        try {
            if ($item = $this->iriConverter->getItemFromIri($value)) {
                return $this->propertyAccessor->getValue($item, 'id');
            }
        } catch (\InvalidArgumentException $e) {
            // Do nothing, return the raw value
        }

        return $value;
    }
}
