<?php

declare(strict_types=1);

namespace KLS\Core\DTO;

use Symfony\Component\Serializer\Annotation\Groups;

/**
 * The reason why I create a class is that the NDA fields need to be operated as a whole: we need to send all these fields at a time.
 * I continue to use the projectParticipationMember as the group prefix since it is used only in ProjectParticipationMember.
 */
class AcceptedNDA
{
    private string $fileVersionId = '';

    /**
     * @Groups({"projectParticipationMember:owner:write"})
     */
    private string $term = '';

    /**
     * @Groups({"projectParticipationMember:owner:write"})
     */
    public function getFileVersionId(): string
    {
        return $this->fileVersionId;
    }

    public function getTerm(): string
    {
        return $this->term;
    }

    public function setFileVersionId(string $fileVersionId): AcceptedNDA
    {
        $this->fileVersionId = $fileVersionId;

        return $this;
    }

    public function setTerm(string $term): AcceptedNDA
    {
        $this->term = $term;

        return $this;
    }
}
