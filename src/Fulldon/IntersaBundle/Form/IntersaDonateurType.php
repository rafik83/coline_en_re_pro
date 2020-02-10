<?php

namespace Fulldon\IntersaBundle\Form;

use Fulldon\SecurityBundle\Form\UserRegisterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Fulldon\DonateurBundle\Entity\FoyerDonateur;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class IntersaDonateurType extends AbstractType {

    public function __construct($em) {
        $this->em = $em;
    }

//    public function getAllFoyerEmail() {
//        $em = $this->em;
//        $entity = $em->getRepository('FulldonDonateurBundle:FoyerDonateur')->findAll();
//        $foyer = array();
//        foreach ($entity as $value) {
//            $foyer[$value->getId()] = $value->getEmail();
//        }
//      
//
//        return $foyer;
//    }

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder
                ->add('nom', 'text')
                ->add('prenom', 'text', array(
                    'required' => false))
                ->add('civilite', 'choice', array(
                    'choices' => array('M' => 'Monsieur', 'Mme' => 'Madame', 'Melle' => 'Mademoiselle', 'Mr et Mme' => 'Couple'),
                    'required' => false)
                )
                ->add('email', 'email', array(
                    'required' => FALSE
                ))
//                 ->add('email', 'text')
                ->add('dateNaissance', 'birthday', array(
                    'required' => false,
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'attr' => array(
                        'class' => 'form-control'
                    )
                ))
                ->add('zipcode', 'text', array('required' => false))
                ->add('isopays', 'country', array(
                    'required' => false,
                    'data' => 'FR',
                    'preferred_choices' => array('FR')
                ))
                ->add('isoville', 'text', array(
                    'required' => false
                ))
                ->add('adresse1', 'text', array('required' => false))
                ->add('adresse2', 'text', array('required' => false))
                ->add('adresse3', 'text', array('required' => false))
                ->add('adresse4', 'text', array('required' => false))
                ->add('receptionMode', 'entity', array(
                    'class' => 'FulldonDonateurBundle:ReceptionMode',
                    'property' => 'name',
                    'multiple' => true,
                    'expanded' => true
                ))
                ->add('categories', 'entity', array(
                    'class' => 'FulldonDonateurBundle:CategoryDonateur',
                    'property' => 'name',
                    'multiple' => true,
                    'expanded' => false,
                    'required' => true
                ))
                ->add('situationFamiliale', 'entity', array(
                    'class' => 'FulldonDonateurBundle:SituationFamiliale',
                    'property' => 'libelle',
                    'multiple' => false,
                    'expanded' => false,
                    'required' => false
                ))
                ->add('situationProfessionnelle', 'entity', array(
                    'class' => 'FulldonDonateurBundle:SituationProfessionnelle',
                    'property' => 'libelle',
                    'multiple' => false,
                    'expanded' => false,
                    'required' => false
                ))
                ->add('statutDonateur', 'entity', array(
                    'class' => 'FulldonDonateurBundle:StatutDonateur',
                    'property' => 'libelle',
                    'multiple' => false,
                    'expanded' => false,
                    'required' => false
                ))
                ->add('nomEntreprise', 'text', array('required' => false))
                ->add('commentaire', 'textarea', array('required' => false))
                ->add('fourchetteMin', 'text', array('required' => false))
                ->add('fourchetteMax', 'text', array('required' => false))
                ->add('telephoneFixe', 'text', array('required' => false))
                ->add('telephoneMobile', 'text', array('required' => false))
                ->add('foyer', 'hidden')
                ->add('extra', 'hidden', [
                    'mapped' => false,
        ]);
//        ->add('token', HiddenType::class, array(
//            'data' => 'abcdef',
//        ));
//                ->add('foyer', 'entity', array(
//                    'class' => 'FulldonDonateurBundle:FoyerDonateur',
//                    'property' => 'email',
//                    'multiple' => true,
//                    'expanded' => true
////                    'data' => $this->getAllFoyerEmail()
//        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Fulldon\DonateurBundle\Entity\Donateur'
        ));
    }

    public function getName() {
        return 'fulldon_donateurbundle_intersadonateurtype';
    }

}
