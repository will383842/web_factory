<?php

declare(strict_types=1);

namespace App\Settings;

use App\Application\Catalog\Services\BriefDefaultsInjector;
use Spatie\LaravelSettings\Settings;

/**
 * Sprint 28 — Editable preambles injected at the top of every uploaded
 * brief's CLAUDE.md by {@see BriefDefaultsInjector}.
 *
 * Initial values come from `resources/brief-defaults/0X-*.md` files in the
 * repo. Edited live from `/admin/manage-brief-defaults`. The next time you
 * upload a brief, the new values are prepended to its CLAUDE.md before
 * Claude Code ever reads it.
 *
 * Group `brief_defaults` (table `settings`, cached).
 */
final class BriefDefaultsSettings extends Settings
{
    public bool $injectionEnabled;

    public string $design;

    public string $production;

    public string $accessibility;

    public string $seoAeo;

    public string $security;

    // Sprint 30 — completeness pack
    public string $pagesLayout;

    public string $components;

    public string $contentEngine;

    public string $authAccount;

    public string $comms;

    public static function group(): string
    {
        return 'brief_defaults';
    }
}
