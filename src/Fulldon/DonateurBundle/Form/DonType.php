<?php
// src/Fulldon/AdminBundle/Form/UserType.php

namespace Fulldon\DonateurBundle\Form;

use Fulldon\DonateurBundle\Form\AbonnementType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\RequestStack;


class DonType extends AbstractType
{
    protected $requestStack;

    function __construct(RequestStack $requestStack = null)
    {
        $this->requestStack = $requestStack;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('montant', 'money', array('required'=> false))
            ->add('modePaiement','entity', array(
                'class'    => 'FulldonDonateurBundle:ModePaiement',
                'property' => 'libelle',
                'multiple' => false,
                'expanded' => false
            ));
        if($this->requestStack->getCurrentRequest()->getLocale() == 'fr')
        {
            $builder
                ->add('cause','entity', array(
                    'class'    => 'FulldonDonateurBundle:Cause',
                    'query_builder' => function(EntityRepository $er) {
                            gc_collect_cycles();
                            return $er->createQueryBuilder('u')
                                ->where('u.visibleOnDonateur = true and u.actif = true and u.libelle is not null');
                        },
                    'property' => 'libelle',
                    'multiple' => false,
                    'expanded' => false
                ));
        } elseif($this->requestStack->getCurrentRequest()->getLocale() == 'en') {
            $builder
                ->add('cause','entity', array(
                    'class'    => 'FulldonDonateurBundle:Cause',
                    'query_builder' => function(EntityRepository $er) {
                            gc_collect_cycles();
                            return $er->createQueryBuilder('u')
                                ->where('u.visibleOnDonateur = true and u.actif = true and (u.libelleEn is not null and u.libelleEn <> \'\')');
                        },
                    'property' => 'libelleEn',
                    'multiple' => false,
                    'expanded' => false
                ));
        }

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fulldon\DonateurBundle\Entity\Don'
        ));
    }

    public function getName()
    {
        return 'fulldon_donateurbundle_dontype';
    }
}