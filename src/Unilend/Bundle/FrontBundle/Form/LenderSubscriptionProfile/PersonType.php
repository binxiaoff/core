<?php

namespace Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Bundle\FrontBundle\Form\Components\CountriesType;
use Unilend\Bundle\FrontBundle\Form\Components\GenderType;
use Unilend\Bundle\FrontBundle\Form\Components\NationalitiesType;

class PersonType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('civilite', GenderType::class)
            ->add('nom', TextType::class, ['required' => false])
            ->add('nomUsage', TextType::class, ['required' => false])
            ->add('prenom', TextType::class, ['required' => false])
            ->add('naissance', DateType::class, ['required' => false])
            ->add('idPaysNaissance', CountriesType::class, ['required' => false]) //use custom country Type
            ->add('villeNaissance', TextType::class, ['required' => false])
            ->add('inseeBirth', HiddenType::class, ['required' => false])
            ->add('idNationalite',NationalitiesType::class, ['required' => false])
            ->add('telephone', TextType::class, ['required' => false])
            ->add('mobile', TextType::class, ['required' => false])
            ->add('email', EmailType::class, ['required' => false])
            ->add('fundsOrigin')
            ->add('fundsOriginDetail')
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'        => 'Unilend\Bundle\CoreBusinessBundle\Entity\Clients',
            'validation_groups' => ['lender_person']
        ]);
    }
}
