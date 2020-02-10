<?php
// src/Fulldon/AdminBundle/Form/UserType.php

namespace Fulldon\DonateurBundle\Form;

use Fulldon\DonateurBundle\Form\AbonnementType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\RequestStack;


class EventType extends AbstractType
{


    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('titre', 'text', array('required' => true))
            ->add('description', 'ckeditor', array('required' => false))
            ->add('prixAdh', 'text', array('required' => false))
            ->add('prixNonAdh', 'text', array('required' => true))
            ->add('dateEvent', 'date', array('required' => true,
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'attr' => array(
                    'class' => ' form-control'
                )))
            ->add('cause', 'entity', array(
                'class' => 'FulldonDonateurBundle:Cause',
                'property' => 'libelle',
                'multiple' => false,
                'expanded' => false,
                'required' => false
            ));


    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fulldon\DonateurBundle\Entity\Event'
        ));
    }

    public function getName()
    {
        return 'fulldon_donateurbundle_event_type';
    }
}