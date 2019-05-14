<?php


namespace Unilend\Form\Unilend\LenderSubscriptionProfile;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{CheckboxType, EmailType, PasswordType, RepeatedType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Form\Components\GenderType;

class LegalEntityType extends AbstractType
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
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('civilite', GenderType::class)
            ->add('nom')
            ->add('prenom')
            ->add('fonction')
            ->add('mobile')
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
            ->add('optin1', CheckboxType::class);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => 'Unilend\Entity\Clients'
        ]);
    }
}
