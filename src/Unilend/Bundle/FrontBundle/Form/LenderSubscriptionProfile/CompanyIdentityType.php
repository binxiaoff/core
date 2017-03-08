<?php


namespace Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile;


use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\FrontBundle\Form\Components\GenderType;

class CompanyIdentityType extends AbstractType
{
    /** @var  EntityManager */
    private $em;

    /** @var  TranslatorInterface */
    private $translator;

    public function __construct(EntityManager $entityManager, TranslatorInterface $translator)
    {
        $this->em = $entityManager;
        $this->translator    = $translator;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $settingEntity       = $this->em->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => "Liste deroulante conseil externe de l'entreprise"]);
        $externalCounselList = array_flip(json_decode($settingEntity->getValue(), true));

        $clientStatusChoices = [
            $this->translator->trans('lender-identity-form_company-client-status-' . \companies::CLIENT_STATUS_MANAGER)             => \companies::CLIENT_STATUS_MANAGER,
            $this->translator->trans('lender-identity-form_company-client-status-' . \companies::CLIENT_STATUS_DELEGATION_OF_POWER) => \companies::CLIENT_STATUS_DELEGATION_OF_POWER,
            $this->translator->trans('lender-identity-form_company-client-status-' . \companies::CLIENT_STATUS_EXTERNAL_CONSULTANT) => \companies::CLIENT_STATUS_EXTERNAL_CONSULTANT
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
            ->add('preciserConseilExterneEntreprise', TextType::class)
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
            'data_class' => 'Unilend\Bundle\CoreBusinessBundle\Entity\Companies'
        ]);
    }

}
