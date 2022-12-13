<?php

namespace Cloakings\Tests\CloakingsMagicChecker;

use Cloakings\CloakingsMagicChecker\MagicCheckerParams;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class MagicCheckerParamsTest extends TestCase
{
    public function testCreateFromRequest(): void
    {
        $request = new Request(server: ['aaa' => 'bbb']);

        $params = MagicCheckerParams::createFromRequest($request);

        self::assertSame(['aaa' => 'bbb'], $params->all());
    }
}
