<?php

namespace App\Form;


use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Entity\Item;
use App\Entity\TodoList;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;


class ItemFormType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        
        $builder->add('name', TextType::class)
                ->add('description', TextareaType::class)
                ->add('state', TextType::class, ['required' => false])
                ->add('todoList', EntityType::class, ['class' => TodoList::class, 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Item::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true
        ]);
    }
}