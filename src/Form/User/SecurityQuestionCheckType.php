<?php

declare(strict_types=1);

namespace Unilend\Form\User;

use Symfony\Component\Form\{AbstractType, FormBuilderInterface};

class SecurityQuestionCheckType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->remove('securityQuestion');
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return SecurityQuestionType::class;
    }
}
