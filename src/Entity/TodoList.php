<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @author ndziePatrickJoel
 * This class represent a TodoList, a TodoList is made up of many item
 * @ORM\Entity
 * @ORM\Table
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="App\Repository\TodoListRepository")
 */
class TodoList{

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
     * @ORM\Column(type="datetime")
     */
    private $createAt;


    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updateAt;


    /**
     * @ORM\OneToMany(
     *  targetEntity="Item",
     *  mappedBy="todoList",
     *  cascade={"persist", "remove"},
     *  fetch="LAZY"
     * )
     * @var array
     */
    private $items;


    /**
     * @ORM\ManyToOne(targetEntity="User") 
     * @var ?User
     */
    private $user;

    /**
     * @ORM\Column(type="string", columnDefinition="ENUM('CREATED', 'PENDING', 'COMPLETED')", options={"default": "PENDING"})
     */
    private $state;


    /**
     * @ORM\Column(type="float", options={"default": 0.0}, nullable=true)
     */
    private $completionRate = 0;


    public function __construct()
    {
        $this->createAt = new \DateTime();
        $this->items = new ArrayCollection();
    }

    /** 
     * Actions to be performed before any update
     * @ORM\PrePersist
     * */
    public function onPrePersist()
    {
        $this->state = 'CREATED';
    }

    public function __toString()
    {
        return $this->name;
    }

    
    /**
     * @return Collection|Item[]
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(Item $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items[] = $item;
            $item->setTodoList($this);
        }

        return $this;
    }

    public function removeItem(Item $item): self
    {
        if ($this->items->contains($item)) {
            $this->items->removeElement($item);
            // set the owning side to null (unless already changed)
            if ($item->getTodoList() === $this) {
                $item->setTodoList(null);
            }
        }

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
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

    public function getCreateAt(): ?\DateTimeInterface
    {
        return $this->createAt;
    }

    public function setCreateAt(\DateTimeInterface $createAt): self
    {
        $this->createAt = $createAt;

        return $this;
    }

    public function getUpdateAt(): ?\DateTimeInterface
    {
        return $this->updateAt;
    }

    public function setUpdateAt(?\DateTimeInterface $updateAt): self
    {
        $this->updateAt = $updateAt;

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

    public function getCompletionRate(): ?float
    {
        return $this->completionRate;
    }

    public function setCompletionRate(?float $completionRate): self
    {
        $this->completionRate = $completionRate;

        return $this;
    }

}


?>