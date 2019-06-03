<?php

declare(strict_types=1);

namespace Unilend\Controller\Attachment;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\{IsGranted, ParamConverter};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\{BinaryFileResponse, Response};
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Entity\{Attachment, Project};
use Unilend\Repository\ProjectAttachmentRepository;
use Unilend\Service\AttachmentManager;
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
     * @param Filesystem        $filesystem
     *
     * @return BinaryFileResponse
     */
    public function download(Attachment $attachment, AttachmentManager $attachmentManager, Filesystem $filesystem)
    {
        $path = $attachmentManager->getFullPath($attachment);

        if (false === $filesystem->exists($path)) {
            throw new FileNotFoundException(null, 0, null, $path);
        }

        $fileName = $attachment->getOriginalName() ?? basename($attachment->getPath());

        return $this->file($path, $fileName);
    }

    /**
     * @Route("/documents/{slug}", name="documents_project")
     *
     * @IsGranted("view", subject="project")
     *
     * @param Project                     $project
     * @param ProjectAttachmentRepository $projectAttachmentRepository
     * @param AttachmentManager           $attachmentManager
     * @param string                      $sharedTemporaryPath
     *
     * @return Response
     */
    public function project(
        Project $project,
        ProjectAttachmentRepository $projectAttachmentRepository,
        AttachmentManager $attachmentManager,
        string $sharedTemporaryPath
    ): Response {
        $zip      = new ZipArchive();
        $filename = $sharedTemporaryPath . $project->getSlug() . '.zip';

        if (true === $zip->open($filename, ZipArchive::CREATE)) {
            $projectAttachments = $projectAttachmentRepository->getAttachmentsWithoutSignature($project, ['added' => 'DESC']);

            foreach ($projectAttachments as $projectAttachment) {
                $attachment = $projectAttachment->getAttachment();
                $zip->addFile($attachmentManager->getFullPath($attachment), $attachment->getOriginalName());
            }

            $zip->close();

            $response = new BinaryFileResponse($filename);
            $response->deleteFileAfterSend(true);

            return $response;
        }

        return $this->redirectToRoute('lender_project_details', ['slug' => $project->getSlug()]);
    }
}
