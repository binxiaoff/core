<?php

use Doctrine\ORM\EntityManager;

class viewerController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess();
    }

    public function _default()
    {
        if (empty($this->params[0]) || empty($this->params[1])) {
            header('Location: ' . $this->lurl);
            exit;
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $entityId      = filter_var($this->params[1], FILTER_VALIDATE_INT);
        $attachmentId  = empty($this->params[2]) ? null : filter_var($this->params[2], FILTER_VALIDATE_INT);

        switch ($this->params[0]) {
            case 'project':
                $project = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($entityId);

                if (null === $project) {
                    header('Location: ' . $this->lurl);
                    exit;
                }

                $hasCategories = true;
                $attachments   = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAttachment')->getAttachedAttachmentsWithCategories($project);
                break;
            case 'client':
                $hasCategories      = false;
                $attachments        = [];
                $attachmentEntities = $entityManager->getRepository('UnilendCoreBusinessBundle:Attachment')->findBy([
                    'idClient' => $entityId
                ]);

                foreach ($attachmentEntities as $attachment) {
                    $attachments[] = [
                        'typeId'       => $attachment->getType()->getId(),
                        'typeName'     => $attachment->getType()->getLabel(),
                        'attachmentId' => $attachment->getId(),
                        'path'         => $attachment->getPath(),
                        'originalName' => $attachment->getOriginalName(),
                    ];
                }
                break;
            default:
                header('Location: ' . $this->lurl);
                exit;
        }

        $this->render(null, [
            'documents'         => $attachments,
            'currentDocumentId' => $attachmentId,
            'hasCategories'     => $hasCategories
        ]);
    }
}
