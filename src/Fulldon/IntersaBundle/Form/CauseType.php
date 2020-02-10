<?php

namespace Fulldon\IntersaBundle\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\ORM\EntityRepository;

class CauseType extends AbstractType
{
    private $hotes = array();
    private $artistes = array();


    public function __construct($options)
    {

        foreach($options['artistes'] as $key => $option) {
            $this->artistes[] = array(' :'.$key.' MEMBER OF d.categories ', $key, $option);
        }
        foreach($options['hotes'] as $option) {
            $this->hotes[] = array(' :'.$key.' MEMBER OF d.categories ', $key,$option);
        }

    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {


        $builder
            ->add('libelle', 'text', array('required'=>true))
            ->add('libelleEn', 'text', array('required'=>false))
            ->add('lieuConcert', 'text', array('required'=>false))
            ->add('dateConcert', 'date', array('required'=>false))
            ->add('code', 'text', array('required'=>true))
            ->add('cible', 'text', array('required'=>false))
            ->add('codeAnalytique','entity', array(
                'class'    => 'FulldonDonateurBundle:CodeAnalytique',
                'property' => 'name',
                'multiple' => false,
                'expanded' => false,
                'required' => false
            ))
            ->add('codeOccasion','entity', array(
                'class'    => 'FulldonDonateurBundle:CodeOccasion',
                'property' => 'name',
                'multiple' => false,
                'expanded' => false,
                'required' => true
            ))

                ->add('entity','entity', array(
                'class'    => 'FulldonIntersaBundle:Entity',
                'property' => 'name',
                'multiple' => false,
                'expanded' => false,
                'required' => true
            ))
            ->add('dateDebutProjet','date', array(
                'required' => false,
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'attr' => array(
                    'class' => 'datepicker form-control'
                )
            ))
            ->add('dateFinProjet','date', array(
                'required' => false,
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'attr' => array(
                    'class' => 'datepicker form-control'
                )
            ))

            ->add('artiste','entity', array(
                'class'    => 'FulldonDonateurBundle:Donateur',
                'query_builder' => function(EntityRepository $er) {
                        $dql =  $er->createQueryBuilder('d');
                        foreach($this->artistes as $art) {
                            $dql = $dql->orwhere($art[0])
                                   ->setParameter($art[1] ,$art[2]);
                        }
                        return $dql;

                    },
                'property' => 'nom',
                'multiple' => false,
                'expanded' => false,
                'required' => false,
                'empty_value' => 'Choisissez un artiste',
            ))
            ->add('hote','entity', array(
                'class'    => 'FulldonDonateurBundle:Donateur',
                'query_builder' => function(EntityRepository $er) {
                        $dql =  $er->createQueryBuilder('d');
                        foreach($this->hotes as $hote) {
                            $dql = $dql->orwhere($hote[0])
                                ->setParameter($hote[1] ,$hote[2]);
                        }
                        return $dql;
                    },
                'property' => 'nom',
                'multiple' => false,
                'expanded' => false,
                'required' => false,
                'empty_value' => 'Choisissez un hôte'
            ))
            ->add('detailProjet', 'text', array('required'=>false))
            ->add('budgetPrevisionnel', 'number', array('required'=> false))
        ;

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fulldon\DonateurBundle\Entity\Cause'
        ));
    }

    public function getName()
    {
        return 'fulldon_donateurbundle_causetype';
    }
}