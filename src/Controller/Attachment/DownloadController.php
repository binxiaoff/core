<?php

declare(strict_types=1);

namespace Unilend\Controller\Attachment;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\{IsGranted, ParamConverter};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Entity\Attachment;
use Unilend\Service\AttachmentManager;

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
}
