<?php

namespace Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
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
            ->add('adresse1')
            ->add('zip')
            ->add('city')
            ->add('idPays')
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Unilend\Bundle\CoreBusinessBundle\Entity\Companies'
        ]);
    }
}
