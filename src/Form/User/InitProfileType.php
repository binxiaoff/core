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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('securityQuestion', SecurityQuestionType::class, ['constraints' => [new Valid()]])
            ->add('password', PasswordType::class, ['constraints' => [new Valid()]])
            ->add('identity', IdentityType::class, ['constraints' => [new Valid()]])
        ;
    }
}
