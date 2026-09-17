<?php

namespace Functional\Catalog\Actions;

use Functional\Catalog\Models\Application;
use Functional\Users\Models\User;
use Technical\Audit\Actions\RecordSecurityEvent;
use Technical\Audit\Enums\SecurityEventType;

class PublishApplication
{
    public function __construct(private readonly RecordSecurityEvent $record) {}

    public function __invoke(Application $application, ?User $publishedBy = null): Application
    {
        $application->publish();

        ($this->record)(
            SecurityEventType::ApplicationPublished,
            actor: $publishedBy,
            subject: $application,
            payload: ['application' => $application->slug],
        );

        return $application->fresh();
    }
}
