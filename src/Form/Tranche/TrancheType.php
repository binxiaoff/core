<?php

declare(strict_types=1);

namespace Unilend\Form\Tranche;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{ChoiceType, CollectionType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;
use Unilend\Entity\Embeddable\NullableLendingRate;
use Unilend\Entity\Tranche;
use Unilend\Form\{Lending\LendingRateType, MoneyType};

class TrancheType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, ['label' => 'tranche-form.name'])
            ->add('money', MoneyType::class, ['constraints' => [new Valid()]])
            ->add('duration', null, [
                'label' => 'tranche-form.maturity',
                'attr'  => ['min' => 1],
            ])
            ->add('loanType', ChoiceType::class, [
                'label'        => 'tranche-form.loan-type',
                'choices'      => Tranche::getLoanTypes(),
                'choice_label' => function ($option, string $key, string $value) {
                    return 'loan-type.' . $value;
                },
                'placeholder' => '',
            ])
            ->add('repaymentType', ChoiceType::class, [
                'label'        => 'tranche-form.repayment-type',
                'choices'      => Tranche::getRepaymentTypes(),
                'choice_label' => function ($option, string $key, string $value) {
                    return 'repayment-type.' . $value;
                },
                'placeholder' => '',
            ])
            ->add('capitalPeriodicity', null, [
                'label' => 'tranche-form.capital-periodicity',
                'attr'  => ['min' => 1],
            ])
            ->add('interestPeriodicity', null, [
                'label' => 'tranche-form.interest-periodicity',
                'attr'  => ['min' => 1],
            ])
            ->add('rate', LendingRateType::class, [
                'data_class'        => NullableLendingRate::class,
                'required'          => $options['rate_required'],
                'validation_groups' => $options['rate_required'] ? ['non-nullable'] : null,
                'empty_data'        => new NullableLendingRate(),
                'constraints'       => [new Valid()],
            ])
            ->add('expectedReleasingDate', null, [
                'label'    => 'tranche-form.expected-releasing-date',
                'required' => false,
                'widget'   => 'single_text',
                'input'    => 'datetime_immutable',
                'format'   => 'dd/MM/yyyy',
                'attr'     => ['class' => 'ui-has-datepicker'],
            ])
            ->add('expectedStartingDate', null, [
                'label'    => 'tranche-form.expected-starting-date',
                'required' => false,
                'widget'   => 'single_text',
                'input'    => 'datetime_immutable',
                'format'   => 'dd/MM/yyyy',
                'attr'     => ['class' => 'ui-has-datepicker'],
            ])
            ->add('trancheFees', CollectionType::class, [
                'label'          => false,
                'constraints'    => [new Valid()],
                'entry_type'     => TrancheFeeType::class,
                'entry_options'  => ['label' => false],
                'allow_add'      => true,
                'allow_delete'   => true,
                'by_reference'   => false,
                'prototype'      => true,
                'prototype_name' => '__tranche_fees__',
                'attr'           => ['class' => 'tranche-fees'],
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

        $resolver->setRequired(['rate_required']);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'project_tranche_type';
    }
}
