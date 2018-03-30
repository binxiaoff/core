<?php

class viewerController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess();
    }

    public function _default()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $this->projects = $this->loadData('projects');

        if (isset($this->params[0]) && $this->projects->get($this->params[0], 'id_project')) {
            $this->projectEntity = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($this->projects->id_project);
            $this->attachments = $this->projectEntity->getAttachments();

            $this->documents = [];
            foreach ( $this->attachments as $attachment )
            {
                /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\ProjectAttachment $attachment */
                $currentAttachment = $attachment->getAttachment();

                $document = [
                    'id' => $currentAttachment->getId(),
                    'name' => empty($currentAttachment->getOriginalName()) ? $currentAttachment->getPath() : $currentAttachment->getOriginalName(),
                    'url' => $this->url . '/attachment/download/id/' . $currentAttachment->getId() . '/file/' . urlencode($currentAttachment->getPath()),
                    'isSelected' => $this->params[1] == $currentAttachment->getId(),
                ];

                $this->documents[] = $document;
            }

            $this->render(null, [
                'documents' => $this->documents,
            ]);
        } else {
            header('Location: ' . $this->lurl);
            die;
        }
    }
}
