<?php
// src/Fulldon/AdminBundle/Form/UserType.php

namespace Fulldon\DonateurBundle\Form;

use Fulldon\DonateurBundle\Form\DonType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class DonPonctuelType extends DonType
{

    protected $requestStack;

    function __construct(RequestStack $requestStack = null)
    {
        $this->requestStack = $requestStack;
        parent::__construct($requestStack);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {


        parent::buildForm($builder, $options);

        $builder
            ->add('modePaiement','entity', array(
                'class'    => 'FulldonDonateurBundle:ModePaiement',
                'query_builder' => function(EntityRepository $er) {
                        return $er->createQueryBuilder('u')
                            ->where('u.codeSolution  IN (\'cb\',\'cheque\',\'paypal\')');
                    },
                'property' => 'libelle',
                'multiple' => false,
                'expanded' => false
            ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fulldon\DonateurBundle\Entity\Don'
        ));
    }

    public function getName()
    {
        return 'fulldon_donateurbundle_donponctueltype';
    }
}