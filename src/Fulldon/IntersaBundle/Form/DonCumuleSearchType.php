<?php

// src/Fulldon/AdminBundle/Form/UserType.php

namespace Fulldon\IntersaBundle\Form;

use Fulldon\SecurityBundle\Entity\User;
use Fulldon\SecurityBundle\Form\UserRegisterType;
use Fulldon\SecurityBundle\Form\UserType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

class DonCumuleSearchType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        parent::buildForm($builder, $options);
        $builder
                ->add('MtMinDon', 'text', [
                    'mapped' => false, 'required' => false,
                ])
                ->add('MtMaxDon', 'text', [
                    'mapped' => false, 'required' => false,
                ])
                ->add('DateMinDon', 'text', [
                    'mapped' => false, 'required' => false,
                ])
                ->add('DateMaxDon', 'text', [
                    'mapped' => false, 'required' => false,
                ])
                ->add('emailDonateur', 'text', [
                    'mapped' => false, 'required' => false,
                ])
                ->add('refDonateur', 'hidden', [
                    'mapped' => false,
                ])

        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Fulldon\DonateurBundle\Entity\Don'
        ));
    }

    public function getName() {
        return 'fulldon_donateurbundle_doncumulesearchtype';
    }

}
