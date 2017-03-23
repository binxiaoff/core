<?php


namespace Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\FrontBundle\Form\Components\GenderType;

class LegalEntityType extends AbstractType
{
    /** @var TranslatorInterface */
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
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Unilend\Bundle\CoreBusinessBundle\Entity\Clients'
        ]);
    }

}
