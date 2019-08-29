<?php

declare(strict_types=1);

namespace Unilend\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Valid;

class IdentityType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', TextType::class, [
                'constraints' => [new Valid()],
                'label'       => 'account-init.first-name-label',
            ])
            ->add('lastName', TextType::class, [
                'constraints' => [new Valid()],
                'label'       => 'account-init.last-name-label',
            ])
            ->add('jobFunction', TextType::class, [
                'constraints' => [new Valid()],
                'label'       => 'account-init.job-function-label',
            ])
            ->add('mobile', PhoneType::class, [
                'constraints' => [new Valid()],
                'label'       => 'account-init.mobile-phone-label',
            ])
            ->add('phone', PhoneType::class, [
                'constraints' => [new Valid()],
                'label'       => 'account-init.phone-label',
            ])
        ;
    }
}
