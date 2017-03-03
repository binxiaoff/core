<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Unilend\Bundle\CoreBusinessBundle\Entity\Attachment;
use Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectAttachment;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\Transfer;
use Unilend\Bundle\CoreBusinessBundle\Entity\TransferAttachment;

class AttachmentManager
{

    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var string
     */
    private $uploadRoot;
    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var Finder
     */
    private $finder;

    public function __construct(EntityManager $entityManager, Filesystem $filesystem, Finder $finder, $uploadRoot)
    {
        $this->entityManager = $entityManager;
        $this->filesystem    = $filesystem;
        $this->finder        = $finder;
        $this->uploadRoot    = $uploadRoot;
    }

    /**
     * @param Clients        $client
     * @param AttachmentType $attachmentType
     *
     * @return bool|int
     */
    public function attachmentExists(Clients $client, AttachmentType $attachmentType)
    {
        $attachment = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Attachment')->findOneBy(['idClient' => $client, 'idType' => $attachmentType]);

        if ($attachment && empty($attachment->getPath())) {
            return $attachment->getId();
        } else {
            return false;
        }
    }

    /**
     * @param Clients        $client
     * @param AttachmentType $attachmentType
     * @param UploadedFile   $file
     * @param null|string    $name
     */
    public function upload(Clients $client, AttachmentType $attachmentType, UploadedFile $file, $name = null)
    {
        if (empty($client->getIdClient())) {
            throw new \InvalidArgumentException('Cannot find the upload destination. The client id is empty.');
        }

        $destination = $this->getUploadDestination($client);
        $file        = $file->move($destination, $name);
        $path        = ltrim($file->getPath(), $this->uploadRoot);
        $attachment  = new Attachment();
        $attachment->setPath($path)
                   ->setClient($client)
                   ->setType($attachmentType);
        $this->entityManager->persist($attachment);
        $this->entityManager->flush($attachment);
    }

    public function getFullPath(Attachment $attachment)
    {
        return $this->uploadRoot . $attachment->getPath();
    }

    public function attachToProject(Attachment $attachment, Projects $project)
    {
        $projectAttachment = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAttachment')->findOneBy(['idAttachment' => $attachment, 'idProject' => $project]);
        if (null === $projectAttachment) {
            $projectAttachment = new ProjectAttachment();
            $projectAttachment->setProject($project)
                              ->setAttachment($attachment);
            $this->entityManager->persist($projectAttachment);
            $this->entityManager->flush($projectAttachment);
        }
    }

    public function attachToTransfer(Attachment $attachment, Transfer $transfer)
    {
        $transferAttachment = $this->entityManager->getRepository('UnilendCoreBusinessBundle:TransferAttachment')->findOneBy(['idAttachment' => $attachment, 'idTransfer' => $transfer]);
        if (null === $transferAttachment) {
            $projectAttachment = new TransferAttachment();
            $projectAttachment->setTransfer($transfer)
                              ->setAttachment($attachment);
            $this->entityManager->persist($transferAttachment);
            $this->entityManager->flush($transferAttachment);
        }
    }

    /**
     * @param Clients $client
     *
     * @return string
     */
    private function getUploadDestination(Clients $client)
    {
        $hash = hash('sha256', $client->getIdClient());

        $destination = $this->uploadRoot . $hash[0] . DIRECTORY_SEPARATOR . $hash[1] . DIRECTORY_SEPARATOR . $client->getIdClient();

        if (false === is_dir($destination)) {
            $this->filesystem->mkdir($destination);
        }

        return $destination;
    }
}
