<?php

return [
    'password_reset' => [
        'subject' => 'Réinitialisation de votre mot de passe',
        'line' => 'Vous recevez ce message parce qu\'une réinitialisation a été demandée pour votre compte.',
        'action' => 'Réinitialiser le mot de passe',
        'expires' => 'Ce lien expirera dans :minutes minutes.',
        'ignore' => 'Si vous n\'êtes pas à l\'origine de cette demande, vous pouvez ignorer ce message.',
    ],
    'invitation' => [
        'subject' => 'Vous êtes invité sur DailyApps',
        'line' => 'Un administrateur vous a ouvert un compte. Choisissez votre mot de passe pour l\'activer.',
        'action' => 'Activer mon compte',
        'expires' => 'Cette invitation expirera dans :days jours.',
    ],
];
