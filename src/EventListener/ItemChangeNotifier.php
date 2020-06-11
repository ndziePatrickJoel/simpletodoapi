<?php
namespace App\EventListener;

use App\Entity\Item;
use App\Entity\TodoList;
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

        $changeSet = $this->em->getUnitOfWork()->getEntityChangeSet($item);

        if(!array_key_exists('state', $changeSet) || $changeSet['state'][0] == $changeSet['state'][1])
        {
            return;
        }

        $initialState = $changeSet['state'][0];
        $finalState = $changeSet['state'][1];

        $this->logger->info("Original state  $initialState , Final state $finalState");

        /**
         * If the item state has changed from CREATED to PENDING
         * If the corresponding list is in CREATED state it should transit to PENDING state
         */
        if (Item::CREATED_STATE == $initialState && Item::PENDING_STATE == $finalState)
        {
            $todoList = $item->getTodoList();

            if (TodoList::CREATED_STATE == $todoList->getState()) {
                $todoList->setState(TodoList::PENDING_STATE);
            }
            $this->em->flush();
        }
        /**
         * If the item state has changed from PENDING to COMPLETED we compute the completionRate of the 
         * corresponding list 
         */
        if (Item::PENDING_STATE == $initialState && Item::COMPLETED_STATE == $finalState) 
        {
            //Update the completion time
            $item->setEndedAt(new \DateTime());
            $item->setCompletionTime($item->getEndedAt()->getTimestamp() - $item->getCreatedAt()->getTimestamp());
            
            
            $todoList = $item->getTodoList();
            $todoListItems = $todoList->getItems();

            $this->logger->info("Number of todoListItems". $todoListItems->count());

            //Update the completion rate
            if($todoListItems->count() > 0)
            {
                $numberOfItemCompleted = array_reduce($todoListItems->toArray(), function ($accumulator, $element) 
                {
                    if (Item::COMPLETED_STATE == $element->getState()) 
                    {
                        $accumulator += 1;
                    }
                    return $accumulator;
                },
                    0
                );

                $this->logger->info($numberOfItemCompleted);
                
                $completionRate = $numberOfItemCompleted / count($todoListItems);
                $todoList->setCompletionRate($completionRate);
                if($numberOfItemCompleted == $todoListItems->count())
                {
                    $todoList->setState(TodoList::COMPLETED_STATE);
                }
                $this->em->flush();
            }
        }
    }
}
