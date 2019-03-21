<?php

namespace Unilend\Bundle\FrontBundle\Form\Lending;

use Symfony\Component\Form\{AbstractType, Extension\Core\Type\ChoiceType, Extension\Core\Type\NumberType, FormBuilderInterface};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Bundle\CoreBusinessBundle\Entity\Embeddable\LendingRate;

class LendingRateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = [];
        foreach (LendingRate::getIndexes() as $key => $value) {
            $choices['interest-rate-index_' . $value] = $value;
        }

        $builder
            ->add('indexType', ChoiceType::class, [
                'label'   => 'lending-form_index-type',
                'choices' => $choices,
            ])
            ->add('margin', NumberType::class, [
                'label' => 'lending-form_margin',
                'scale' => LendingRate::MARGIN_SCALE,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', LendingRate::class);
    }

    public function getBlockPrefix()
    {
        return 'lending_rate';
    }
}
