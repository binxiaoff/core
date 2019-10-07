<?php

declare(strict_types=1);

namespace Unilend\Form\Bid;

use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\{AbstractType, FormBuilderInterface, FormEvent, FormEvents};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Entity\Bids;
use Unilend\Form\Lending\LendingRateType;
use Unilend\Form\MoneyType;

class BidType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('money', MoneyType::class, ['disable_currency' => true])
            ->add('rate', LendingRateType::class)
            ->add('bidFees', CollectionType::class, [
                'label'         => false,
                'entry_type'    => BidFeeType::class,
                'entry_options' => ['label' => false],
                'allow_add'     => true,
                'allow_delete'  => true,
                'by_reference'  => false,
            ])
            ->add('comment')
            ->add('expectedCommitteeDate', DateType::class, [
                'required' => false,
                'widget'   => 'single_text',
                'input'    => 'datetime_immutable',
                'format'   => 'dd/MM/yyyy',
                'attr'     => ['class' => 'ui-has-datepicker'],
            ])
            ->addEventListener(FormEvents::POST_SET_DATA, [$this, 'handleRateFieldsDisplaying'])
        ;
    }

    /**
     * @param FormEvent $formEvent
     */
    public function handleRateFieldsDisplaying(FormEvent $formEvent): void
    {
        $form = $formEvent->getForm();
        /** @var Bids $bid */
        $bid = $formEvent->getData();
        if ($bid->getTranche() && $trancheRate = $bid->getTranche()->getRate()) {
            if ($trancheRate->getIndexType()) {
                $form->get('rate')->remove('indexType');
            }
        }
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
