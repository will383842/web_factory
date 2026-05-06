<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Generated Projects Workspace
    |--------------------------------------------------------------------------
    |
    | Sprint 27 — when a brief ZIP is uploaded through the admin, WebFactory
    | extracts it on the host filesystem at `host_path/{slug}/` so Claude Code
    | (running natively on the developer's machine, NOT inside Docker) can
    | read/write the same files that wf-app sees through the bind mount.
    |
    | host_path      → path as the developer types it in their terminal
    |                  (e.g. `cd C:\Users\willi\Documents\Projets\my-project`).
    |                  Used for display and "copy path" buttons in the UI.
    |
    | container_path → path as PHP sees it from inside wf-app. Used for all
    |                  filesystem ops (ZipArchive::extractTo, file_put_contents).
    |                  These two MUST point at the same directory on disk —
    |                  enforced by the bind mount in docker-compose.yml.
    |
    */

    'projects' => [
        'host_path' => env('WEBFACTORY_PROJECTS_HOST_PATH', 'C:/Users/willi/Documents/Projets'),
        'container_path' => env('WEBFACTORY_PROJECTS_CONTAINER_PATH', '/host/projects'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Git push (Sprint 2 of the local workflow)
    |--------------------------------------------------------------------------
    |
    | When a project is finished generating in Claude Code, the developer
    | clicks "Push to GitHub" in the admin. WebFactory runs `git init / add
    | / commit / remote / push` inside wf-app against the workspace folder.
    |
    | Authentication: if `pat` is set, it is injected into the HTTPS URL as
    | `https://x-access-token:<pat>@github.com/owner/repo.git` for the push,
    | then the remote URL is reset to the clean form so the token does not
    | persist on disk. Without a PAT, the push command is shown to the
    | developer who runs it manually from their host terminal.
    |
    */

    'git' => [
        'pat' => env('WEBFACTORY_GH_TOKEN'),
        'user_name' => env('WEBFACTORY_GIT_USER_NAME', 'WebFactory'),
        'user_email' => env('WEBFACTORY_GIT_USER_EMAIL', 'webfactory@local'),
        'default_branch' => env('WEBFACTORY_GIT_DEFAULT_BRANCH', 'main'),
        'commit_message' => env('WEBFACTORY_GIT_COMMIT_MESSAGE', 'Initial generation by Claude Code via WebFactory'),
    ],

];
