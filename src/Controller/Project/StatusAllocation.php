<?php

declare(strict_types=1);

namespace Unilend\Controller\Project;

use ApiPlatform\Core\Api\IriConverterInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Http\Client\Common\Exception\BatchException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Unilend\Entity\Clients;
use Unilend\Entity\Embeddable\NullableMoney;
use Unilend\Entity\Embeddable\OfferWithFee;
use Unilend\Entity\Project;
use Unilend\Entity\ProjectParticipation;
use Unilend\Entity\ProjectParticipationTranche;
use Unilend\Entity\ProjectStatus;
use Unilend\Entity\Tranche;
use Unilend\Exception\Staff\StaffNotFoundException;

class StatusAllocation
{
    /**
     * @var IriConverterInterface
     */
    private IriConverterInterface $iriConverter;
    /**
     * @var Security
     */
    private Security $security;
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $manager;
    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;
    /**
     * @var ValidatorInterface
     */
    private ValidatorInterface $validator;

    /**
     * StatusAllocation constructor.
     *
     * @param IriConverterInterface  $iriConverter
     * @param Security               $security
     * @param EntityManagerInterface $manager
     * @param SerializerInterface    $serializer
     */
    public function __construct(IriConverterInterface $iriConverter, Security $security, EntityManagerInterface $manager, SerializerInterface $serializer, ValidatorInterface $validator, DenormalizerInterface $denormalizer)
    {
        $this->iriConverter = $iriConverter;
        $this->security = $security;
        $this->manager = $manager;
        $this->serializer = new Serializer([$denormalizer]);
        $this->validator = $validator;
    }

    public function __invoke(Project $data, Request $request) {

        // mets la date à jour, crée le projectStatus correctement. s'assurer du reste dans le todo



        $client = $this->security->getUser();

        if (false === $client instanceof Clients || null === $client->getCurrentStaff()) {
            throw new StaffNotFoundException();
        }

        if ($data->getCurrentStatus()->getStatus() !== ProjectStatus::STATUS_DRAFT) {
            throw new BadRequestException();
        }

        $content = json_decode($request->getContent(),true);


        $projectParticipations = $content['projectParticipations'] ?? null;

        if (!$projectParticipations || false === is_array($projectParticipations)) {
            throw new BadRequestException('Invalide projectParticipations');
        }

        //@todo denormalization array of existing objects impossible
//        $participations = $this->serializer->denormalize($content['projectParticipations'], ProjectParticipation::class . '[]', 'array', [AbstractNormalizer::OBJECT_TO_POPULATE => $data->getProjectParticipations()]);
//        dump('after deserializer', $participations);

        if (count($projectParticipations) !== $data->getProjectParticipations()->count()) {
            throw new BadRequestException('La requête ne contient pas tous les participants sur le projet.');
        }

        foreach ($projectParticipations as $projectParticipationArray) {
            if (false === isset($projectParticipationArray['@id']) || false === is_string($projectParticipationArray['@id'])) {
                throw new BadRequestException('Invalid @id participant');
            }

            /** @var ProjectParticipation $projectParticipation */
            $projectParticipation = $this->iriConverter->getItemFromIri($projectParticipationArray['@id'], [AbstractNormalizer::GROUPS => []]);

            if (false === $data->getProjectParticipations()->contains($projectParticipation)) {
                throw new BadRequestException('La participant est invalide ou ne fait pas parti du projet.');
            }

            if (false === isset($projectParticipationArray['invitationRequest']) || false === is_array($projectParticipationArray['invitationRequest'])) {
                throw new BadRequestException('Invalid invitationRequest participant');
            }
            $invitationRequest = $projectParticipationArray['invitationRequest'];

            // validation manuelle identique pour money et feeRate =/

            $offerWithFee = $this->serializer->denormalize(['money' => $invitationRequest['money'], 'feeRate' => $invitationRequest['feeRate']], OfferWithFee::class,'array');
            $projectParticipation->setInvitationRequest($offerWithFee);

            foreach ($projectParticipationArray['tranches'] as $tranche) {
                $tranche = $this->iriConverter->getItemFromIri($tranche, [AbstractNormalizer::GROUPS => []]);

                if (false === $data->getTranches()->contains($tranche)) {
                    throw new BadRequestException("Une des tranches sélectionnée est invalide ou ne fait pas parti du projet.");
                }

                $projectParticipationTranche = new ProjectParticipationTranche($projectParticipation, $tranche, $client->getCurrentStaff());

                // valide que dalle
                $this->validator->validate($projectParticipationTranche);
                $this->manager->persist($projectParticipationTranche);
            }
        }

//        $projectStatus = new ProjectStatus($data, ProjectStatus::STATUS_ALLOCATION, $staff);
//        $this->manager->persist($projectStatus);

//        $this->manager->flush();

        return $data;
    }
}