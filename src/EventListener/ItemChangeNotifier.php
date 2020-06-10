<?php
namespace App\EventListener;

use App\Entity\Item;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Psr\Log\LoggerInterface;

/**
 * @author ndziePatrickJoel
 * This class is used to listen to changes on
 * Item table, whenever an item state goes from 'CREATED' to 'PENDING' and from 'PENDING' to 'COMPLETED'
 */
class ItemChangeNotifier
{
    private $logger;
    private $em;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $em)
    {
        $this->logger = $logger;
        $this->em = $em;
    }

    public function postUpdate(Item $item, LifecycleEventArgs $event)
    {
        $originalData = $this->em->getUnitOfWork()->getOriginalEntityData($item);

        /**
         * If the item state has changed from CREATED to PENDING
         * If the corresponding list is in CREATED state it should transit to PENDING state
         */
        if ('CREATED' == $originalData->getState() && 'PENDING' == $item->getState()) 
        {
            $todoList = $item->getTodoList();

            if ('CREATED' == $todoList->getState()) {
                $todoList->setState('PENDING');
            }
            $this->em->flush();
        }
        /**
         * If the item state has changed from PENDING to COMPLETED we compute the completionRate of the 
         * corresponding list 
         */
        if ('PENDING' == $originalData->getState() && 'COMPLETED' == $item->getState()) 
        {
            //Update the completion time
            $item->setEndedAt(new \DateTime());
            $item->setCompletionTime($item->getEndedAt()->getTimestamp() - $item->getCreatedAt()->getTimestamp());
            
            
            $todoList = $item->getTodoList();
            $todoListItems = $todoList->getItems();

            //Update the completion rate
            if(count($todoListItems) > 0)
            {
                $numberOfItemCompleted = array_reduce($todoListItems, function ($accumulator, $element) 
                {
                    if ('COMPLETED' == $element->getState()) 
                    {
                        $accumulator += 1;
                    }
                    return $accumulator;
                },
                    0
                );
                
                $completionRate = $numberOfItemCompleted / count($todoListItems);
                $todoList->setCompletionRate($completionRate);
                $this->em->flush();
            }
        }
    }
}
