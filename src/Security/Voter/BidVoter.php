<?php

declare(strict_types=1);

namespace Unilend\Security\Voter;

use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Unilend\Entity\{Bids, Clients, WalletType};
use Unilend\Repository\WalletRepository;
use Unilend\Traits\ConstantsAwareTrait;

class BidVoter extends Voter
{
    use ConstantsAwareTrait;

    public const ATTRIBUTE_MANAGE = 'manage';

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;
    /** @var WalletRepository */
    private $walletRepository;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param WalletRepository              $walletRepository
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, WalletRepository $walletRepository)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->walletRepository     = $walletRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        $attributes = self::getConstants('ATTRIBUTE_');

        if (false === in_array($attribute, $attributes)) {
            return false;
        }

        if (false === $subject instanceof Bids) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $bid, TokenInterface $token): bool
    {
        /** @var Clients $user */
        $user = $token->getUser();

        if (false === $user instanceof Clients) {
            return false;
        }

        switch ($attribute) {
            case self::ATTRIBUTE_MANAGE:
                return $this->canManage($bid, $user);
        }

        throw new LogicException('This code should not be reached');
    }

    /**
     * @param Bids    $bid
     * @param Clients $user
     *
     * @return bool
     */
    private function canManage(Bids $bid, Clients $user): bool
    {
        if ($this->authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_MANAGE_BIDS, $bid->getTranche()->getProject())) {
            return true;
        }

        $wallet = $this->walletRepository->getWalletByType($user, WalletType::LENDER);

        return null !== $wallet && $wallet === $bid->getWallet();
    }
}
