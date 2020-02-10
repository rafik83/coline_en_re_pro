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

class PersodType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder


            ->add('dc2title1', 'text', array('required'=> false))
            ->add('dc1footertitle', 'text', array('required'=> false))
            ->add('dc2footertitle', 'text', array('required'=> false))
            ->add('dc1footer', 'ckeditor', array('required'=> false))
            ->add('dc2footer', 'ckeditor', array('required'=> false))
            ->add('dc3block1', 'ckeditor', array('required'=> false))
            ->add('dc3secure', 'ckeditor', array('required'=> false))
            ->add('dc2title1En', 'text', array('required'=> false))
            ->add('dc1footertitleEn', 'text', array('required'=> false))
            ->add('dc2footertitleEn', 'text', array('required'=> false))
            ->add('dc1footerEn', 'ckeditor', array('required'=> false))
            ->add('dc2footerEn', 'ckeditor', array('required'=> false))
            ->add('dc3block1En', 'ckeditor', array('required'=> false))
            ->add('dc3secureEn', 'ckeditor', array('required'=> false))

            ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fulldon\IntersaBundle\Entity\Persod'
        ));
    }

    public function getName()
    {
        return 'fulldon_intersabundle_persodnnalisationtype';
    }
}