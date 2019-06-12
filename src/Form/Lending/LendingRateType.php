<?php

declare(strict_types=1);

namespace Unilend\Form\Lending;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{ChoiceType, NumberType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Entity\Embeddable\LendingRate;

class LendingRateType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('indexType', ChoiceType::class, [
                'label'        => 'lending-form.index-type',
                'required'     => $options['required'],
                'placeholder'  => '',
                'choices'      => LendingRate::getIndexes(),
                'choice_label' => function ($option, string $key, string $value) {
                    return 'interest-rate-index.' . mb_strtolower($key);
                },
            ])
            ->add('margin', NumberType::class, [
                'label'    => 'lending-form.margin',
                'required' => $options['required'],
                'scale'    => LendingRate::MARGIN_SCALE,
            ])
            ->add('floor', NumberType::class, [
                'label'    => 'lending-form.floor',
                'required' => false,
                'scale'    => LendingRate::MARGIN_SCALE,
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'        => LendingRate::class,
            'required'          => true,
            'validation_groups' => ['non-nullable'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'lending_rate';
    }
}
