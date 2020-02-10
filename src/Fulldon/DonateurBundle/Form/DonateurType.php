<?php

// src/Fulldon/AdminBundle/Form/UserType.php

namespace Fulldon\DonateurBundle\Form;

use Fulldon\SecurityBundle\Entity\User;
use Fulldon\SecurityBundle\Form\UserRegisterType;
use Fulldon\SecurityBundle\Form\UserType;
use Fulldon\SecurityBundle\Form\FoyerDonateurType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Fulldon\DonateurBundle\Entity\FoyerDonateur;
use Doctrine\ORM\EntityRepository;

class DonateurType extends AbstractType {

    public function __construct($em) {
        $this->em = $em;
    }

    public function getAllFoyerEmail() {
        $em = $this->em;
        $entity = $em->getRepository('FulldonDonateurBundle:FoyerDonateur')->findAll();
        $foyer = array();
        foreach ($entity as $value) {
            $foyer[$value->getId()] = $value->getEmail();
        }
        var_dump($foyer);
        
        return $foyer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder
                ->add('nom', 'text')
                ->add('prenom', 'text')
                ->add('civilite', 'choice', array(
                    'choices' => array('M' => 'Monsieur', 'Mme' => 'Madame', 'Melle' => 'Madmoiselle', 'Mr et Mme' => 'Couple'),
                    'translation_domain' => 'messages'
                        )
                )
                ->add('email', 'email', array('required' => false))
//                ->add('email', 'text')
                ->add('dateNaissance', 'birthday', array(
                    'required' => false,
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'attr' => array(
                        'class' => ' form-control'
                    )
                ))
                ->add('zipcode', 'text', array('required' => false))
                ->add('isopays', 'country', array(
                    'required' => false,
                    'data' => 'FR'
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
                ->add('telephoneFixe', 'text', array('required' => false))
                ->add('telephoneMobile', 'text', array('required' => false))
                ->add('user', new UserRegisterType());
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
        return 'fulldon_donateurbundle_donateurtype';
    }

}
