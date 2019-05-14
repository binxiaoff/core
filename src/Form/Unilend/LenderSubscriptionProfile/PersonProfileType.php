<?php

namespace Unilend\Form\Unilend\LenderSubscriptionProfile;


use Symfony\Component\Form\{AbstractType, Extension\Core\Type\TextType, FormBuilderInterface};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Form\Components\{GenderType, NationalitiesType};

class PersonProfileType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('civilite', GenderType::class)
            ->add('nomUsage', TextType::class, ['required' => false])
            ->add('prenom')
            ->add('idNationalite', NationalitiesType::class)
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'        => 'Unilend\Entity\Clients',
            'validation_groups' => ['lender_person']
        ]);
    }
}
