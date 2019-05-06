<?php

declare(strict_types=1);

namespace Unilend\Form\Tranche;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Entity\Embeddable\NullableLendingRate;
use Unilend\Entity\Tranche;
use Unilend\Form\Lending\LendingRateType;
use Unilend\Form\MoneyType;

class TrancheType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, ['label' => 'tranche-form.name'])
            ->add('repaymentType', ChoiceType::class, [
                'label'        => 'tranche-form.repayment-type',
                'choices'      => Tranche::getRepaymentTypes(),
                'choice_label' => function ($option, string $key, string $value) {
                    return 'repayment-type.' . mb_strtolower($key);
                },
            ])
            ->add('duration', null, ['label' => 'tranche-form.maturity'])
            ->add('money', MoneyType::class)
            ->add('rate', LendingRateType::class, [
                'data_class' => NullableLendingRate::class,
                'required'   => false,
            ])
            ->add('capitalPeriodicity', null, ['label' => 'tranche-form.capital-periodicity'])
            ->add('interestPeriodicity', null, ['label' => 'tranche-form.interest-periodicity'])
            ->add('expectedReleasingDate', null, [
                'label'  => 'tranche-form.expected-releasing-date',
                'widget' => 'single_text',
                'input'  => 'datetime_immutable',
                'attr'   => ['class' => 'ui-has-datepicker'],
            ])
            ->add('expectedStartingDate', null, [
                'label'  => 'tranche-form.expected-starting-date',
                'widget' => 'single_text',
                'input'  => 'datetime_immutable',
                'attr'   => ['class' => 'ui-has-datepicker'],
            ])
            ->add('tranchePercentFees', CollectionType::class, [
                'label'          => false,
                'entry_type'     => TranchePercentFeeType::class,
                'entry_options'  => ['label' => false],
                'allow_add'      => true,
                'allow_delete'   => true,
                'by_reference'   => false,
                'prototype'      => true,
                'prototype_name' => '__tranche_percent_fees__',
                'attr'           => ['class' => 'tranche-percent-fees'],
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Tranche::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'project_tranche_type';
    }
}
