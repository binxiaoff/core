<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\core\Loader;

class DevMigrateAttachmentCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('dev:migrate:attachment')
            ->setDescription('Modify the attachment path in DB');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \attachment $attachmentData */
        $attachmentData = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('attachment');
        /** @var \attachment_type $attachmentTypeData */
        $attachmentTypeData =$this->getContainer()->get('unilend.service.entity_manager')->getRepository('attachment_type');
        /** @var \attachment_helper $attachmentHelper */
        $attachmentHelper = Loader::loadLib('attachment_helper', array($attachmentData, $attachmentTypeData, $this->getContainer()->getParameter('kernel.root_dir') . '/../'));

        $offset = 0;
        $limit = 100;

        while ($attachments = $attachmentData->select('', '', $offset, $limit)) {
            $offset += $limit;
            /** @var \attachment $attachment */
            foreach ($attachments as $item) {
                $attachmentData->get($item['id']);
                $path = substr($attachmentHelper->getUploadPath($attachmentData->type_owner, $attachmentData->id_type), 10);
                $attachmentData->path = $path . $attachmentData->path;
                $attachmentData->update();
            }
        }
    }
}
