<?php

declare(strict_types=1);

namespace Unilend\Form\Lending;

use Symfony\Component\Form\Extension\Core\Type\{ChoiceType, NumberType};
use Symfony\Component\Form\{AbstractType, FormBuilderInterface, FormError, FormEvent, FormEvents};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Entity\Embeddable\LendingRate;

class LendingRateType extends AbstractType
{
    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

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
            ->addEventListener(FormEvents::SUBMIT, [$this, 'checkRateData'])
        ;
    }

    /**
     * @param FormEvent $formEvent
     */
    public function checkRateData(FormEvent $formEvent): void
    {
        $form = $formEvent->getForm();
        /** @var LendingRate $lendingRate */
        $lendingRate = $formEvent->getData();
        if ($lendingRate->getIndexType() && null === $lendingRate->getMargin()) {
            $form->get('margin')->addError(new FormError($this->translator->trans('lending-rate-form.margin-required')));
        }
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
