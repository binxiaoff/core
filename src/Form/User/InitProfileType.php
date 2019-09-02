<?php

declare(strict_types=1);

namespace Unilend\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Valid;

class InitProfileType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('securityQuestion', SecurityQuestionType::class, [
                'mapped'      => false,
                'constraints' => [new Valid()],
            ])
            ->add('password', PasswordType::class, [
                'mapped'      => false,
                'constraints' => [new Valid()],
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): string
    {
        return IdentityType::class;
    }
}
