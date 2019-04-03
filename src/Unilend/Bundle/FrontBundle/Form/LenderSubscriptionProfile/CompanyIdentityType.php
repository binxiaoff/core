<?php


namespace Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Entity\Companies;
use Unilend\Bundle\FrontBundle\Form\Components\GenderType;
use Unilend\Entity\Settings;

class CompanyIdentityType extends AbstractType
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $settingEntity       = $this->entityManager->getRepository(Settings::class)->findOneBy(['type' => "Liste deroulante conseil externe de l'entreprise"]);
        $externalCounselList = array_flip(json_decode($settingEntity->getValue(), true));

        $clientStatusChoices = [
            'lender-identity-form_company-client-status-' . Companies::CLIENT_STATUS_MANAGER             => Companies::CLIENT_STATUS_MANAGER,
            'lender-identity-form_company-client-status-' . Companies::CLIENT_STATUS_DELEGATION_OF_POWER => Companies::CLIENT_STATUS_DELEGATION_OF_POWER,
            'lender-identity-form_company-client-status-' . Companies::CLIENT_STATUS_EXTERNAL_CONSULTANT => Companies::CLIENT_STATUS_EXTERNAL_CONSULTANT
        ];

        $builder
            ->add('name', TextType::class)
            ->add('forme')
            ->add('siren')
            ->add('capital')
            ->add('phone')
            ->add('statusClient', ChoiceType::class, [
                'choices' => $clientStatusChoices,
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('statusConseilExterneEntreprise', ChoiceType::class, [
                'choices' => $externalCounselList,
                'required' => false,
                'expanded' => false,
                'multiple' => false
            ])
            ->add('preciserConseilExterneEntreprise', TextType::class, ['required' => false])
            ->add('civiliteDirigeant', GenderType::class)
            ->add('nomDirigeant', TextType::class, ['required' => false])
            ->add('prenomDirigeant', TextType::class, ['required' => false])
            ->add('fonctionDirigeant', TextType::class, ['required' => false])
            ->add('emailDirigeant', EmailType::class, ['required' => false])
            ->add('phoneDirigeant', TextType::class, ['required' => false])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Unilend\Entity\Companies'
        ]);
    }

}
