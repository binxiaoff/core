<?php

declare(strict_types=1);

namespace Unilend\Form\Fee;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{CheckboxType, ChoiceType, PercentType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Entity\Embeddable\Fee;

class FeeType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label'        => 'fee-form.type',
                'required'     => true,
                'choices'      => $options['fee_type'],
                'choice_label' => function ($choice, $key, $value) {
                    return 'fee-type.' . mb_strtolower($key);
                },
                'choice_translation_domain' => true,
                'placeholder'               => '',
            ])
            ->add('rate', PercentType::class, [
                'label'  => 'fee-form.rate',
                'scale'  => Fee::RATE_SCALE,
                'symbol' => false,
            ])
            ->add('isRecurring', CheckboxType::class, [
                'label'    => 'fee-form.recurring',
                'required' => false,
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', Fee::class);
        $resolver->setRequired(['fee_type']);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'fee_type';
    }
}
