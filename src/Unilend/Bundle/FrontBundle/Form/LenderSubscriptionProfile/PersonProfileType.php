<?php

namespace Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Bundle\FrontBundle\Form\Components\GenderType;
use Unilend\Bundle\FrontBundle\Form\Components\NationalitiesType;

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
            'data_class'        => 'Unilend\Bundle\CoreBusinessBundle\Entity\Clients',
            'validation_groups' => ['lender_person']
        ]);
    }
}
