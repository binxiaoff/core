<?php

declare(strict_types=1);

namespace Unilend\Test\Hook;

use DG\BypassFinals;
use PHPUnit\Runner\BeforeTestHook;

final class BypassFinalHook implements BeforeTestHook
{
    /**
     * @param string $test
     */
    public function executeBeforeTest(string $test): void
    {
        BypassFinals::enable();
    }
}
