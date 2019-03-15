<?php

namespace Unilend\Bundle\FrontBundle\Form\Lending;

use Symfony\Component\Form\{AbstractType, Extension\Core\Type\ChoiceType, Extension\Core\Type\NumberType, FormBuilderInterface};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Bundle\CoreBusinessBundle\Entity\Embeddable\LendingRate;

class LendingRateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('indice', ChoiceType::class, [
                'label' => 'lending-form_indice',
                'choices' => LendingRate::getIndices()
            ])
            ->add('margin', NumberType::class, [
                'label' => 'lending-form_margin',
                'scale' => 2,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', LendingRate::class);
    }

    public function getBlockPrefix()
    {
        return 'unilend_front_bundle_lending_rate';
    }
}
