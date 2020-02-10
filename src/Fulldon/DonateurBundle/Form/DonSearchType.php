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

class DonSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('id', 'text', array('required' => false))
            ->add('montant', 'text', array('required' => false))
            ->add('num_cheque','text', array('required' => false))
            ->add('date_don', 'date' , array(
                'required' => false,
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'attr' => array(
                    'class' => 'datepicker form-control'
                )
            ))
            ->add('mode_paiement','text', array('required' => false))
            ->add('cause', 'text', array('required' => false))
            ->add('num_reçu', 'text', array('required' => false))
           ;

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fulldon\DonateurBundle\Entity\Don'
        ));
    }

    public function getName()
    {
        return 'fulldon_donateurbundle_donsearchtype';
    }
}