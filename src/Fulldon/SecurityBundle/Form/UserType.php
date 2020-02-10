<?php
// src/Fulldon/AdminBundle/Form/UserType.php

namespace Fulldon\SecurityBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

class UserType extends AbstractType
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
            ->add('roles', 'entity', array(
                'class'    => 'FulldonSecurityBundle:Role',
                'property' => 'name',
                'multiple' => true,
                'expanded' => false
            ))
            ->add('isactive', 'checkbox', array('required' => false))
            ->add('Valider l\'utilisateur', 'submit', array('attr' => array("class" =>"btn btn-primary pull-right",)))
        ;

        // On récupère la factory (usine)
        $factory = $builder->getFormFactory();

        // On ajoute une fonction qui va écouter l'évènement PRE_SET_DATA
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA, // Ici, on définit l'évènement qui nous intéresse
            function(FormEvent $event) use ($factory) { // Ici, on définit une fonction qui sera exécutée lors de l'évènement
                $user = $event->getData();
                // Cette condition est importante, on en reparle plus loin
                if (null === $user) {
                    return; // On sort de la fonction lorsque l'utilisateur vaut null
                }
            }
        );
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fulldon\SecurityBundle\Entity\User'
        ));
    }

    public function getName()
    {
        return 'fulldon_securitybundle_usertype';
    }
}