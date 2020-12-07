<?php

declare(strict_types=1);

namespace Unilend\Syndication\Entity\Versioned;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;

/**
 * @ORM\Table(
 *     name="syndication_zz_versioned_project_participation",
 *     indexes={
 *         @ORM\Index(columns={"logged_at"}),
 *         @ORM\Index(columns={"username"}),
 *         @ORM\Index(columns={"object_id", "object_class", "version"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="Gedmo\Loggable\Entity\Repository\LogEntryRepository")
 */
class VersionedProjectParticipation extends AbstractLogEntry
{
}
