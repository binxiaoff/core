<?php

namespace Unilend\Form\Unilend;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LenderWithdrawalType extends AbstractType
{
    const CSRF_TOKEN_ID = 'withdrawal';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'amount',
                NumberType::class,
                ['required' => true]
            )
            ->add('password',
                PasswordType::class,
                ['required' => true ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['csrf_token_id' => self::CSRF_TOKEN_ID]);
    }
}
