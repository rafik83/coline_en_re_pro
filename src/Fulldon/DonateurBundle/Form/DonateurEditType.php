<?php
// src/Fulldon/AdminBundle/Form/UserType.php

namespace Fulldon\DonateurBundle\Form;


use Fulldon\SecurityBundle\Form\UserRegisterEditType;
use Fulldon\SecurityBundle\Form\UserRegisterType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DonateurEditType extends DonateurType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        // On fait appel à la méthode buildForm du parent, qui va ajouter tous les champs à $builder
        parent::buildForm($builder, $options);
        $builder->remove('Valider');
        $builder->add('user', new UserRegisterEditType());

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fulldon\DonateurBundle\Entity\Donateur'
        ));
    }

    public function getName()
    {
        return 'fulldon_donateurbundle_donateuredittype';
    }
}