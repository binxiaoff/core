<?php

declare(strict_types=1);

namespace KLS\Agency\Serializer\ContextBuilder;

use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use DateTimeImmutable;
use Exception;
use KLS\Agency\Entity\Participation;
use KLS\Agency\Entity\ParticipationMember;
use KLS\Agency\Entity\ParticipationTrancheAllocation;
use KLS\Agency\Entity\Project as AgencyProject;
use KLS\Agency\Entity\Tranche;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Staff;
use KLS\Syndication\Arrangement\Entity\Project as ArrangementProject;
use KLS\Syndication\Arrangement\Repository\ProjectRepository as ArrangementProjectRepository;
use KLS\Syndication\Arrangement\Security\Voter\ProjectVoter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class ProjectContextBuilder implements SerializerContextBuilderInterface
{
    public const IMPORT_QUERY_PARAMETER = 'import';

    private ArrangementProjectRepository $arrangementProjectRepository;
    private Security $security;
    private SerializerContextBuilderInterface $decorated;

    public function __construct(
        SerializerContextBuilderInterface $decorated,
        Security $security,
        ArrangementProjectRepository $arrangementProjectRepository
    ) {
        $this->arrangementProjectRepository = $arrangementProjectRepository;
        $this->security                     = $security;
        $this->decorated                    = $decorated;
    }

    /**
     * @throws Exception
     */
    public function createFromRequest(Request $request, bool $normalization, array $extractedAttributes = null): array
    {
        $token = $this->security->getToken();

        $staff = $token && $token->hasAttribute('staff') ? $token->getAttribute('staff') : null;

        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);

        if (false === ($staff instanceof Staff)) {
            return $context;
        }

        if (
            Request::METHOD_POST !== $request->getMethod()
            || AgencyProject::class !== $request->attributes->get('_api_resource_class')
            || 'api_agency_projects_post_collection' !== $request->attributes->get('_route')
        ) {
            return $context;
        }

        $arrangementProjectPublicId = $request->get(static::IMPORT_QUERY_PARAMETER);

        /** @var ArrangementProject $arrangementProject */
        $arrangementProject = $this->arrangementProjectRepository->findOneBy(['publicId' => $arrangementProjectPublicId]);

        if (null === $arrangementProject) {
            return $context;
        }

        if (false === $this->security->isGranted(ProjectVoter::ATTRIBUTE_VIEW, $arrangementProject)) {
            throw new AccessDeniedException();
        }

        if (false === $arrangementProject->isFinished()) {
            throw new BadRequestHttpException();
        }

        // Do not hesitate to move the following code (minus the context lines) to the entity should it be need elsewhere
        $agencyProject = new AgencyProject(
            $staff,
            $arrangementProject->getTitle(),
            $arrangementProject->getRiskGroupName(),
            clone $arrangementProject->getGlobalFundingMoney(),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            $arrangementProject
        );

        $agencyProject->getPrimaryParticipationPool()->setParticipationType($arrangementProject->getParticipationType());
        $agencyProject->getPrimaryParticipationPool()->setSyndicationType($arrangementProject->getSyndicationType());
        $agencyProject->getPrimaryParticipationPool()->setRiskType($arrangementProject->getRiskType());

        $agencyProject->setCompanyGroupTag($arrangementProject->getCompanyGroupTag());

        $allocations = [];

        foreach ($arrangementProject->getTranches() as $tranche) {
            $agencyTranche = new Tranche(
                $agencyProject,
                $tranche->getName(),
                $tranche->getColor(),
                $tranche->getLoanType(),
                $tranche->getRepaymentType(),
                $tranche->getDuration(),
                clone $tranche->getMoney(),
                clone $tranche->getRate()
            );

            $agencyProject->addTranche($agencyTranche);

            foreach ($tranche->getProjectParticipationTranches() as $participationTrancheAllocation) {
                $allocations[] = [
                    'money' => new Money(
                        $participationTrancheAllocation->getAllocation()->getMoney()->getCurrency() ?? $agencyProject->getGlobalFundingMoney()->getCurrency(),
                        $participationTrancheAllocation->getAllocation()->getMoney()->getAmount() ?? '0'
                    ),
                    'tranche'     => $agencyTranche,
                    'participant' => $participationTrancheAllocation->getProjectParticipation()->getParticipant(),
                ];
            }
        }

        foreach ($arrangementProject->getProjectParticipations() as $arrangementParticipation) {
            $finalAllocation = new Money(
                $arrangementParticipation->getTotalAllocation()->getCurrency() ?? $agencyProject->getGlobalFundingMoney()->getCurrency(),
                $arrangementParticipation->getTotalAllocation()->getAmount() ?? '0'
            );
            $agencyParticipation = $agencyProject->findParticipationByParticipant($arrangementParticipation->getParticipant());

            if (null === $agencyParticipation) {
                $agencyParticipation = new Participation(
                    $agencyProject->getPrimaryParticipationPool(),
                    $arrangementParticipation->getParticipant()
                );

                $agencyProject->addParticipation($agencyParticipation);
            }

            foreach ($arrangementParticipation->getProjectParticipationMembers() as $arrangementMember) {
                $agencyParticipation->addMember(new ParticipationMember($agencyParticipation, $arrangementMember->getStaff()->getUser()));
            }
        }

        foreach ($allocations as ['participant' => $participant, 'money' => $allocation, 'tranche' => $agencyTranche]) {
            if ($agencyTranche->isSyndicated()) {
                $participationTrancheAllocation = new ParticipationTrancheAllocation(
                    $agencyProject->findParticipationByParticipant($participant),
                    $agencyTranche,
                    $allocation
                );

                $participationTrancheAllocation->getParticipation()->addAllocation($participationTrancheAllocation);
                $participationTrancheAllocation->getTranche()->addAllocation($participationTrancheAllocation);
            }
        }

        $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $agencyProject;

        return $context;
    }
}
