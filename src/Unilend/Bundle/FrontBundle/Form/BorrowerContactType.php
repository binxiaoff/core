<?php
namespace Unilend\Bundle\FrontBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Unilend\Bundle\FrontBundle\Security\User\UserBorrower;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class BorrowerContactType extends AbstractType
{
    /** @var UserBorrower */
    private $borrower;
    /** @var EntityManager */
    private $entityManager;
    private $language;

    public function __construct(EntityManager $entityManager, TokenStorage $tokenStorage, $language)
    {
        $this->entityManager = $entityManager;
        $this->language      = $language;
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

        if ($this->borrower instanceof UserBorrower) {
            /** @var \clients $client */
            $client = $this->entityManager->getRepository('clients');
            if ($client->get($this->borrower->getClientId())) {
                $lastName  = $client->nom;
                $firstName = $client->prenom;
            }

            /** @var \companies $company */
            $company = $this->entityManager->getRepository('companies');
            if ($company->get($this->borrower->getClientId(), 'id_client_owner')) {
                $mobile = empty($client->mobile) ? $client->telephone : $client->mobile;
                $email  = empty($client->email) ? $company->email_dirigeant : $client->email;
            }
        }
        /** @var \contact_request_subjects $requestSubjects */
        $requestSubjects = $this->entityManager->getRepository('contact_request_subjects');
        $subjects        = $requestSubjects->select();

        $subjectsChoices = [];
        foreach ($subjects as $subject) {
            $subjectsChoices['borrower-contact_subject-option-' . $subject['id_contact_request_subject']] = $subject['id_contact_request_subject'];
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
