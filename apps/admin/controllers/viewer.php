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
                $project = $entityManager->getRepository(Projects::class)->find($entityId);

                if (null === $project) {
                    header('Location: ' . $this->lurl);
                    exit;
                }

                $hasCategories = true;
                $attachments   = $entityManager->getRepository(ProjectAttachment::class)->getAttachedAttachmentsWithCategories($project);
                break;
            case 'client':
                $hasCategories      = false;
                $attachments        = [];
                $attachmentEntities = $entityManager->getRepository(Attachment::class)->findBy([
                    'idClient' => $entityId,
                    'archived' => null
                ]);

                foreach ($attachmentEntities as $attachment) {
                    $attachments[] = [
                        'typeName'     => $attachment->getType()->getLabel(),
                        'attachmentId' => $attachment->getId(),
                        'path'         => $attachment->getPath(),
                        'originalName' => $attachment->getOriginalName(),
                        'downloadable' => $attachment->getType()->getDownloadable()
                    ];
                }

                usort($attachments, function ($first, $second) {
                    return strcmp($first['typeName'], $second['typeName']);
                });
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
