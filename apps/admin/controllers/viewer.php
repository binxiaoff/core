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

        if (
            false === empty($this->params[0]) && false === empty($this->params[1])
            && $project = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find(filter_var($this->params[0], FILTER_VALIDATE_INT))
        ) {
            $attachments = $project->getAttachments();

            $this->render(null, [
                'documents'         => $attachments,
                'currentDocumentId' => filter_var($this->params[1], FILTER_VALIDATE_INT),
            ]);
        } else {
            header('Location: ' . $this->lurl);
            die;
        }
    }
}
