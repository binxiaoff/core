<?php

declare(strict_types=1);

namespace Unilend\Controller;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\{ParamConverter, Security};
use Swift_RfcComplianceException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{RedirectResponse, Request, Response};
use Symfony\Component\Routing\{Annotation\Route, Router, RouterInterface};
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{AttachmentType, Clients, Project, ProjectAttachment, ProjectAttachmentSignature};
use Unilend\Repository\{AttachmentTypeRepository, CompaniesRepository, ProjectAttachmentSignatureRepository};
use Unilend\Service\{AttachmentManager, DemoMailerManager, ElectronicSignatureManager};

/**
 * @Security("is_granted('ROLE_USER')")
 */
class SignatureController extends AbstractController
{
    /**
     * @Route("/signature/{projectAttachment}", name="signature_sign", requirements={"projectAttachment": "\d+"})
     *
     * @param ProjectAttachment                    $projectAttachment
     * @param UserInterface|Clients|null           $user
     * @param ProjectAttachmentSignatureRepository $signatureRepository
     * @param ElectronicSignatureManager           $signatureManager
     * @param AttachmentManager                    $attachmentManager
     * @param RouterInterface                      $router
     *
     * @throws OptimisticLockException
     * @throws ORMException
     *
     * @return RedirectResponse
     */
    public function sign(
        ProjectAttachment $projectAttachment,
        ?UserInterface $user,
        ProjectAttachmentSignatureRepository $signatureRepository,
        ElectronicSignatureManager $signatureManager,
        AttachmentManager $attachmentManager,
        RouterInterface $router
    ): RedirectResponse {
        /** @var ProjectAttachmentSignature $signature */
        $signature = $signatureRepository->findOneBy([
            'projectAttachment' => $projectAttachment,
            'signatory'         => $user,
        ]);

        if (null === $signature) {
            return $this->redirectToRoute('wallet');
        }

        $documentContent  = file_get_contents($attachmentManager->getFullPath($projectAttachment->getAttachment()));
        $signatureRequest = $signatureManager->createSignatureRequest(
            $user,
            'Signature électronique de votre document',
            $projectAttachment->getAttachment()->getOriginalName(),
            base64_encode($documentContent),
            'pdf',
            '330',
            '520',
            $router->generate('signature_confirmation', ['projectAttachment' => $projectAttachment->getId()], Router::ABSOLUTE_URL)
        );

        $signature->setDocusignEnvelopeId((int) $signatureRequest['envelope']);
        $signatureRepository->save($signature);

        return new RedirectResponse($signatureRequest['url']);
    }

    /**
     * @Route("/signature/confirmation/{projectAttachment}", name="signature_confirmation", requirements={"projectAttachment": "\d+"})
     *
     * @param ProjectAttachment                    $projectAttachment
     * @param UserInterface|Clients|null           $user
     * @param ProjectAttachmentSignatureRepository $signatureRepository
     * @param Request                              $request
     *
     * @throws OptimisticLockException
     * @throws ORMException
     *
     * @return Response
     */
    public function confirmation(
        ProjectAttachment $projectAttachment,
        ?UserInterface $user,
        ProjectAttachmentSignatureRepository $signatureRepository,
        Request $request
    ): Response {
        /** @var ProjectAttachmentSignature $signature */
        $signature = $signatureRepository->findOneBy([
            'projectAttachment' => $projectAttachment,
            'signatory'         => $user,
        ]);

        if (null === $signature) {
            return $this->redirectToRoute('wallet');
        }

        switch ($request->query->get('event')) {
            case ElectronicSignatureManager::RECIPIENT_ACTION_SIGNING_COMPLETE:
                $signature->setStatus(ProjectAttachmentSignature::STATUS_SIGNED);
                $signatureRepository->save($signature);

                break;
        }

        // @todo retrieve document content
        // @todo check event status

        return $this->render('signature/confirmation.html.twig');
    }

    /**
     * @Route("/signature/chargement/{project}", name="signature_upload", requirements={"project": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     *
     * @ParamConverter("project", options={"mapping": {"project": "hash"}})
     *
     * @param Project                              $project
     * @param Request                              $request
     * @param UserInterface|Clients|null           $user
     * @param AttachmentTypeRepository             $attachmentTypeRepository
     * @param CompaniesRepository                  $companyRepository
     * @param ProjectAttachmentSignatureRepository $signatureRepository
     * @param AttachmentManager                    $attachmentManager
     * @param DemoMailerManager                    $mailerManager
     *
     * @throws Exception
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
        ProjectAttachmentSignatureRepository $signatureRepository,
        AttachmentManager $attachmentManager,
        DemoMailerManager $mailerManager
    ): Response {
        $file               = $request->files->get('extraElectronicSignature');
        $fileType           = $request->request->get('filetype')['electronicSignature'];
        $fileName           = $request->request->get('filename')['electronicSignature'];
        $signatoryCompanies = $request->request->get('signatory');

        if (false === is_array($signatoryCompanies)) {
            return $this->redirectToRoute('demo_project_details', ['hash' => $project->getHash()]);
        }

        foreach ($signatoryCompanies as $signatoryCompanyId) {
            if (false === is_numeric($signatoryCompanyId)) {
                return $this->redirectToRoute('demo_project_details', ['hash' => $project->getHash()]);
            }
        }

        if (false === empty($file) && false === empty($fileType) && false === empty($signatoryCompanies)) {
            /** @var AttachmentType|null $attachmentType */
            $attachmentType = $attachmentTypeRepository->find($request->request->get('filetype')['electronicSignature']);

            if ($attachmentType) {
                $attachment        = $attachmentManager->upload($user, $user->getCompany(), $user, $attachmentType, null, $file, false, $fileName);
                $projectAttachment = $attachmentManager->attachToProject($attachment, $project);

                foreach ($signatoryCompanies as $signatoryCompanyId) {
                    $signatory = $companyRepository->find($signatoryCompanyId)->getIdClientOwner();
                    $signature = new ProjectAttachmentSignature();
                    $signature
                        ->setProjectAttachment($projectAttachment)
                        ->setSignatory($signatory)
                        ->setStatus(ProjectAttachmentSignature::STATUS_PENDING)
                    ;

                    $signatureRepository->save($signature);

                    $mailerManager->sendElectronicSignature($signature);
                }
            }
        }

        return $this->redirectToRoute('demo_project_details', ['hash' => $project->getHash()]);
    }
}
