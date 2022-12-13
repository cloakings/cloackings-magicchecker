<?php

namespace Cloakings\CloakingsMagicChecker;

// TODO: think why it may be needed
class MagicCheckerClickData
{
    public function __construct(
        public readonly string $clickId,
        public readonly string $moneyUrl,
        public readonly string $safeUrl,
        public readonly int $time,
        public readonly string $tp,
        public readonly string $mms,
        public readonly string $lls,
        public readonly bool $showFirst,
        public readonly bool $hideScript,
        public readonly bool $moneyRedirect, // 1 - true, 2 - false
        public readonly bool $safeRedirect, // 1 - true, 2 - false
        public readonly mixed $v1,
        public readonly mixed $v2,
    ) {
    }
}
