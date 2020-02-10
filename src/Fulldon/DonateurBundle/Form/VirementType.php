<?php
// src/Fulldon/AdminBundle/Form/UserType.php

namespace Fulldon\DonateurBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class VirementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder

            ->add('iban', 'text', array('required'=> true))
            ->add('bic', 'text', array('required'=> true))
            ->add('Valider le paiement', 'submit', array('attr' => array("class" =>"btn btn-primary pull-right",)));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fulldon\DonateurBundle\Entity\Virement'
        ));
    }

    public function getName()
    {
        return 'fulldon_donateurbundle_virementtype';
    }
}