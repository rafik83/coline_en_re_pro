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

class ConfAvanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder

            ->add('seuilRf', 'text', array('required'=>false))
            ->add('seuilPnd', 'text', array('required'=>false))
            ->add('assocIban', 'text', array('required'=>false))
            ->add('assocBic', 'text', array('required'=>false))
            ->add('assocSepa', 'text', array('required'=>false))
            ->add('assocBanqueName', 'text', array('required'=>false))
            ->add('jourPrelevement', 'text', array('required'=>false))
            ->add('twitterId', 'text', array('required'=>false))
            ->add('facebookId', 'text', array('required'=>false))
            ->add('googleAnalytics', 'text', array('required' => false))
            ->add('maxPerPage', 'text', array('required' => false))
            ->add('generateRf', 'checkbox', array('required' => false))
            ->add('sendStats', 'checkbox', array('required' => false))
            ;

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fulldon\IntersaBundle\Entity\ConfAvance'
        ));
    }

    public function getName()
    {
        return 'fulldon_donateurbundle_confavance';
    }
}