<?php

declare(strict_types=1);

namespace KLS\Core\Service\FileInput;

use KLS\Core\DTO\FileInput;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\File;
use KLS\Core\Entity\Staff;
use RuntimeException;
use Symfony\Component\Security\Core\Security;

trait FileInputDataUploadTrait
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    private static function denyUploadExistingFile(FileInput $request, File $existingFile, object $targetEntity): void
    {
        throw new RuntimeException(\sprintf(
            'There is already a %s with id %s on the %s %s. You can only update its version',
            $request->type,
            $existingFile->getPublicId(),
            \get_class($targetEntity),
            \method_exists($targetEntity, 'getPublicId') ? $targetEntity->getPublicId() : '',
        ));
    }

    private function getCurrentStaff(): ?Staff
    {
        $token = $this->security->getToken();

        return $token && $token->hasAttribute('staff') ? $token->getAttribute('staff') : null;
    }

    private function getCurrentCompany(): ?Company
    {
        $token = $this->security->getToken();

        return $token && $token->hasAttribute('company') ? $token->getAttribute('company') : null;
    }
}
