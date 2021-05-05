<?php

declare(strict_types=1);

namespace Unilend\Core\Service\Jwt;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;

class StaffPayloadManager implements PayloadManagerInterface
{
    private IriConverterInterface $iriConverter;

    public function __construct(IriConverterInterface $iriConverter)
    {
        $this->iriConverter = $iriConverter;
    }

    /**
     * To handle case where the staff is disabled whereas user is still connected
     * This will disconnect him the next time the user attempts to access the api after its token has been disabled.
     *
     * @param mixed $payload
     */
    public function isPayloadValid(array $payload): bool
    {
        if (isset($payload['staff'])) {
            try {
                /** @var Staff $staff */
                $staff = $this->iriConverter->getItemFromIri($payload['staff'], [AbstractNormalizer::GROUPS => []]);
            } catch (ItemNotFoundException $exception) {
                return false;
            }

            if (false === ($staff instanceof Staff && $staff->isGrantedLogin())) {
                return false;
            }
        }

        return true;
    }

    public function updateSecurityToken(TokenInterface $token, array $payload): void
    {
        if (isset($payload['staff'])) {
            /** @var Staff $currentStaff */
            $currentStaff = $this->iriConverter->getItemFromIri($payload['staff'], [AbstractNormalizer::GROUPS => []]);

            // Legacy way of storing current staff for security
            $token->getUser()->setCurrentStaff($currentStaff);

            $token->setAttribute('staff', $currentStaff);
            $token->setAttribute('company', $currentStaff->getCompany());
        }
    }

    public static function getScope(): string
    {
        return 'staff';
    }

    /**
     * @return iterable|array
     */
    public function getPayloads(User $user): iterable
    {
        foreach ($user->getStaff() as $staff) {
            if ($staff->isGrantedLogin()) {
                yield ['staff' => $this->iriConverter->getIriFromItem($staff)];
            }
        }
    }
}
