<?php

namespace Unilend\Bundle\FrontBundle\Service;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\{
    Extension\Core\Type\CheckboxType, FormBuilderInterface, FormFactory, FormInterface
};
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    AddressType, ClientAddress, Clients, ClientsHistoryActions, CompanyAddress
};
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\{
    BankAccountType, ClientAddressType, CompanyAddressType, OriginOfFundsType
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
     * @param $dbObject
     * @param $formObject
     *
     * @return array
     * @throws \Exception
     */
    public function getModifiedContent($dbObject, $formObject): array
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
     * @param Clients $client
     *
     * @return FormInterface
     */
    public function getBankInformationForm(Clients $client): FormInterface
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
     * @param array $post
     *
     * @return array
     */
    public function cleanPostData(array $post): array
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
     * @param $files
     *
     * @return array
     */
    public function getNamesOfFiles($files): array
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
     * @param Clients|\clients $client
     * @param string           $formName
     * @param string           $serialize
     * @param string           $ip
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveFormSubmission($client, string $formName, string $serialize, string $ip)
    {
        if ($client instanceof \clients) {
            $client = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
        }

        $clientAction = new ClientsHistoryActions();
        $clientAction
            ->setNomForm($formName)
            ->setIdClient($client)
            ->setSerialize($serialize)
            ->setIP($ip);

        $this->entityManager->persist($clientAction);
        $this->entityManager->flush($clientAction);
    }

    /**
     * @param CompanyAddress|null $address
     * @param string              $type
     * @param array              $option
     *
     * @return FormBuilderInterface
     */
    public function getCompanyAddressFormBuilder(?CompanyAddress $address, string $type, $option = []): FormBuilderInterface
    {
        $formBuilder = $this->formFactory->createNamedBuilder($type, CompanyAddressType::class, null, $option);

        if (null !== $address) {
            $formBuilder->get('address')->setData($address->getAddress());
            $formBuilder->get('zip')->setData($address->getZip());
            $formBuilder->get('city')->setData($address->getCity());
            $formBuilder->get('idCountry')->setData($address->getIdCountry()->getIdPays());
        }

        return $formBuilder;
    }

    /**
     * @param Clients            $client
     * @param ClientAddress|null $address
     * @param string             $type
     * @param array              $option
     *
     * @return FormBuilderInterface
     */
    public function getClientAddressFormBuilder(Clients $client, ?ClientAddress $address, string $type, $option = []): FormBuilderInterface
    {
        $formBuilder = $this->formFactory->createNamedBuilder($type, ClientAddressType::class, null, $option);

        if (null !== $address) {
            $formBuilder->get('address')->setData($address->getAddress());
            $formBuilder->get('zip')->setData($address->getZip());
            $formBuilder->get('city')->setData($address->getCity());
            $formBuilder->get('idCountry')->setData($address->getIdCountry()->getIdPays());
        }

        if ($client->isLender() && AddressType::TYPE_MAIN_ADDRESS === $type) {
            $formBuilder
                ->add('housedByThirdPerson', CheckboxType::class, ['required' => false])
                ->add('noUsPerson', CheckboxType::class, ['data' => false === $client->getUsPerson(), 'required' => false]);
        }

        return $formBuilder;
    }
}
