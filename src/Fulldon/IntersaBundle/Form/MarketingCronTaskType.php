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
use Fulldon\IntersaBundle\Service\AttachmentsTransformer;

class MarketingCronTaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder

            ->add('emailContent', 'ckeditor', array('required' => false ))
            ->add('smsContent', 'textarea', array('required' => false ))
            ->add($builder->create(
                'file', 'file', [
                    'required' => false,
                    'multiple' => true,
                ]
            )->addModelTransformer(new AttachmentsTransformer()))

            ->add('objet', 'text', array('required' => false ))
            ->add('title', 'text', array('required' => true ))
            ->add('isEmail', 'checkbox', array('required' => false ))
            ->add('isSms', 'checkbox', array('required' => false ))
            ;

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fulldon\IntersaBundle\Entity\MarketingCronTask'
        ));
    }

    public function getName()
    {
        return 'fulldon_marketing';
    }
}