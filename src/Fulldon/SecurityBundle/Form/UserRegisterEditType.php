<?php
// src/Fulldon/AdminBundle/Form/UserType.php

namespace Fulldon\SecurityBundle\Form;


use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserRegisterEditType extends UserRegisterType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // On fait appel à la méthode buildForm du parent, qui va ajouter tous les champs à $builder
        parent::buildForm($builder, $options);
        $builder->add('password', 'password', array('required' => false));

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fulldon\SecurityBundle\Entity\User'
        ));
    }

    public function getName()
    {
        return 'fulldon_securitybundle_userregisteredittype';
    }
}