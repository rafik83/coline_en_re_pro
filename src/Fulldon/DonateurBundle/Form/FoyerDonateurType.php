<?php
// src/Fulldon/AdminBundle/Form/UserType.php

namespace Fulldon\SecurityBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FoyerDonateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        
        $builder
            ->add('email','email', array('required'=>false))
            

        ;

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fulldon\DonateurBundle\Entity\FoyerDonateur'
        ));
    }

    public function getName()
    {
        return 'fulldon_donateurbundle_foyerdonateurtype';
    }
}