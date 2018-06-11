<?php

namespace Unilend\Bundle\FrontBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{
    CheckboxType, ChoiceType
};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\{
    ClientAddressType, PersonType, SecurityQuestionType
};

class LenderSubscriptionIdentityPerson extends AbstractType
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
            ->add('client', PersonType::class, ['data' => $options['data']['client']])
            ->add('mainAddress', ClientAddressType::class)
            ->add('samePostalAddress', CheckboxType::class)
            ->add('housedByThirdPerson', CheckboxType::class, ['required' => false])
            ->add('noUsPerson', CheckboxType::class, ['required' => false])
            ->add('postalAddress', ClientAddressType::class)
            ->add('security', SecurityQuestionType::class, ['data' => $options['data']['client']])
            ->add('clientType', ChoiceType::class, [
                'choices'  => [
                    $this->translator->trans('lender-subscription_identity-client-type-person-label')       => 'person',
                    $this->translator->trans('lender-subscription_identity-client-type-legal-entity-label') => 'legalEntity'
                ],
                'expanded' => true,
                'multiple' => false,
                'data'     => 'person'
            ])
            ->add('tos', CheckboxType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'form_person';
    }
}
