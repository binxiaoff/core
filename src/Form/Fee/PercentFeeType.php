<?php

declare(strict_types=1);

namespace Unilend\Form\Fee;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{CheckboxType, NumberType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Entity\{FeeType, PercentFee};

class PercentFeeType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', EntityType::class, [
                'label'        => 'percent-fee-form.type',
                'class'        => FeeType::class,
                'choice_label' => function (FeeType $feeType, $key, $value) {
                    return 'fee-type.' . $feeType->getLabel();
                },
                'choice_translation_domain' => true,
                'placeholder'               => '',
            ])
            ->add('rate', NumberType::class, [
                'label' => 'percent-fee-form.rate',
                'scale' => 2,
            ])
            ->add('isRecurring', CheckboxType::class, [
                'label'    => 'percent-fee-form.recurring',
                'required' => false,
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', PercentFee::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'percent_fee_type';
    }
}
