<?php

declare(strict_types=1);

namespace Unilend\Form\Bid;

use Symfony\Component\Form\Extension\Core\Type\{CollectionType, MoneyType};
use Symfony\Component\Form\{AbstractType, FormBuilderInterface, FormEvents};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Entity\Bids;
use Unilend\Form\Lending\LendingRateType;

class BidType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('money', \Unilend\Form\MoneyType::class, ['disable_currency' => true])
            ->add('rate', LendingRateType::class)
            ->add('bidPercentFees', CollectionType::class, [
                'label'         => false,
                'entry_type'    => BidPercentFeeType::class,
                'entry_options' => ['label' => false],
                'allow_add'     => true,
                'allow_delete'  => true,
                'by_reference'  => false,
            ])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', Bids::class);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'bid_type';
    }
}
