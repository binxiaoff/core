<?php

declare(strict_types=1);

namespace Unilend\Form\User;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\{AbstractType, FormBuilderInterface};
use Symfony\Component\Validator\Constraints\NotBlank;

class SecurityQuestionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('securityQuestion', TextType::class, [
                'constraints' => new NotBlank(),
                'label'       => 'common.security-question',
            ])
            ->add('securityAnswer', TextType::class, [
                'constraints' => new NotBlank(),
                'label'       => 'common.security-answer',
            ])
        ;
    }
}
