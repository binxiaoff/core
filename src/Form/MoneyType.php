<?php

declare(strict_types=1);

namespace Unilend\Form;

use Symfony\Component\Form\Extension\Core\Type\{CurrencyType, NumberType};
use Symfony\Component\Form\{AbstractType, FormBuilderInterface};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Entity\Embeddable\Money;
use Unilend\Form\ViewTransformer\AmountTransformer;

class MoneyType extends AbstractType
{
    /** @var AmountTransformer */
    private $transformer;

    /**
     * @param AmountTransformer $transformer
     */
    public function __construct(AmountTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('amount', NumberType::class, [
            'label' => 'money-form.amount',
            'attr'  => ['class' => 'amount'],
        ]);

        $builder->get('amount')->addViewTransformer($this->transformer);

        if (false === $options['disable_currency']) {
            $builder->add('currency', CurrencyType::class, [
                'label'             => 'money-form.currency',
                'preferred_choices' => $options['preferred_currency'],
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'money_type';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'         => Money::class,
            'preferred_currency' => ['EUR'],
            'disable_currency'   => false,
        ]);
    }
}
