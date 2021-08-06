<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Core\Entity\Interfaces\StatusInterface;
use Unilend\Core\Entity\Interfaces\TraceableStatusAwareInterface;
use Unilend\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use Unilend\Core\Traits\ConstantsAwareTrait;

/**
 * @ORM\Table(
 *     name="core_user_status",
 *     indexes={
 *         @ORM\Index(columns={"id_user"}, name="idx_user_status_id_user"),
 *         @ORM\Index(columns={"status"}, name="idx_user_status_status")
 *     }
 * )
 * @ORM\Entity(repositoryClass="Unilend\Core\Repository\UserStatusRepository")
 * @ORM\HasLifecycleCallbacks
 */
class UserStatus implements StatusInterface
{
    use ConstantsAwareTrait;
    use TimestampableAddedOnlyTrait;

    // The use is invited to our platform, the account is created in the database but the profil is not completed.
    public const STATUS_INVITED = 10;
    public const STATUS_CREATED = 20;

    public const GRANTED_LOGIN = [
        self::STATUS_INVITED,
        self::STATUS_CREATED,
    ];

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="statuses")
     * @ORM\JoinColumn(name="id_user", referencedColumnName="id", nullable=false)
     */
    private $user;

    /**
     * @var int
     *
     * @Groups({"user_status:read"})
     *
     * @ORM\Column(type="smallint")
     */
    private $status;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @throws \Exception
     */
    public function __construct(User $users, int $status)
    {
        if (!\in_array($status, static::getPossibleStatuses(), true)) {
            throw new InvalidArgumentException(
                \sprintf('%s is not a possible status for %s', $status, __CLASS__)
            );
        }
        $this->status = $status;
        $this->user   = $users;
        $this->added  = new DateTimeImmutable();
    }

    /**
     * Get idUser.
     */
    public function getUser(): User
    {
        return $this->user;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public static function getPossibleStatuses(): array
    {
        return static::getConstants('STATUS_');
    }

    /**
     * @return User|TraceableStatusAwareInterface
     */
    public function getAttachedObject()
    {
        return $this->getUser();
    }
}
