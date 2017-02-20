<?php


namespace Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
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
            ->add('nom', TextType::class)
            ->add('prenom', TextType::class)
            ->add('fonction', TextType::class)
            ->add('telephone', TextType::class)
            ->add('email', EmailType::class)
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
