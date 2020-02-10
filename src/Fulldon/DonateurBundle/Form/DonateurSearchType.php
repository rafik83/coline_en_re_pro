<?php
// src/Fulldon/AdminBundle/Form/UserType.php

namespace Fulldon\DonateurBundle\Form;

use Fulldon\SecurityBundle\Entity\User;
use Fulldon\SecurityBundle\Form\UserRegisterType;
use Fulldon\SecurityBundle\Form\UserType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

class DonateurSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('refDonateur', 'text', array('required' => false))
            ->add('nom', 'text', array('required' => false))
            ->add('nomEntreprise', 'text', array('required' => false))
            ->add('prenom', 'text', array('required' => false))
            ->add('civilite', 'choice', array(
                'choices' => array('M' => 'Monsieur', 'Mme' => 'Madame',  'Melle'=>'Mademoiselle', 'Mr et Mme' => 'Couple'),'required' => false))
            ->add('email','email', array('required' => false))
            ->add('dateNaissance','birthday', array(
                'required' => false,
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'attr' => array(
                    'class' => 'datepicker form-control'
                )
            ))
            ->add('zipcode','text', array('required' => false))
            ->add('isopays','country', array(
                'required' => false,
                'preferred_choices' => array('FR'),
                'data' => 'FR',
            ))
            ->add('isoville','text', array(
                'required' => false
            ))

            ->add('adresse1', 'text', array('required' => false))
            ->add('adresse2', 'text', array('required' => false))
            ->add('adresse3', 'text', array('required' => false))
            ->add('adresse4', 'text', array('required' => false))

//            ->add('receptionMode','entity', array(
//                'required' => false,
//                'empty_value'=> '',
//                'class'    => 'FulldonDonateurBundle:ReceptionMode',
//                'property' => 'name',
//                'multiple' => false,
//                'expanded' => false
//            ))
            ->add('categories','entity', array(
                'required' => false,
                'empty_value'=> '',
                'class'    => 'FulldonDonateurBundle:CategoryDonateur',
                'property' => 'name',
                'multiple' => true,
                'expanded' => false
            ))
            ->add('allowRf', 'checkbox', array('required' => false))
        ;

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fulldon\DonateurBundle\Entity\Donateur'
        ));
    }

    public function getName()
    {
        return 'fulldon_donateurbundle_donateursearchtype';
    }
}