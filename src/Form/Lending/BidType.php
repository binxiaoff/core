<?php

namespace Unilend\Form\Lending;

use Symfony\Component\Form\{AbstractType, Extension\Core\Type\CheckboxType, Extension\Core\Type\CollectionType, Extension\Core\Type\MoneyType, FormBuilderInterface};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Entity\Bids;

class BidType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('amount', MoneyType::class, [
            'label'   => 'lending-form_amount',
            'divisor' => 100,
        ])
            ->add('rate', LendingRateType::class)
            ->add('bidPercentFees', CollectionType::class, [
                'label'         => false,
                'entry_type'    => BidPercentFeeType::class,
                'entry_options' => ['label' => false],
                'allow_add'     => true,
                'allow_delete'  => true,
                'by_reference'  => false,
            ])
            ->add('agent', CheckboxType::class, [
                'label'    => 'Je souhaite être agent',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', Bids::class);
    }

    public function getBlockPrefix()
    {
        return 'bid_type';
    }
}
