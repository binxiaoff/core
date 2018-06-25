<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\{
    Route, Security
};
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\{
    Request, Response
};
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    AddressType, Attachment, AttachmentType, Clients, GreenpointAttachment
};
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\{
    BankAccountType, ClientAddressType, OriginOfFundsType, PersonPhoneType, PersonProfileType
};
use Unilend\Bundle\FrontBundle\Security\User\UserLender;
use Unilend\Bundle\FrontBundle\Service\LenderProfileFormsHandler;

class LenderDataUpdateController extends Controller
{
    /**
     * @Route("/profile/mise-a-jour", name="lender_data_update_start")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @return Response
     */
    public function startAction(): Response
    {
        return $this->render('lender_data_update/start.html.twig', [
            'client' => $this->getClient()
        ]);
    }

    /**
     * @Route("/profile/mise-a-jour/details", name="lender_data_update_details")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function detailsAction(Request $request): Response
    {
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $client                  = $this->getClient();
        $unattachedClient        = clone $client;
        $lastModifiedMainAddress = $entityManager->getRepository('UnilendCoreBusinessBundle:ClientAddress')->findLastModifiedNotArchivedAddressByType($client, AddressType::TYPE_MAIN_ADDRESS);
        $bankAccount             = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getLastModifiedBankAccount($client);
        $unattachedBankAccount   = clone $bankAccount;

        $addressForm = $this->get('form.factory')->createNamedBuilder('mainAddress', ClientAddressType::class, [
            'address'   => $lastModifiedMainAddress->getAddress(),
            'zip'       => $lastModifiedMainAddress->getZip(),
            'city'      => $lastModifiedMainAddress->getCity(),
            'idCountry' => $lastModifiedMainAddress->getIdCountry()->getIdPays(),
        ]);
        $addressForm
            ->add('housedByThirdPerson', CheckboxType::class, ['required' => false])
            ->add('noUsPerson', CheckboxType::class, ['required' => false, 'data' => false === $client->getUsPerson()]);
        $form = $this->createFormBuilder()
            ->add('client', PersonProfileType::class, ['data' => $client])
            ->add('phone', PersonPhoneType::class, ['data' => $client])
            ->add($addressForm)
            ->add('bankAccount', BankAccountType::class, ['data' => $bankAccount])
            ->add('fundsOrigin', OriginOfFundsType::class, ['data' => $client])
            ->getForm();

        if ($request->isMethod(Request::METHOD_POST)) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $formsHandler = $this->get(LenderProfileFormsHandler::class);
                try {
                    $formsHandler->handleAllPersonalData($client, $unattachedClient, $lastModifiedMainAddress, AddressType::TYPE_MAIN_ADDRESS, $unattachedBankAccount, $form, $request->files);

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

                return $this->redirectToRoute('lender_data_update_details');
            }
        }

        $attachmentRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Attachment');
        /** @var Attachment $identityDocument */
        $identityDocument = $attachmentRepository->findOneBy([
            'idClient' => $client,
            'idType'   => AttachmentType::CNI_PASSPORTE,
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

        return $this->render('lender_data_update/details.html.twig', [
            'client'                  => $client,
            'identityDocument'        => $identityDocument,
            'identityDocumentDetails' => $greenPointAttachmentDetails,
            'clientMainAddress'       => $lastModifiedMainAddress,
            'residenceAttachments'    => [
                AttachmentType::JUSTIFICATIF_DOMICILE         => $attachmentRepository->findOneClientAttachmentByType($client, AttachmentType::JUSTIFICATIF_DOMICILE),
                AttachmentType::ATTESTATION_HEBERGEMENT_TIERS => $attachmentRepository->findOneClientAttachmentByType($client, AttachmentType::ATTESTATION_HEBERGEMENT_TIERS),
                AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT => $attachmentRepository->findOneClientAttachmentByType($client, AttachmentType::CNI_PASSPORT_TIERS_HEBERGEANT),
                AttachmentType::JUSTIFICATIF_FISCAL           => $attachmentRepository->findOneClientAttachmentByType($client, AttachmentType::JUSTIFICATIF_FISCAL),
            ],
            'bankAccount'             => $bankAccount,
            'fundsOrigins'            => $this->get('unilend.service.lender_manager')->getFundsOrigins($client->getType()),
            'form'                    => $form->createView()
        ]);
    }

    /**
     * @Route("/profile/mise-a-jour/fin", name="lender_data_update_end")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function endAction(): Response
    {
        try {
            $hasValidEvaluation = $this->get('unilend.service.cip_manager')->hasValidEvaluation($this->getClient());
        } catch (\Exception $exception) {
            $hasValidEvaluation = false;
            $this->get('logger')->error('Could not get lender CIP evaluation information. Error: ' . $exception->getMessage(), [
                'id_client' => $this->getClient()->getIdClient(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine()
            ]);
        }
        return $this->render('lender_data_update/end.html.twig', [
            'hasValidCipEvaluation' => $hasValidEvaluation
        ]);
    }

    /**
     * @return Clients
     */
    private function getClient(): Clients
    {
        /** @var UserLender $user */
        $user   = $this->getUser();
        $client = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients')->find($user->getClientId());

        return $client;
    }
}
