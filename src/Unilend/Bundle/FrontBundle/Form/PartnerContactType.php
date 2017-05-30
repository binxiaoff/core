<?php

namespace Unilend\Bundle\FrontBundle\Form;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Unilend\Bundle\FrontBundle\Security\User\UserPartner;

class PartnerContactType extends AbstractType
{
    /** @var UserPartner */
    private $user;
    /** @var EntityManager */
    private $entityManager;
    /** @var string */
    private $language;

    public function __construct(EntityManager $entityManager, TokenStorage $tokenStorage, $language)
    {
        $this->entityManager = $entityManager;
        $this->language      = $language;
        $this->user          = $tokenStorage->getToken()->getUser();
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $client = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->user->getClientId());
        $phone  = $client->getTelephone();
        $email  = $client->getEmail();

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
