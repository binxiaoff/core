<?php

namespace Unilend\Bundle\FrontBundle\Form;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;

class PartnerContactType extends AbstractType
{
    /** @var Clients */
    private $user;
    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     * @param TokenStorage           $tokenStorage
     */
    public function __construct(EntityManagerInterface $entityManager, TokenStorage $tokenStorage)
    {
        $this->entityManager = $entityManager;
        $this->user          = $tokenStorage->getToken()->getUser();
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $phone  = $this->user->getTelephone();
        $email  = $this->user->getEmail();

        $builder
            ->add('phone', TextType::class, [
                'label'    => 'partner-contact_phone-field-label',
                'data'     => $phone,
                'required' => true
            ])
            ->add('email', EmailType::class, [
                'label'    => 'common_email',
                'data'     => $email,
                'required' => true
            ])
            ->add('message', TextareaType::class, [
                'label'    => 'partner-contact_message-field-label',
                'required' => true,
                'attr'     => [
                    'rows' => 8,
                ]
            ]);
    }
}
