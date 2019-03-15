<?php

namespace Unilend\Bundle\FrontBundle\Form\Lending;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\{AbstractType, Extension\Core\Type\CheckboxType, Extension\Core\Type\NumberType, FormBuilderInterface};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Bundle\CoreBusinessBundle\Entity\{FeeType, PercentFee};

class PercentFeeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', EntityType::class, [
                'label' => 'lending-form_fee-type',
                'class' => FeeType::class,
            ])
            ->add('rate', NumberType::class, [
                'label' => 'lending-form_fee-rate',
                'scale' => 2,
            ])
            ->add('isRecurring', CheckboxType::class, [
                'label' => 'lending-form_recurring',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', PercentFee::class);
    }

    public function getBlockPrefix()
    {
        return 'unilend_front_bundle_percent_fee';
    }
}
