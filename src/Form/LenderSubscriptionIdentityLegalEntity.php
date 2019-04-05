<?php

namespace Unilend\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{
    CheckboxType, ChoiceType
};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Form\LenderSubscriptionProfile\{
    CompanyAddressType, CompanyIdentityType, LegalEntityType, SecurityQuestionType
};

class LenderSubscriptionIdentityLegalEntity extends AbstractType
{
    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('client', LegalEntityType::class, ['data' => $options['data']['client']])
            ->add('company', CompanyIdentityType::class, ['data' => $options['data']['company']])
            ->add('mainAddress', CompanyAddressType::class)
            ->add('samePostalAddress', CheckboxType::class)
            ->add('postalAddress', CompanyAddressType::class)
            ->add('security', SecurityQuestionType::class, ['data' => $options['data']['client']])
            ->add('clientType', ChoiceType::class, [
                'choices'  => [
                    'lender-subscription_identity-client-type-person-label'       => 'person',
                    'lender-subscription_identity-client-type-legal-entity-label' => 'legalEntity'
                ],
                'expanded' => true,
                'multiple' => false,
                'data'     => 'legalEntity'
            ])
            ->add('tos', CheckboxType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'form_legal_entity';
    }
}
