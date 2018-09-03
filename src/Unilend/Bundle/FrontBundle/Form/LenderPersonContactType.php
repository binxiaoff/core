<?php

namespace Unilend\Bundle\FrontBundle\Form;

use Symfony\Component\Form\{AbstractType, Extension\Core\Type\EmailType, Extension\Core\Type\RepeatedType, Extension\Core\Type\TextType, FormBuilderInterface};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class LenderPersonContactType extends AbstractType
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

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('mobile', TextType::class, ['required' => true])
            ->add('telephone', TextType::class, ['required' => false])
            ->add('email', RepeatedType::class, [
                'type'            => EmailType::class,
                'invalid_message' => $this->translator->trans('common-validator_email-address-invalid'),
                'required'        => true
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Unilend\Bundle\CoreBusinessBundle\Entity\Clients'
        ));
    }
}
