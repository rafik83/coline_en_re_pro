<?php
// src/Fulldon/SecurityBundle/Form/UserEditType.php

namespace Fulldon\SecurityBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserEditType extends UserType // Ici, on hérite de UserType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // On fait appel à la méthode buildForm du parent, qui va ajouter tous les champs à $builder
        parent::buildForm($builder, $options);

        $builder->add('password', 'password', array('required' => false));
    }

    // On modifie cette méthode, car les deux formulaires doivent avoir un nom différent
    public function getName()
    {
        return 'fulldon_securitybundle_useredittype';
    }
}