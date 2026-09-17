<?php

namespace Technical\Oidc\Listeners;

use Technical\Oidc\Events\ApplicationEnteredSession;

class RecordSessionParticipant
{
    /**
     * The participants of a session are the exact list of applications to warn
     * when it closes — recorded at the moment each one is let in, not guessed
     * afterwards from the tokens.
     */
    public function handle(ApplicationEnteredSession $event): void
    {
        $event->session->admit($event->application);
    }
}
