<?php

namespace Fulldon\IntersaBundle\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class CompagneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder

            ->add('name', 'text')
            ->add('code', 'text')

            ;

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fulldon\DonateurBundle\Entity\CodeCompagne'
        ));
    }

    public function getName()
    {
        return 'fulldon_donateurbundle_codecompagnetype';
    }
}