<?php

declare(strict_types=1);

namespace Unilend\Form\User;

use libphonenumber\PhoneNumberFormat;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Valid;

class InitPasswordType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('securityQuestion', SecurityQuestionType::class, ['constraints' => [new Valid()]])
            ->add('password', PasswordType::class, ['constraints' => [new Valid()]])
            ->add('mobile', PhoneNumberType::class, [
                'widget'         => PhoneNumberType::WIDGET_SINGLE_TEXT,
                'default_region' => 'FR',
                'format'         => PhoneNumberFormat::NATIONAL,
                'constraints'    => [new Valid()],
                'label'          => 'password-init.mobile-phone-label',
            ])
        ;
    }
}
