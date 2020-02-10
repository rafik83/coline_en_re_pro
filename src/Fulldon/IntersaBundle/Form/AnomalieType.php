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

class AnomalieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder

            ->add('commentaire', 'textarea', array('required'=>true))
            ->add('lot', 'text')
            ->add('sequence', 'text')
            ->add('type', 'hidden')
            ;

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fulldon\IntersaBundle\Entity\Anomalie'
        ));
    }

    public function getName()
    {
        return 'fulldon_donateurbundle_anomalietype';
    }
}