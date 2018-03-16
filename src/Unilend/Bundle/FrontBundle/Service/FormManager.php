<?php

namespace Unilend\Bundle\FrontBundle\Service;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\Extension\Core\Type\{
    CheckboxType, ChoiceType
};
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Clients, ClientsAdresses, ClientsHistoryActions, Companies
};
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\{
    BankAccountType, CompanyAddressType, CompanyIdentityType, LegalEntityType, OriginOfFundsType, PersonFiscalAddressType, PersonType, PostalAddressType, SecurityQuestionType
};

class FormManager
{
    /** @var FormFactory */
    private $formFactory;
    /** @var TranslatorInterface */
    private $translator;
    /** EntityManager */
    private $entityManager;

    /**
     * @param FormFactory         $formFactory
     * @param TranslatorInterface $translator
     * @param EntityManager       $entityManager
     */
    public function __construct(
        FormFactory $formFactory,
        TranslatorInterface $translator,
        EntityManager $entityManager
    )
    {
        $this->formFactory   = $formFactory;
        $this->translator    = $translator;
        $this->entityManager = $entityManager;
    }

    /**
     * @param object $dbObject
     * @param object $formObject
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getModifiedContent($dbObject, $formObject)
    {
        if (get_class($dbObject) !== get_class($formObject)) {
            throw new \Exception('The objects to be compared are not of the same class');
        }

        $differences = [];
        $object      = new \ReflectionObject($dbObject);

        foreach ($object->getMethods() as $method) {
            if (
                substr($method->name, 0, 3) === 'get'
                && $method->invoke($dbObject) != $method->invoke($formObject)
            ) {
                if ($method->name !== 'getUpdated') {
                    $differences[] = str_replace('get', '', $method->name);
                }
            }
        }

        return $differences;
    }

    /**
     * @param Clients         $client
     * @param ClientsAdresses $clientAddress
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    public function getLenderSubscriptionPersonIdentityForm(Clients $client, ClientsAdresses $clientAddress)
    {
        $form = $this->formFactory->createBuilder()
            ->add('client', PersonType::class, ['data' => $client])
            ->add('fiscalAddress', PersonFiscalAddressType::class, ['data' => $clientAddress])
            ->add('postalAddress', PostalAddressType::class, ['data' => $clientAddress])
            ->add('security', SecurityQuestionType::class, ['data' => $client])
            ->add('clientType', ChoiceType::class, [
                'choices'  => [
                    $this->translator->trans('lender-subscription_identity-client-type-person-label')       => 'person',
                    $this->translator->trans('lender-subscription_identity-client-type-legal-entity-label') => 'legalEntity'
                ],
                'expanded' => true,
                'multiple' => false,
                'data'     => 'person'
            ])
            ->add('tos', CheckboxType::class)
            ->getForm();

        return $form;
    }

    /**
     * @param Clients   $client
     * @param Companies $company
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    public function getLenderSubscriptionLegalEntityIdentityForm(Clients $client, Companies $company)
    {
        $form = $this->formFactory->createBuilder()
            ->add('client', LegalEntityType::class, ['data' => $client])
            ->add('company', CompanyIdentityType::class, ['data' => $company])
            ->add('mainAddress', CompanyAddressType::class)
            ->add('samePostalAddress', CheckboxType::class)
            ->add('postalAddress', CompanyAddressType::class)
            ->add('security', SecurityQuestionType::class, ['data' => $client])
            ->add('clientType', ChoiceType::class, [
                'choices'  => [
                    $this->translator->trans('lender-subscription_identity-client-type-person-label')       => 'person',
                    $this->translator->trans('lender-subscription_identity-client-type-legal-entity-label') => 'legalEntity'
                ],
                'expanded' => true,
                'multiple' => false,
                'data'     => 'legalEntity'
            ])
            ->add('tos', CheckboxType::class)
            ->getForm();

        return $form;
    }

    /**
     * @param Clients $client
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    public function getBankInformationForm(Clients $client)
    {
        $form = $this->formFactory->createBuilder()
            ->add('bankAccount', BankAccountType::class)
            ->add('client', OriginOfFundsType::class, ['data' => $client])
            ->add('housedByThirdPerson', CheckboxType::class, [
                'required' => false,
            ])
            ->getForm();

        return $form;
    }

    /**
     * @param  array $post
     *
     * @return mixed
     */
    public function cleanPostData($post)
    {
        foreach ($post as $key => $value) {
            if (is_array($value)) {
                $this->cleanPostData($value);
            }

            if (is_string($value)) {
                $post[$key] = htmlspecialchars(strip_tags($value));
            }
        }

        return $post;
    }

    /**
     * @param array $files
     *
     * @return array
     */
    public function getNamesOfFiles($files)
    {
        $fileNames = [];
        foreach($files as $name => $file) {
            if ($file instanceof UploadedFile) {
                $fileNames[$name] = $file->getClientOriginalName();
            }
        }

        return $fileNames;
    }

    /**
     * @param $client
     * @param $formName
     * @param $serialize
     * @param $ip
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveFormSubmission($client, $formName, $serialize, $ip)
    {
        if ($client instanceof \clients) {
            $client = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
        }

        $clientAction = new ClientsHistoryActions();
        $clientAction->setNomForm($formName);
        $clientAction->setIdClient($client);
        $clientAction->setSerialize($serialize);
        $clientAction->setIP($ip);

        $this->entityManager->persist($clientAction);
        $this->entityManager->flush($clientAction);
    }
}
