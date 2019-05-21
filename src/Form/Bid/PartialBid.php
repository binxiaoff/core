<?php

declare(strict_types=1);

namespace Unilend\Form\Bid;

use Symfony\Component\Form\Extension\Core\Type\{HiddenType, NumberType};
use Symfony\Component\Form\{AbstractType, FormBuilderInterface};
use Unilend\Form\ViewTransformer\AmountTransformer;

class PartialBid extends AbstractType
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
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', HiddenType::class, ['required' => true])
            ->add('amount', NumberType::class)
        ;

        $builder->get('amount')->addViewTransformer($this->transformer);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'bid_partial';
    }
}
