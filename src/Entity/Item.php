<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @author ndziePatrickJoel
 * This class represent a TodoList input
 * @ORM\Entity
 * @ORM\Table
 * @ORM\HasLifecycleCallbacks()
 */
class Item{

    public  const CREATED_STATE = 'CREATED';
    public  const PENDING_STATE = 'PENDING';
    public  const COMPLETED_STATE = 'COMPLETED';

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=512)
     * 
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;


    /**
     * @ORM\Column(type="string", columnDefinition="ENUM('CREATED', 'PENDING', 'COMPLETED')", options={"default": "PENDING"})
     */
    private $state;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;


    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $startedAt;


    /**
     * @ORM\ManyToOne(targetEntity="TodoList") 
     * @var ?TodoList
     */
    private $todoList;


    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $endedAt;


    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $expectedEndDate;


    /**
     * The number of seconds used to complete the task
     * @ORM\Column(type="integer", nullable=true)
     */
    private $completionTime;


    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;


    /** 
     * Actions to be performed before any update
     * @ORM\PreUpdate 
     * */
    public function onPreUpdate()
    {
        $this->updatedAt = new \DateTime();

    }

    /** 
     * Actions to be performed before any update
     * @ORM\PrePersist
     * */
    public function onPrePersist()
    {
        $this->state = 'CREATED';
    }

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }


    public function __toString()
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeInterface $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getEndedAt(): ?\DateTimeInterface
    {
        return $this->endedAt;
    }

    public function setEndedAt(?\DateTimeInterface $endedAt): self
    {
        $this->endedAt = $endedAt;

        return $this;
    }

    public function getExpectedEndDate(): ?\DateTimeInterface
    {
        return $this->expectedEndDate;
    }

    public function setExpectedEndDate(?\DateTimeInterface $expectedEndDate): self
    {
        $this->expectedEndDate = $expectedEndDate;

        return $this;
    }

    public function getCompletionTime(): ?int
    {
        return $this->completionTime;
    }

    public function setCompletionTime(?int $completionTime): self
    {
        $this->completionTime = $completionTime;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getTodoList(): ?TodoList
    {
        return $this->todoList;
    }

    public function setTodoList(?TodoList $todoList): self
    {
        $this->todoList = $todoList;

        return $this;
    }

    

}


?>