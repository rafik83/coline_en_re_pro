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

class PersoaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder

            ->add('ac1footertitle', 'text', array('required'=> false))
            ->add('ac2footertitle', 'text', array('required'=> false))
            ->add('ac1footer', 'ckeditor', array('required'=> false))
            ->add('ac2footer', 'ckeditor', array('required'=> false))
            ->add('ac2title1', 'text', array('required'=> false))
            ->add('ac2block1', 'ckeditor', array('required'=> false))
            ->add('ac2block2', 'ckeditor', array('required'=> false))
            ->add('ac3block1', 'ckeditor', array('required'=> false))
            ->add('ac1footertitleEn', 'text', array('required'=> false))
            ->add('ac2footertitleEn', 'text', array('required'=> false))
            ->add('ac1footerEn', 'ckeditor', array('required'=> false))
            ->add('ac2footerEn', 'ckeditor', array('required'=> false))
            ->add('ac2title1En', 'text', array('required'=> false))
            ->add('ac2block1En', 'ckeditor', array('required'=> false))
            ->add('ac2block2En', 'ckeditor', array('required'=> false))
            ->add('ac3block1En', 'ckeditor', array('required'=> false))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fulldon\IntersaBundle\Entity\Persoa'
        ));
    }

    public function getName()
    {
        return 'fulldon_intersabundle_persoatype';
    }
}