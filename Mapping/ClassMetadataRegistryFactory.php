<?php

namespace Dunglas\ApiBundle\Mapping;

use Doctrine\Common\Persistence\ManagerRegistry;

class ClassMetadataRegistryFactory
{
    /**
     * @var ClassMetadataFactoryInterface
     */
    private $classMetadataFactory;
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    public function __construct(ClassMetadataFactoryInterface $classMetadataFactory, ManagerRegistry $managerRegistry)
    {
        $this->classMetadataFactory = $classMetadataFactory;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param string $class
     * @param array  $normalizationGroups
     * @param array  $denormalizationGroups
     * @param array  $validationGroups
     *
     * @return ClassMetadataRegistry
     */
    public function getMetadataRegistryFor(
        $class,
        array $normalizationGroups = null,
        array $denormalizationGroups = null,
        array $validationGroups = null
    ) {
        $mappingMetadata = $this->classMetadataFactory->getMetadataFor($class, $normalizationGroups, $denormalizationGroups, $validationGroups);

        $doctrineMetadata = !$this->managerRegistry ? null : $this->managerRegistry->getManagerForClass($class)->getClassMetadata($class);

        return new ClassMetadataRegistry($mappingMetadata, $doctrineMetadata);
    }
}
