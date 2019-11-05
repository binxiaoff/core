<?php

declare(strict_types=1);

namespace Unilend\Controller\Attachment;

use ApiPlatform\Core\Api\IriConverterInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Security;
use Unilend\Entity\Attachment;
use Unilend\Service\Attachment\AttachmentManager;

class Upload
{
    /** @var AttachmentManager */
    private $attachmentManager;
    /** @var Security */
    private $security;
    /** @var IriConverterInterface */
    private $converter;

    /**
     * @param AttachmentManager     $attachmentManager
     * @param Security              $security
     * @param IriConverterInterface $converter
     */
    public function __construct(AttachmentManager $attachmentManager, Security $security, IriConverterInterface $converter)
    {
        $this->attachmentManager = $attachmentManager;
        $this->security          = $security;
        $this->converter         = $converter;
    }

    /**
     * @param Request $request
     *
     * @throws Exception
     *
     * @return Attachment
     */
    public function __invoke(Request $request): Attachment
    {
        $user = $this->security->getUser();

        if ($userIri = $request->request->get('user')) {
            if (false === $this->security->isGranted('ROLE_ADMIN')) {
                throw new AccessDeniedHttpException();
            }
            $user = $this->converter->getItemFromIri($userIri);
        }

        $type = $request->request->get('type');
        $type = $type ? $this->converter->getItemFromIri($type) : null;

        $company = $request->request->get('company');
        $company = $company ? $this->converter->getItemFromIri($company) : null;

        return $this->attachmentManager->upload(
            $request->files->get('file'),
            $user,
            $type,
            $company,
            $request->request->get('description')
        );
    }
}
