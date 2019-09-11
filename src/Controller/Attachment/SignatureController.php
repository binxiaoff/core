<?php

declare(strict_types=1);

namespace Unilend\Controller\Attachment;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use League\Flysystem\FileNotFoundException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\{IsGranted, ParamConverter};
use Swift_RfcComplianceException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{RedirectResponse, Request, Response};
use Symfony\Component\Routing\{Annotation\Route, Router, RouterInterface};
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{Attachment, AttachmentSignature, AttachmentType, Clients, Project};
use Unilend\Repository\{AttachmentSignatureRepository, AttachmentTypeRepository, CompaniesRepository};
use Unilend\Service\{Attachment\AttachmentManager, Attachment\ProjectAttachmentManager, ElectronicSignatureManager, MailerManager};

class SignatureController extends AbstractController
{
    /**
     * @Route("/signature/{attachment}", name="signature_sign", requirements={"attachment": "\d+"})
     *
     * @IsGranted("download", subject="attachment")
     *
     * @param Attachment                    $attachment
     * @param UserInterface|Clients|null    $user
     * @param AttachmentSignatureRepository $signatureRepository
     * @param ElectronicSignatureManager    $signatureManager
     * @param AttachmentManager             $attachmentManager
     * @param RouterInterface               $router
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws FileNotFoundException
     *
     * @return RedirectResponse
     */
    public function sign(
        Attachment $attachment,
        ?UserInterface $user,
        AttachmentSignatureRepository $signatureRepository,
        ElectronicSignatureManager $signatureManager,
        AttachmentManager $attachmentManager,
        RouterInterface $router
    ): RedirectResponse {
        /** @var AttachmentSignature $signature */
        $signature = $signatureRepository->findOneBy([
            'attachment' => $attachment,
            'signatory'  => $user,
        ]);

        if (null === $signature || AttachmentSignature::STATUS_PENDING !== $signature->getStatus()) {
            return $this->redirectToRoute('wallet');
        }

        $documentContent  = $attachmentManager->read($attachment);
        $signatureRequest = $signatureManager->createSignatureRequest(
            $user,
            'Signature électronique de votre document',
            $attachment->getOriginalName(),
            base64_encode($documentContent),
            'pdf',
            '330',
            '520',
            $router->generate('signature_confirmation', ['attachment' => $attachment->getId()], Router::ABSOLUTE_URL)
        );

        $signature->setDocusignEnvelopeId((int) $signatureRequest['envelope']);
        $signatureRepository->save($signature);

        return new RedirectResponse($signatureRequest['url']);
    }

    /**
     * @Route("/signature/confirmation/{attachment}", name="signature_confirmation", requirements={"attachment": "\d+"})
     *
     * @IsGranted("download", subject="attachment")
     *
     * @param Attachment                    $attachment
     * @param Request                       $request
     * @param UserInterface|Clients|null    $user
     * @param AttachmentSignatureRepository $signatureRepository
     *
     * @throws OptimisticLockException
     * @throws ORMException
     *
     * @return Response
     */
    public function confirmation(
        Attachment $attachment,
        Request $request,
        ?UserInterface $user,
        AttachmentSignatureRepository $signatureRepository
    ): Response {
        /** @var AttachmentSignature $signature */
        $signature = $signatureRepository->findOneBy([
            'attachment' => $attachment,
            'signatory'  => $user,
        ]);

        if (null === $signature || AttachmentSignature::STATUS_PENDING !== $signature->getStatus()) {
            return $this->redirectToRoute('wallet');
        }

        switch ($request->query->get('event')) {
            case ElectronicSignatureManager::RECIPIENT_ACTION_SIGNING_COMPLETE:
                $signature->setStatus(AttachmentSignature::STATUS_SIGNED);
                $signatureRepository->save($signature);

                break;
        }

        // @todo retrieve document content
        // @todo check event status

        return $this->render('attachment/signature/confirmation.html.twig');
    }

    /**
     * @Route("/signature/chargement/{project}", name="signature_upload", requirements={"project": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     *
     * @IsGranted("edit", subject="project")
     *
     * @ParamConverter("project", options={"mapping": {"project": "hash"}})
     *
     * @param Project                       $project
     * @param Request                       $request
     * @param UserInterface|Clients|null    $user
     * @param AttachmentTypeRepository      $attachmentTypeRepository
     * @param CompaniesRepository           $companyRepository
     * @param AttachmentSignatureRepository $signatureRepository
     * @param AttachmentManager             $attachmentManager
     * @param ProjectAttachmentManager      $projectAttachmentManager
     * @param MailerManager                 $mailerManager
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Swift_RfcComplianceException
     *
     * @return Response
     */
    public function upload(
        Project $project,
        Request $request,
        ?UserInterface $user,
        AttachmentTypeRepository $attachmentTypeRepository,
        CompaniesRepository $companyRepository,
        AttachmentSignatureRepository $signatureRepository,
        AttachmentManager $attachmentManager,
        ProjectAttachmentManager $projectAttachmentManager,
        MailerManager $mailerManager
    ): Response {
        $file               = $request->files->get('extraElectronicSignature');
        $fileType           = $request->request->get('filetype')['electronicSignature'];
        $fileName           = $request->request->get('filename')['electronicSignature'];
        $signatoryCompanies = $request->request->get('signatory');

        if (false === is_array($signatoryCompanies)) {
            return $this->redirectToRoute('edit_project_details', ['hash' => $project->getHash()]);
        }

        foreach ($signatoryCompanies as $signatoryCompanyId) {
            if (false === is_numeric($signatoryCompanyId)) {
                return $this->redirectToRoute('edit_project_details', ['hash' => $project->getHash()]);
            }
        }

        if (false === empty($file) && false === empty($fileType) && false === empty($signatoryCompanies)) {
            /** @var AttachmentType|null $attachmentType */
            $attachmentType = $attachmentTypeRepository->find($request->request->get('filetype')['electronicSignature']);

            if ($attachmentType) {
                $attachment = $attachmentManager->upload($user, $user->getCompany(), $user, $attachmentType, null, $file, false, $fileName);

                $projectAttachmentManager->attachToProject($attachment, $project);

                foreach ($signatoryCompanies as $signatoryCompanyId) {
                    $signatory = $companyRepository->find($signatoryCompanyId)->getIdClientOwner();
                    $signature = new AttachmentSignature();
                    $signature
                        ->setAttachment($attachment)
                        ->setSignatory($signatory)
                        ->setStatus(AttachmentSignature::STATUS_PENDING)
                    ;

                    $signatureRepository->save($signature);

                    $mailerManager->sendElectronicSignature($project, $signature);
                }
            }
        }

        return $this->redirectToRoute('edit_project_details', ['hash' => $project->getHash()]);
    }
}
