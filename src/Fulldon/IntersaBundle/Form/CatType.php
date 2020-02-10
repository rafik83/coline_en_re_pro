<?php

namespace Fulldon\IntersaBundle\Form;

use Fulldon\SecurityBundle\Entity\User;
use Fulldon\SecurityBundle\Form\UserRegisterType;
use Fulldon\SecurityBundle\Form\UserType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

class CatType extends AbstractType
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
            'data_class' => 'Fulldon\DonateurBundle\Entity\CategoryDonateur'
        ));
    }

    public function getName()
    {
        return 'fulldon_donateurbundle_cattype';
    }
}