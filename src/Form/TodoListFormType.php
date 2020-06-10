<?php

namespace App\Form;


use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use App\Entity\TodoList;
use App\Form\ItemFormType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class TodoListFormType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        
        $builder->add('name', TextType::class)
                ->add('description', TextareaType::class);
        $builder->add('items', CollectionType::class, [
                    'entry_type' => ItemFormType::class,
                    'allow_add' => true,
                    'allow_extra_fields' => true,
                    'required' => false
                ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TodoList::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true
        ]);
    }
}