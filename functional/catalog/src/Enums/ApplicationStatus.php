<?php

namespace Functional\Catalog\Enums;

enum ApplicationStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Retired = 'retired';

    public function isPublished(): bool
    {
        return $this === self::Published;
    }

    public function canTransitionTo(self $target): bool
    {
        return match ([$this, $target]) {
            [self::Draft, self::Published],
            [self::Published, self::Retired],
            [self::Retired, self::Published] => true,
            default => false,
        };
    }
}
