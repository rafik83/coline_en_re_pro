<?php
// src/Fulldon/AdminBundle/Form/UserType.php

namespace Fulldon\SecurityBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserRegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        //$db = $this->getDoctrine()->getManager();
        //$repRoles = $db->getRepository('FulldonSecurityBundle:Role');
        //$roles = $repRoles->findAll();
        //$userRoles = array();

        //foreach($roles as $ur)
        //{
        //    $userRoles[$ur->getId()] = $ur->getName();
        //}

        // On ajoute les champs de l'entité que l'on veut à notre formulaire
        $builder
            ->add('username',        'text')
            ->add('password',       'password')

        ;

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fulldon\SecurityBundle\Entity\User'
        ));
    }

    public function getName()
    {
        return 'fulldon_securitybundle_userregistertype';
    }
}