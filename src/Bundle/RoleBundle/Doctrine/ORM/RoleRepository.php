<?php

declare(strict_types=1);

namespace Bundle\RoleBundle\Doctrine\ORM;

use Bundle\CoreBundle\Doctrine\ORM\EntityRepository as TianosEntityRepository;
use Component\Role\Repository\RoleRepositoryInterface;
use Component\OneToMany\Repository\OneToManyRightRepositoryInterface;

class RoleRepository extends TianosEntityRepository implements RoleRepositoryInterface, OneToManyRightRepositoryInterface
{

    /**
     * {@inheritdoc}
     */
    public function searchBoxRight($q, $offset = 0, $limit = 50): array
    {
        $qb = $this->createQueryBuilder('o')
            ->select('o.id, o.code, o.name, o.createdAt')
            ->andWhere('o.isActive = :active')
            ->andWhere('o.name LIKE :q')
            ->setParameter('active', 1)
            ->setParameter('q', '%' . $q . '%')
            ->getQuery()
        ;

        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);

        return $qb->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function find($id)
    {
        $em = $this->getEntityManager();
        $dql = "
            SELECT role
            FROM RoleBundle:Role role
            WHERE
            role.id = :id AND
            role.isActive = :active
            ";

        $query = $em->createQuery($dql);
        $query->setParameter('active', 1);
        $query->setParameter('id', $id);

        return $query->getOneOrNullResult();
    }

    /**
     * {@inheritdoc}
     */
//    public function find($id)
//    {
//        return $this->createQueryBuilder('o')
//            ->select('o.id, o.code, o.name, o.createdAt')
//            ->andWhere('o.isActive = :active')
//            ->andWhere('o.id = :id')
//            ->setParameter('active', 1)
//            ->setParameter('id', $id)
//            ->getQuery()
//            ->getOneOrNullResult()
//            ;
//    }

    /**
     * {@inheritdoc}
     */
    public function findAll(): array
    {
        return $this->createQueryBuilder('o')
            ->select('o.id, o.code, o.name, o.createdAt')
            ->andWhere('o.isActive = :active')
            ->setParameter('active', 1)
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * {@inheritdoc}
     */
    public function findAllOffsetLimit($offset = 0, $limit = 50): array
    {
        $qb = $this->createQueryBuilder('o')
            ->select('o.id, o.code, o.name, o.createdAt')
            ->andWhere('o.isActive = :active')
            ->setParameter('active', 1)
            ->getQuery()
        ;

        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);

        return $qb->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findByName(string $name, string $locale): array
    {
        return $this->createQueryBuilder('o')
            ->innerJoin('o.translations', 'translation', 'WITH', 'translation.locale = :locale')
            ->andWhere('translation.name = :name')
            ->setParameter('name', $name)
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function findByNamePart(string $phrase, string $locale): array
    {
        return $this->createQueryBuilder('o')
            ->innerJoin('o.translations', 'translation', 'WITH', 'translation.locale = :locale')
            ->andWhere('translation.name LIKE :name')
            ->setParameter('name', '%' . $phrase . '%')
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getResult()
        ;
    }
}
