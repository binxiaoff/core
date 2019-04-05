<?php

namespace Unilend\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{AddressType, Attachment, AttachmentType, BankAccount, ClientAddress, Clients, Companies, CompanyAddress, GreenpointAttachment, Settings};
use Unilend\Form\LenderSubscriptionProfile\{BankAccountType, CompanyIdentityType, LegalEntityProfileType, OriginOfFundsType, PersonPhoneType, PersonProfileType};
use Unilend\Service\Front\LenderProfileFormsHandler;

class LenderDataUpdateController extends Controller
{
    /**
     * @Route("/profile/mise-a-jour", name="lender_data_update_start")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function startAction(?UserInterface $client): Response
    {
        $company = null;

        if (false === $client->isNaturalPerson()) {
            $company = $this->get('doctrine.orm.entity_manager')->getRepository(Companies::class)->findOneBy(['idClientOwner' => $client]);
        }

        return $this->render('lender_data_update/start.html.twig', [
            'client'  => $client,
            'company' => $company
        ]);
    }

    /**
     * @Route("/profile/mise-a-jour/details", name="lender_data_update_details")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function detailsAction(Request $request, ?UserInterface $client): Response
    {
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $formManager   = $this->get('unilend.frontbundle.service.form_manager');

        $unattachedClient     = clone $client;
        $company              = null;
        $unattachedCompany    = null;
        $bankAccount          = $entityManager->getRepository(BankAccount::class)->getLastModifiedBankAccount($client);
        $identityDocumentType = AttachmentType::CNI_PASSPORTE;
        $formErrors           = [];

        $formBuilder = $this->createFormBuilder()
            ->add('bankAccount', BankAccountType::class)
            ->add('fundsOrigin', OriginOfFundsType::class, ['data' => $client]);

        if (null !== $bankAccount) {
            $formBuilder->get('bankAccount')->get('iban')->setData($bankAccount->getIban());
            $formBuilder->get('bankAccount')->get('bic')->setData($bankAccount->getBic());
        }

        if ($client->isNaturalPerson()) {
            $lastModifiedMainAddress = $entityManager->getRepository(ClientAddress::class)->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_MAIN_ADDRESS);
            $addressForm             = $formManager->getClientAddressFormBuilder($client, $lastModifiedMainAddress, AddressType::TYPE_MAIN_ADDRESS);
            $formBuilder
                ->add('client', PersonProfileType::class, ['data' => $client])
                ->add('phone', PersonPhoneType::class, ['data' => $client]);
        } else {
            $identityDocumentType = AttachmentType::CNI_PASSPORTE_DIRIGEANT;
            $company              = $entityManager->getRepository(Companies::class)->findOneBy(['idClientOwner' => $client]);
            $unattachedCompany    = clone $company;

            $lastModifiedMainAddress = $entityManager->getRepository(CompanyAddress::class)->findLastModifiedNotArchivedAddressByType($company, AddressType::TYPE_MAIN_ADDRESS);
            $addressForm             = $formManager->getCompanyAddressFormBuilder($lastModifiedMainAddress, AddressType::TYPE_MAIN_ADDRESS);

            $formBuilder
                ->add('client', LegalEntityProfileType::class, ['data' => $client])
                ->add('company', CompanyIdentityType::class, ['data' => $company]);

            $formBuilder->get('company')
                ->remove('siren');
        }

        $form = $formBuilder
            ->add($addressForm)
            ->getForm();

        if ($request->isMethod(Request::METHOD_POST)) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $formsHandler = $this->get(LenderProfileFormsHandler::class);
                try {
                    if ($client->isNaturalPerson()) {
                        $formsHandler->handleAllPersonalData($client, $unattachedClient, $lastModifiedMainAddress, AddressType::TYPE_MAIN_ADDRESS, $form, $request->files);
                    } else {
                        $formsHandler->handleAllLegalEntityData($client, $unattachedClient, $company, $unattachedCompany, $lastModifiedMainAddress, AddressType::TYPE_MAIN_ADDRESS, $form, $request->files);
                    }

                    if ($form->isValid()) {
                        return $this->redirectToRoute('lender_data_update_end');
                    }
                } catch (\Exception $exception) {
                    $this->get('logger')->error('An error occurred while updating the lender data. Error message: ' . $exception->getMessage(), [
                        'id_client' => $client->getIdClient(),
                        'class'     => __CLASS__,
                        'function'  => __FUNCTION__,
                        'file'      => $exception->getFile(),
                        'line'      => $exception->getLine()
                    ]);
                }
            }

            $formErrors = $form->getErrors(true);
        }

        $attachmentRepository = $entityManager->getRepository(Attachment::class);
        /** @var Attachment $identityDocument */
        $identityDocument = $attachmentRepository->findOneBy([
            'idClient' => $client,
            'idType'   => $identityDocumentType,
            'archived' => null
        ]);

