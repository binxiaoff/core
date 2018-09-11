<?php

namespace Unilend\Bundle\FrontBundle\Form;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{ChoiceType, EmailType, TextareaType, TextType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;

class BorrowerContactType extends AbstractType
{
    /** @var Clients */
    private $borrower;
    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     * @param TokenStorageInterface  $tokenStorage
     */
    public function __construct(EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage)
    {
        $this->entityManager = $entityManager;
        $this->borrower      = $tokenStorage->getToken()->getUser();
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $lastName  = '';
        $firstName = '';
        $mobile    = '';
        $email     = '';

        if ($this->borrower instanceof Clients && $this->borrower->isBorrower()) {
            $lastName  = $this->borrower->getNom();
            $firstName = $this->borrower->getPrenom();

            $company = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findOneBy(['idClientOwner' => $this->borrower]);
            if ($company) {
                $mobile = empty($this->borrower->getMobile()) ? $this->borrower->getTelephone() : $this->borrower->getMobile();
                $email  = empty($this->borrower->getEmail()) ? $company->getEmailDirigeant() : $this->borrower->getEmail();
            }
        }

        $subjects = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ContactRequestSubjects')->findAll();

        $subjectsChoices = [];
        foreach ($subjects as $subject) {
            $subjectsChoices['borrower-contact_subject-option-' . $subject->getIdContactRequestSubject()] = $subject->getIdContactRequestSubject();
        }

        $builder
            ->add('last_name', TextType::class, [
                'label'    => 'common_name',
                'data'     => $lastName,
                'required' => true
            ])
            ->add('first_name', TextType::class, [
                'label'    => 'common_firstname',
                'data'     => $firstName,
                'required' => true
            ])
            ->add('mobile', TextType::class, [
                'label'    => 'borrower-contact_mobile',
                'data'     => $mobile,
                'required' => true
            ])
            ->add('email', EmailType::class, [
                'label'    => 'common_email',
                'data'     => $email,
                'required' => true
            ])
            ->add('subject', ChoiceType::class, [
                'label'    => 'borrower-contact_choose-a-subject',
                'required' => true,
                'choices'  => $subjectsChoices
            ])
            ->add('message', TextareaType::class, [
                'label'    => 'borrower-contact_message',
                'required' => true,
                'attr'     => [
                    'rows' => 8,
                ]
            ]);
    }
}
