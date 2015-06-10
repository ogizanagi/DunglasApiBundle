<?php

namespace Dunglas\ApiBundle\Mapping;

use Doctrine\Common\Persistence\Mapping\ClassMetadata as DoctrineClassMetadata;
use Dunglas\ApiBundle\Mapping\ClassMetadataInterface as MappingClassMetadataInterface;

class ClassMetadataRegistry
{
    /**
     * @var DoctrineClassMetadata
     */
    private $doctrineMetadata;
    /**
     * @var ClassMetadataInterface
     */
    private $mappingMetadata;

    public function __construct(MappingClassMetadataInterface $mappingMetadata, DoctrineClassMetadata $doctrineMetadata = null)
    {
        $this->doctrineMetadata = $doctrineMetadata;
        $this->mappingMetadata = $mappingMetadata;
    }

    /**
     * Retrieve attribute mapping metadata by its name or converted name
     *
     * @param $name
     *
     * @return AttributeMetadata|null
     */
    public function getAttributeMetadata($name)
    {
        foreach ($this->mappingMetadata->getAttributes() as $attributeMetadata) {
            if ($name === $attributeMetadata->getName() || $name === $attributeMetadata->getConvertedName()) {
                return $attributeMetadata;
            }
        }
    }

    /**
     * @return DoctrineClassMetadata
     */
    public function getDoctrineMetadata()
    {
        return $this->doctrineMetadata;
    }

    /**
     * @return ClassMetadata
     */
    public function getMappingMetadata()
    {
        return $this->mappingMetadata;
    }
}
