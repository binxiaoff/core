<?php


namespace Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Bundle\FrontBundle\Form\Components\GenderType;

class LegalEntityType extends AbstractType
{
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
            ->add('email', EmailType::class)
            ->add('emailConfirmation', EmailType::class, ['mapped' => false])
            ->add('password', PasswordType::class)
            ->add('passwordConfirmation', PasswordType::class, ['mapped' => false])
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
