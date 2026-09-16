# CLAUDE.md — dailyapps-sso-api

Laravel backend of **DailyApps SSO**. Part of the `dailyapps-sso` spec-kit
workspace: open the agent session at the workspace root, one level up, never
here — specs live above this directory.

## Status

The repository is empty: the Laravel application is not scaffolded yet. Scaffold
it with `laravel:osdd-scaffolding` (domain-isolated OSDD layers, the default for
new Xefi Laravel projects), then run `boost:install` inside this repo.

`boost:install` owns this file from that point on — it writes the guidelines
block and installs the skills the project's packages ship. Do not hand-extend
this file with conventions in the meantime; the install would overwrite them.

## Install, run, test

Not yet applicable — no `composer.json`, no `artisan`. Once the application is
scaffolded, this section is `boost:install`'s to fill.

## Commits

Trunk is `main`. Feature branches are cut from it by the workspace's
`speckit.multirepo.branch` hook, one per spec-kit feature.
