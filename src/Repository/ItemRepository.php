<?php

namespace App\Repository;

use App\Entity\TodoList;
use App\Entity\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TodoList|null find($id, $lockMode = null, $lockVersion = null)
 * @method TodoList|null findOneBy(array $criteria, array $orderBy = null)
 * @method TodoList[]    findAll()
 * @method TodoList[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Item::class);
    }

    public function search($query, $limit, $offset, $todoListId): ?array
    {        
        
        $queryBuilder = $this->createQueryBuilder('i');
        $queryBuilder->where("i.name  LIKE :name")
                     ->orWhere('i.description LIKE :description')
                     ->andWhere('i.todoList = :todoList')
                     ->setParameter('name', '%'.$query.'%')
                     ->setParameter('description', '%'.$query.'%')
                     ->setParameter('todoList', $todoListId)
                     ->setFirstResult($offset)
                     ->setMaxResults($limit);

        $searchResult = $queryBuilder->getQuery()->getResult();

        $queryBuilder = $this->createQueryBuilder('t')
                        ->select("count('i.id')")
                        ->where("i.name  LIKE :name")
                        ->orWhere('i.description LIKE :description')
                        ->andWhere('i.todoList = :todoList')
                        ->setParameter('name', '%'.$query.'%')
                        ->setParameter('description', '%'.$query.'%')
                        ->setParameter('todoList', $todoListId);
                     
        $totalResults = $queryBuilder->getQuery()->getSingleScalarResult();
        return [
            "totalResults" => $totalResults,
            "limit" => $limit,
            "offset" => $offset,
            "data" => $searchResult
        ];
        
    }

}
