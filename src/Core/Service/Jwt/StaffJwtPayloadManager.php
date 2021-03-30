<?php

declare(strict_types=1);

namespace Unilend\Core\Service\JWT;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\{Event\AuthenticationSuccessEvent, Event\JWTAuthenticatedEvent, Event\JWTCreatedEvent, Event\JWTDecodedEvent, Events as JwtEvents,
    Services\JWTTokenManagerInterface};
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Unilend\Core\Entity\{Staff, User};
use Unilend\Core\Repository\UserRepository;

class StaffJwtPayloadManager implements JwtPayloadManagerInterface
{
    /** @var IriConverterInterface */
    private IriConverterInterface $iriConverter;

    /**
     * @param IriConverterInterface $iriConverter
     */
    public function __construct(IriConverterInterface $iriConverter)
    {
        $this->iriConverter = $iriConverter;
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return 'staff';
    }

    /**
     * @inheritDoc
     */
    public function isTokenPayloadValid(array $payload): bool
    {
        if (false === isset($payload['staff'])) {
            return false;
        }

        try {
            /** @var Staff $staff */
            $staff = $this->iriConverter->getItemFromIri($payload['staff'], [AbstractNormalizer::GROUPS => []]);
        } catch (ItemNotFoundException $exception) {
            return false;
        }

        return $staff instanceof Staff && $staff->isGrantedLogin();
    }

    /**
     * @inheritDoc
     */
    public function generatePayloads(User $user): iterable
    {
        $staffCollection = $user->getStaff();

        /** @var Staff $staffEntry */
        foreach ($staffCollection as $staffEntry) {
            if ($staffEntry->isGrantedLogin()) {
                yield ['staff' => $this->iriConverter->getIriFromItem($staffEntry), ];
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function updateSecurityToken(TokenInterface $token, array $payload): void
    {
        /** @var Staff $currentStaff */
        $currentStaff = $this->iriConverter->getItemFromIri($payload['staff'], [AbstractNormalizer::GROUPS => []]);

        // Legacy way of storing current staff for security
        $token->getUser()->setCurrentStaff($currentStaff);

        $token->setAttribute('staff', $currentStaff);
        $token->setAttribute('company', $currentStaff->getCompany());
    }
}
