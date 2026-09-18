<?php

namespace Technical\Oidc\Exceptions;

use Illuminate\Http\Response;
use RuntimeException;

class InvitationNoLongerValid extends RuntimeException
{
    public static function forToken(): self
    {
        return new self(
            'The invitation this token points at has expired, has already been spent, or was '
            .'never issued. The three are deliberately indistinguishable to the visitor.'
        );
    }

    /**
     * Gone, not refused: the invitation is over and asking for the same URL
     * again will not bring it back.
     */
    public function render(): Response
    {
        return response()->view('oidc::livewire.invitation-expired', status: 410);
    }
}
