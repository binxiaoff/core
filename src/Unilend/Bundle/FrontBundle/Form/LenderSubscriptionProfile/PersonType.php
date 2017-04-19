<?php

namespace Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\FrontBundle\Form\Components\CountriesType;
use Unilend\Bundle\FrontBundle\Form\Components\GenderType;
use Unilend\Bundle\FrontBundle\Form\Components\NationalitiesType;

class PersonType extends AbstractType
{
    /** @var  TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $startBdayRange = new \DateTime('NOW-18 years');

        $builder
            ->add('civilite', GenderType::class)
            ->add('nom')
            ->add('nomUsage', TextType::class, ['required' => false])
            ->add('prenom')
            ->add('naissance', BirthdayType::class, ['years' => range($startBdayRange->format('Y'), 1910)])
            ->add('idPaysNaissance', CountriesType::class)
            ->add('villeNaissance')
            ->add('inseeBirth', HiddenType::class, ['required' => false])
            ->add('idNationalite',NationalitiesType::class)
            ->add('email', RepeatedType::class, [
                'type'            => EmailType::class,
                'invalid_message' => $this->translator->trans('common-validator_email-address-invalid'),
                'required'        => true
            ])
            ->add('password', RepeatedType::class, [
                'type'            => PasswordType::class,
                'invalid_message' => $this->translator->trans('common-validator_password-not-equal'),
                'required'        => true
            ])
            ->add('mobile')
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
