<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Doctrine\Orm;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Dunglas\ApiBundle\Model\DataProviderInterface;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Data provider for the Doctrine ORM.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
class DataProvider implements DataProviderInterface
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var QueryExtensionInterface[]
     */
    private $extensions;

    /**
     * @param ManagerRegistry           $managerRegistry
     * @param QueryExtensionInterface[] $extensions
     */
    public function __construct(ManagerRegistry $managerRegistry, array $extensions = [])
    {
        $this->managerRegistry = $managerRegistry;
        $this->extensions = $extensions;
    }

    /**
     * @param QueryExtensionInterface $extension
     */
    public function addExtension(QueryExtensionInterface $extension)
    {
        $this->extensions[] = $extension;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(ResourceInterface $resource, $id, $fetchData = false)
    {
        $entityClass = $resource->getEntityClass();
        $manager = $this->managerRegistry->getManagerForClass($entityClass);
        $repository = $manager->getRepository($entityClass);
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $repository->createQueryBuilder('o');
        $identifier = $manager->getClassMetadata($resource->getEntityClass())->getIdentifierFieldNames()[0];
        $queryBuilder->where($queryBuilder->expr()->eq('o.'.$identifier, ':id'))->setParameter('id', $id);

        foreach ($this->extensions as $extension) {
            $extension->applySingle($resource, $id, $queryBuilder);
        }

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(ResourceInterface $resource, Request $request)
    {
        $entityClass = $resource->getEntityClass();

        $manager = $this->managerRegistry->getManagerForClass($resource->getEntityClass());
        $repository = $manager->getRepository($entityClass);
        $queryBuilder = $repository->createQueryBuilder('o');

        foreach ($this->extensions as $extension) {
            $extension->apply($resource, $request, $queryBuilder);

            if ($extension instanceof QueryResultExtensionInterface) {
                if ($extension->supportsResult($resource, $request)) {
                    return $extension->getResult($queryBuilder);
                }
            }
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ResourceInterface $resource)
    {
        return null !== $this->managerRegistry->getManagerForClass($resource->getEntityClass());
    }
}
