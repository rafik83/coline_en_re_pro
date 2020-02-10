<?php
// src/Fulldon/AdminBundle/Form/UserType.php

namespace Fulldon\DonateurBundle\Form;

use Fulldon\DonateurBundle\Form\AbonnementType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

class PndType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder

            ->add('motifDesc', 'textarea', array('required'=> false))
            ->add('motif','entity', array(
                'class'    => 'FulldonDonateurBundle:MotifPnd',
                'property' => 'name',
                'multiple' => false,
                'expanded' => false
            ))
            ;

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fulldon\DonateurBundle\Entity\Pnd'
        ));
    }

    public function getName()
    {
        return 'fulldon_donateurbundle_pndtype';
    }
}