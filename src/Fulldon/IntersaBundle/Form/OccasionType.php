<?php

namespace Fulldon\IntersaBundle\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class OccasionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder

            ->add('name', 'text')
            ->add('code', 'text')
            ->add('cible', 'text')
            ->add('codeCompagne','entity', array(
                'class'    => 'FulldonDonateurBundle:CodeCompagne',
                'property' => 'name',
                'multiple' => false,
                'expanded' => false,
                'required' => false
            ))
            ;

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fulldon\DonateurBundle\Entity\CodeOccasion'
        ));
    }

    public function getName()
    {
        return 'fulldon_donateurbundle_codeoccasiontype';
    }
}