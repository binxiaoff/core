<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Controller\Reporting\FinancingObject;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Reader\Exception\ReaderNotOpenedException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\Mapping\MappingException;
use KLS\Core\DTO\FileInput;
use KLS\Core\Entity\Staff;
use KLS\CreditGuaranty\FEI\Entity\StaffPermission;
use KLS\CreditGuaranty\FEI\Service\FileInput\FileInputFinancingObjectUploader;
use KLS\CreditGuaranty\FEI\Service\StaffPermissionManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

class UpdateByFile
{
    /**
     * @throws IOException
     * @throws ReaderNotOpenedException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws MappingException
     */
    public function __invoke(
        Request $request,
        Security $security,
        StaffPermissionManager $staffPermissionManager,
        FileInputFinancingObjectUploader $fileInputFinancingObjectUploader
    ): JsonResponse {
        $file = $request->files->get('file');

        if (false === $file instanceof UploadedFile) {
            throw new BadRequestHttpException();
        }

        if (false === \in_array($file->getMimeType(), FileInput::ACCEPTED_MEDIA_TYPE, true)) {
            throw new BadRequestHttpException(\sprintf(
                '%s is not an acceptable media type',
                $file->getMimeType()
            ));
        }

        $token = $security->getToken();
        /** @var Staff|null $staff */
        $staff = ($token && $token->hasAttribute('staff')) ? $token->getAttribute('staff') : null;

        if (
            null === $staff
            || false === $staffPermissionManager->hasPermissions($staff, StaffPermission::PERMISSION_REPORTING)
        ) {
            throw new AccessDeniedException();
        }

        $response = $fileInputFinancingObjectUploader->upload($file);

        return new JsonResponse($response, Response::HTTP_OK);
    }
}