        $greenPointAttachmentDetails = null;
        if ($identityDocument) {
            if ($greenPointAttachment = $identityDocument->getGreenpointAttachment()) {
                if (GreenpointAttachment::STATUS_VALIDATION_VALID === $greenPointAttachment->getValidationStatus()) {
                    $greenPointAttachmentDetails = $greenPointAttachment->getGreenpointAttachmentDetail();
                }
            }
        }

        $kbis = null;
        if (false === $client->isNaturalPerson()) {
            $kbis = $attachmentRepository->findOneBy([
                'idClient' => $client,
                'idType'   => AttachmentType::KBIS,
                'archived' => null
            ]);
        }

        $externalCounselListSetting = $entityManager->getRepository(Settings::class)->findOneBy(['type' => 'Liste deroulante conseil externe de l\'entreprise']);
        $externalCounselList        = [];
        if ($externalCounselListSetting) {
            $externalCounselList = json_decode($externalCounselListSetting->getValue(), true);
        }

        return $this->render('lender_data_update/details.html.twig', [
            'client'                  => $client,
            'company'                 => $company,
            'identityDocument'        => $identityDocument,
            'kbis'                    => $kbis,
            'identityDocumentDetails' => $greenPointAttachmentDetails,
            'mainAddress'             => $lastModifiedMainAddress,
            'residenceAttachments'    => [
                AttachmentType::JUSTIFICATIF_DOMICILE         => $attachmentRepository->findOneClientAttachmentByType($client, AttachmentType::JUSTIFICATIF_DOMICILE),
                AttachmentType::ATTESTATION_HEBERGEMENT_TIERS => $attachmentRepository->findOneClientAttachmentByType($client, AttachmentType::ATTESTATION_HEBERGEMENT_TIERS),
                AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT => $attachmentRepository->findOneClientAttachmentByType($client, AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT),
                AttachmentType::JUSTIFICATIF_FISCAL           => $attachmentRepository->findOneClientAttachmentByType($client, AttachmentType::JUSTIFICATIF_FISCAL),
            ],
            'bankAccount'             => $bankAccount,
            'fundsOrigins'            => $this->get('unilend.service.lender_manager')->getFundsOrigins($client->getType()),
            'form'                    => $form->createView(),
            'formErrors'              => $formErrors,
            'externalCounselList'     => $externalCounselList
        ]);
    }

    /**
     * @Route("/profile/mise-a-jour/fin", name="lender_data_update_end")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function endAction(?UserInterface $client): Response
    {
        try {
            $needCipEvaluation = $this->get('unilend.service.cip_manager')->needReevaluation($client);
        } catch (\Exception $exception) {
            $needCipEvaluation = false;
            $this->get('logger')->error('Could not get lender CIP evaluation information. Error: ' . $exception->getMessage(), [
                'id_client' => $client->getIdClient(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine()
            ]);
        }

        $company = null;

        if (false === $client->isNaturalPerson()) {
            $company = $this->get('doctrine.orm.entity_manager')->getRepository(Companies::class)->findOneBy(['idClientOwner' => $client]);
        }

        return $this->render('lender_data_update/end.html.twig', [
            'needCipEvaluation' => $needCipEvaluation,
            'client'            => $client,
            'company'           => $company,
        ]);
    }
}
