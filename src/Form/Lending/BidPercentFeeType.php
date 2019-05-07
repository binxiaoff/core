<?php

declare(strict_types=1);

namespace Unilend\Form\Lending;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Entity\BidPercentFee;
use Unilend\Form\Fee\PercentFeeType;

class BidPercentFeeType extends AbstractType
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
        $resolver->setDefault('data_class', BidPercentFee::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'bid_percent_fee_type';
    }
}
