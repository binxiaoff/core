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
use Unilend\Entity\Clients;
use Unilend\Entity\Project;
use Unilend\Entity\ProjectStatus;
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
     * @var ObjectManager
     */
    private ObjectManager $manager;

    /**
     * StatusAllocation constructor.
     *
     * @param IriConverterInterface  $iriConverter
     * @param Security               $security
     * @param EntityManagerInterface $manager
     */
    public function __construct(IriConverterInterface $iriConverter, Security $security, EntityManagerInterface $manager)
    {
        $this->iriConverter = $iriConverter;
        $this->security = $security;
        $this->manager = $manager;
    }

    public function __invoke(Project $data, Request $request) {

        // mets la date à jour, crée le projectStatus correctement. s'assurer du reste dans le todo



        $client = $this->security->getUser();

        if (false === $client instanceof Clients || null === $staff = $client->getCurrentStaff()) {
            throw new StaffNotFoundException();
        }

        if ($data->getCurrentStatus()->getStatus() !== ProjectStatus::STATUS_DRAFT) {
            throw new BadRequestException();
        }

        $content = json_decode($request->getContent(),true);

        $projectParticipations = $content['projectParticipations'];

        if (count($projectParticipations) !== $data->getProjectParticipations()->count()) {
            throw new BadRequestException();
        }

        // @todo non testé, voir si les PP sont correctement update, les PPTranches correctement créées
        // + validation: 1. assurer que toutes les données soient presentes + valider $content['projectParticipations'], $projectParticipationArray['invitationRequest'], $invitationRequest['money']), $invitationRequest['feeRate'] = DTO?
//        foreach ($projectParticipations as $projectParticipationArray) {
//            /** @var ProjectParticipation $projectParticipation */
//            $projectParticipation = $this->iriConverter->getItemFromIri($projectParticipationArray['@id'], [AbstractNormalizer::GROUPS => []]);
//
//            // set invitation request
//            $invitationRequest = $projectParticipationArray['invitationRequest'];
//            $offerWithFee = new OfferWithFee(new NullableMoney($data->getGlobalFundingMoney()->getCurrency(), $invitationRequest['money']), $invitationRequest['feeRate']);
//            $projectParticipation->setInvitationRequest($offerWithFee);
//
//            // create ProjectParticipationTranches
//            $tranches = $projectParticipationArray['tranches'];
//            foreach ($tranches as $tranche) {
//                $projectParticipationTranche = new ProjectParticipationTranche($projectParticipation, $this->iriConverter->getItemFromIri($tranche, [AbstractNormalizer::GROUPS => []]), $this->security->getUser());
//                $this->manager->persist($projectParticipationTranche);
//            }
//        }

        $projectStatus = new ProjectStatus($data, ProjectStatus::STATUS_ALLOCATION, $staff);
        $this->manager->persist($projectStatus);

        $this->manager->flush();

        return $data;
    }
}