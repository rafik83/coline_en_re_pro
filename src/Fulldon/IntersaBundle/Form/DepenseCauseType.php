<?php

namespace Fulldon\IntersaBundle\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class DepenseCauseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder

            ->add('objetDepense', 'text')
            ->add('montant', 'number')
            ->add('cause','entity', array(
                'class'    => 'FulldonDonateurBundle:Cause',
                'property' => 'libelle',
                'multiple' => false,
                'expanded' => false,
                'required' => false
            ))
            ;

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fulldon\DonateurBundle\Entity\DepenseCause'
        ));
    }

    public function getName()
    {
        return 'fulldon_donateurbundle_depensecausetype';
    }
}