<?php

namespace App\Repository;

use App\Entity\TodoList;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TodoList|null find($id, $lockMode = null, $lockVersion = null)
 * @method TodoList|null findOneBy(array $criteria, array $orderBy = null)
 * @method TodoList[]    findAll()
 * @method TodoList[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TodoListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TodoList::class);
    }



    public function search($query, $limit, $offset): ?array
    {        
        
        $queryBuilder = $this->createQueryBuilder('t');
        $queryBuilder->where("t.name  LIKE :name")
                     ->orWhere('t.description LIKE :description')
                     ->setParameter('name', '%'.$query.'%')
                     ->setParameter('description', '%'.$query.'%')
                     ->setFirstResult($offset)
                     ->setMaxResults($limit);

        $searchResult = $queryBuilder->getQuery()->getResult();

        $queryBuilder = $this->createQueryBuilder('t')
                        ->select("count('t.id')")
                        ->where("t.name  LIKE :name")
                        ->orWhere('t.description LIKE :description')
                        ->setParameter('name', '%'.$query.'%')
                        ->setParameter('description', '%'.$query.'%');
                     
        $totalResults = $queryBuilder->getQuery()->getSingleScalarResult();
        return [
            "totalResults" => $totalResults,
            "limit" => $limit,
            "offset" => $offset,
            "data" => $searchResult
        ];
        
    }

}
