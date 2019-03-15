<?php

namespace Unilend\Bundle\FrontBundle\Form\Lending;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Bundle\CoreBusinessBundle\Entity\BidPercentFee;

class BidPercentFeeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('percentFee', PercentFeeType::class, ['label' => false]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', BidPercentFee::class);
    }

    public function getBlockPrefix()
    {
        return 'unilend_front_bundle_bid_percent_fee_type';
    }
}
