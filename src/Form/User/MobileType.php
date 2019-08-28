<?php

declare(strict_types=1);

namespace Unilend\Form\User;

use libphonenumber\PhoneNumberFormat;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MobileType extends AbstractType
{
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label'          => 'common.mobile-phone-label',
            'widget'         => PhoneNumberType::WIDGET_SINGLE_TEXT,
            'default_region' => 'FR',
            'format'         => PhoneNumberFormat::NATIONAL,
        ]);
    }

    /**
     * @return string|null
     */
    public function getParent()
    {
        return PhoneNumberType::class;
    }
}
