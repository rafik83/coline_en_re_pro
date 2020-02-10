<?php
// src/Fulldon/AdminBundle/Form/UserType.php

namespace Fulldon\IntersaBundle\Form;

use Fulldon\SecurityBundle\Entity\User;
use Fulldon\SecurityBundle\Form\UserRegisterType;
use Fulldon\SecurityBundle\Form\UserType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

class SaisieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder

            ->add('nom', 'text')
            ->add('prenom', 'text')
            ->add('civilite', 'choice', array(
                'choices' => array('M' => 'Monsieur', 'Mme' => 'Madame', 'Mlle'=>'Mademoiselle')))
            ->add('email','email', array('required' => false))
            ->add('dateNaissance','birthday', array(
                'required' => false,
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'attr' => array(
                    'class' => 'datepicker-birth form-control'
                )
            ))
            ->add('zipcode','text', array('required' => false))
            ->add('isopays','country', array(
                'required' => false,
                'data' => 'FR'
            ))
            ->add('isoville','text', array(
                'required' => false
            ))
            ->add('categories','entity', array(
                'class'    => 'FulldonDonateurBundle:CategoryDonateur',
                'property' => 'name',
                'multiple' => true,
                'expanded' => false,
                'required' => true

            ))
            ->add('telephoneFixe', 'text', array('required' => false))
            ->add('telephoneMobile', 'text', array('required' => false))
            ->add('adresse1', 'text', array('required' => false))
            ->add('adresse2', 'text', array('required' => false))
            ->add('adresse3', 'text', array('required' => false))
            ->add('adresse4', 'text', array('required' => false))
            ->add('receptionMode','entity', array(
                'class'    => 'FulldonDonateurBundle:ReceptionMode',
                'property' => 'name',
                'multiple' => true,
                'expanded' => true
            ));

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fulldon\DonateurBundle\Entity\Donateur'
        ));
    }

    public function getName()
    {
        return 'fulldon_donateurbundle_saisietype';
    }
}