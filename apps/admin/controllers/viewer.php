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
        // Abort if insufficient param values given
        if (true === empty($this->params[0]) || true === empty($this->params[1]) || true === empty($this->params[2]))
        {
            header('Location: ' . $this->lurl);
            die;
        }

        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        // Sanitise params
        $entity_type = strtolower($this->params[0]);
        $entity_id = filter_var($this->params[1], FILTER_VALIDATE_INT);
        $attachment_id = filter_var($this->params[2], FILTER_VALIDATE_INT);

        // Get the appropriate attachments depending on the entity
        switch ($entity_type)
        {
            case 'project':
                if ($project = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($entity_id))
                {
                    $attachments = $project->getAttachments();
                }
                break;

            case 'client':
                $attachments = $entityManager->getRepository('UnilendCoreBusinessBundle:Attachment')->findBy([
                    'idClient' => $entity_id
                ]);
                break;
        }

        // Render only if has attachments
        if (false === empty($attachments)) {
            $this->render(null, [
                'documents'         => $attachments,
                'currentDocumentId' => $attachment_id,
            ]);
        } else {
            header('Location: ' . $this->lurl);
            die;
        }
        
    }
}
