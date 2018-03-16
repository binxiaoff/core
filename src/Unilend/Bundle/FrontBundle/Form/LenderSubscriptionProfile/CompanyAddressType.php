<?php

namespace Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile;

use Symfony\Component\Form\{
    AbstractType, FormBuilderInterface
};
use Unilend\Bundle\FrontBundle\Form\Components\CountriesType;

class CompanyAddressType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('address')
            ->add('zip')
            ->add('city')
            ->add('idCountry', CountriesType::class)
        ;
    }
}
