<?php

declare(strict_types=1);

namespace Unilend\Form\User;

use Symfony\Component\Form\Extension\Core\Type\{PasswordType as BasePasswordType, RepeatedType};
use Symfony\Component\Form\{AbstractType, FormBuilderInterface};
use Symfony\Component\Validator\Constraints\{Length, NotBlank, Regex};
use Unilend\Security\PasswordConstraints;

class PasswordType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('plainPassword', RepeatedType::class, [
            'type'            => BasePasswordType::class,
            'invalid_message' => 'The password fields must match.',
            'options'         => ['attr' => ['class' => 'password-field']],
            'required'        => true,
            'first_options'   => ['label' => 'common.password'],
            'second_options'  => ['label' => 'common.password-confirmation'],
            'constraints'     => [
                new NotBlank(),
                new Length([
                    'min' => PasswordConstraints::MIN_PASSWORD_LENGTH,
                    'max' => PasswordConstraints::MAX_PASSWORD_LENGTH,
                ]),
                new Regex([
                    'pattern' => PasswordConstraints::PASSWORD_REGEX_PATTERN,
                ]),
            ],
        ]);
    }
}
