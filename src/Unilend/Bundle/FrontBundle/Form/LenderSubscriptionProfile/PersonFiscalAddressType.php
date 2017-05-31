<?php

namespace Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Bundle\FrontBundle\Form\Components\CountriesType;

class PersonFiscalAddressType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('adresseFiscal')
            ->add('villeFiscal')
            ->add('cpFiscal')
            ->add('idPaysFiscal', CountriesType::class)
            ->add('housedByThirdPerson', CheckboxType::class, [
                'required' => false,
                'mapped'   => false
            ])
            ->add('noUsPerson', CheckboxType::class, [
                'required' => false,
                'mapped'   => false
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Unilend\Bundle\CoreBusinessBundle\Entity\ClientsAdresses'
        ]);
    }
}
