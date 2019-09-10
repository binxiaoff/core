<?php

declare(strict_types=1);

namespace Unilend\Controller\Attachment;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use League\Flysystem\FileNotFoundException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\{IsGranted, ParamConverter};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{BinaryFileResponse, Response, ResponseHeaderBag, StreamedResponse};
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Entity\{Attachment, Project};
use Unilend\Repository\ProjectAttachmentRepository;
use Unilend\Service\Attachment\AttachmentManager;
use URLify;
use ZipArchive;

class DownloadController extends AbstractController
{
    /**
     * @Route("/document/{id}/{originalName}", name="document_download", requirements={"id": "\d+", "originalName": ".+"})
     *
     * @IsGranted("download", subject="attachment")
     *
     * @ParamConverter("attachment", options={"mapping": {"id": "id", "originalName": "originalName"}})
     *
     * @param Attachment        $attachment
     * @param AttachmentManager $attachmentManager
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws FileNotFoundException
     *
     * @return StreamedResponse
     */
    public function download(Attachment $attachment, AttachmentManager $attachmentManager): StreamedResponse
    {
        $response = new StreamedResponse(static function () use ($attachment, $attachmentManager) {
            stream_copy_to_stream($attachmentManager->readStream($attachment), fopen('php://output', 'w+b'));
        });

        $contentDisposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, URLify::downcode($attachment->getOriginalName()));
        $response->headers->set('Content-Disposition', $contentDisposition);
        $response->headers->set('Content-Type', $attachmentManager->getMimeType($attachment) ?: 'application/octet-stream');

        $attachmentManager->logDownload($attachment);

        return $response;
    }

    /**
     * @Route("/documents/{slug}", name="documents_project")
     *
     * @IsGranted("view", subject="project")
     *
     * @param Project                     $project
     * @param ProjectAttachmentRepository $projectAttachmentRepository
     * @param AttachmentManager           $attachmentManager
     * @param string                      $temporaryDirectory
     *
     * @throws FileNotFoundException
     *
     * @return Response
     */
    public function project(
        Project $project,
        ProjectAttachmentRepository $projectAttachmentRepository,
        AttachmentManager $attachmentManager,
        string $temporaryDirectory
    ): Response {
        $zip      = new ZipArchive();
        $filename = $temporaryDirectory . $project->getSlug() . '.zip';

        if (true === $zip->open($filename, ZipArchive::CREATE)) {
            $projectAttachments = $projectAttachmentRepository->getAttachmentsWithoutSignature($project, ['added' => 'DESC']);

            foreach ($projectAttachments as $projectAttachment) {
                $attachment = $projectAttachment->getAttachment();
                $zip->addFromString($attachment->getOriginalName(), $attachmentManager->read($attachment));
            }

            $zip->close();

            $response = new BinaryFileResponse($filename);
            $response->deleteFileAfterSend(true);

            return $response;
        }

        return $this->redirectToRoute('lender_project_details', ['slug' => $project->getSlug()]);
    }
}
