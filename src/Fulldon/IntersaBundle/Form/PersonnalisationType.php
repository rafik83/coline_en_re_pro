<?php
/*
 * This file is part of the fulldon project
 *
 * (c) SAMI BOUSSACSOU <boussacsou@intersa.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Fulldon\IntersaBundle\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PersonnalisationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder

            ->add('couleur', 'text', array('required'=> false))
            ->add('presentation', 'ckeditor', array('required'=> false))
            ->add('presentationEn', 'ckeditor', array('required'=> false))
            ->add('adresseAssoc', 'ckeditor', array('required'=> false))
            ->add('telephone', 'text', array('required'=> false))
            ->add('rfMessage', 'ckeditor', array('required'=> false))
            ->add('rfMessageEn', 'ckeditor', array('required'=> false))
            ->add('logo', 'file', array('required'=> false, 'data_class' => null))
            ->add('fondPage', 'file', array('required'=> false, 'data_class' => null))
            ->add('headerPage', 'file', array('required'=> false, 'data_class' => null))
            ->add('persoa', new PersoaType())
            ->add('persod', new PersodType())
            ->add('couleurAdh', 'text', array('required'=> false))
            ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fulldon\IntersaBundle\Entity\Personnalisation'
        ));
    }

    public function getName()
    {
        return 'fulldon_intersabundle_personnalisationtype';
    }
}