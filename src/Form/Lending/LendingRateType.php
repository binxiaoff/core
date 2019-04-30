<?php

namespace Unilend\Form\Lending;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{ChoiceType, NumberType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Entity\Embeddable\LendingRate;
use Unilend\Form\Traits\ConstantsToChoicesTrait;

class LendingRateType extends AbstractType
{
    use ConstantsToChoicesTrait;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('indexType', ChoiceType::class, [
                'label'   => 'lending-form.index-type',
                'choices' => $this->getChoicesFromConstants(LendingRate::getIndexes(), 'interest-rate-index'),
            ])
            ->add('margin', NumberType::class, [
                'label' => 'lending-form.margin',
                'scale' => LendingRate::MARGIN_SCALE,
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', LendingRate::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'lending_rate';
    }
}
