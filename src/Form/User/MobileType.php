<?php

declare(strict_types=1);

namespace Unilend\Form\User;

use libphonenumber\PhoneNumberFormat;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class MobileType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('mobile', PhoneNumberType::class, [
            'widget'         => PhoneNumberType::WIDGET_SINGLE_TEXT,
            'default_region' => 'FR',
            'format'         => PhoneNumberFormat::NATIONAL,
            'label'          => 'password-init.mobile-phone-label',
        ]);
    }
}
