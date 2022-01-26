<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;
use KLS\Syndication\Agency\Entity\AgentBankAccount;
use Symfony\Component\Security\Core\Security;

class AgentBankAccountExtension implements QueryCollectionExtensionInterface
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
    ) {
        if (AgentBankAccount::class !== $resourceClass || $this->security->isGranted(User::ROLE_ADMIN)) {
            return;
        }

        $token = $this->security->getToken();

        /** @var Staff|null $staff */
        $staff = ($token && $token->hasAttribute('staff')) ? $token->getAttribute('staff') : null;

        if (false === ($staff instanceof Staff)) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        [0 => $agentBankAccountAlias] = $queryBuilder->getRootAliases();

        $agentAlias       = $queryNameGenerator->generateJoinAlias('agent');
        $agentMemberAlias = $queryNameGenerator->generateJoinAlias('agentMember');

        $managedUsersParameterName = $queryNameGenerator->generateParameterName('managedUsers');
        $companyParameterName      = $queryNameGenerator->generateParameterName('company');

        $queryBuilder->innerJoin("{$agentBankAccountAlias}.agent", $agentAlias)
            ->innerJoin("{$agentAlias}.members", $agentMemberAlias)
            ->andWhere(
                "{$agentMemberAlias}.user IN (:{$managedUsersParameterName})",
                "{$agentMemberAlias}.archivingDate IS NULL",
                "{$agentAlias}.company = :{$companyParameterName}"
            )
            ->setParameter($managedUsersParameterName, \iterator_to_array($staff->getManagedUsers(), false))
            ->setParameter($companyParameterName, $staff->getCompany())
        ;
    }
}
