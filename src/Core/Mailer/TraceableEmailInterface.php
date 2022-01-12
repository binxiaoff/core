<?php

declare(strict_types=1);

namespace KLS\Core\Mailer;

interface TraceableEmailInterface
{
    public function getMessageId(): ?string;
}
