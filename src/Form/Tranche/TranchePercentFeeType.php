<?php

declare(strict_types=1);

namespace Unilend\Form\Tranche;

use Symfony\Component\Form\{AbstractType, FormBuilderInterface};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Entity\TranchePercentFee;
use Unilend\Form\Fee\PercentFeeType;

class TranchePercentFeeType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('percentFee', PercentFeeType::class, ['label' => false]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', TranchePercentFee::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'tranche_percent_fee_type';
    }
}
