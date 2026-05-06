<?php

declare(strict_types=1);

namespace App\Application\Catalog\Services;

use App\Settings\BriefDefaultsSettings;

/**
 * Sprint 28 — prepends 5 standardized preambles (design, production,
 * accessibility, SEO/AEO, security) to a brief's CLAUDE.md so every
 * generated platform shares the same baseline of quality regardless of
 * how detailed the brief itself is.
 *
 * Order is fixed (design → production → a11y → SEO → security → original
 * CLAUDE.md content) because LLMs weight earlier-document instructions
 * more than later ones. Design first means the visual specs are read
 * before Claude Code ever sees the brief's "feature list".
 *
 * Idempotency: if the marker `<!-- WEBFACTORY_DEFAULTS_INJECTED -->` is
 * already present, no-op — re-imports don't double-inject.
 */
final class BriefDefaultsInjector
{
    private const MARKER = '<!-- WEBFACTORY_DEFAULTS_INJECTED -->';

    public function __construct(private readonly BriefDefaultsSettings $settings) {}

    /**
     * Returns the augmented CLAUDE.md content. If injection is disabled or
     * the original is empty, returns the original verbatim.
     */
    public function augment(string $originalClaudeMd): string
    {
        if (! $this->settings->injectionEnabled) {
            return $originalClaudeMd;
        }

        if (str_contains($originalClaudeMd, self::MARKER)) {
            return $originalClaudeMd;
        }

        $sections = array_filter([
            // Quality pack (Sprint 28)
            $this->settings->design,
            $this->settings->production,
            $this->settings->accessibility,
            $this->settings->seoAeo,
            $this->settings->security,
            // Completeness pack (Sprint 30) — pages, components, content, auth, comms
            $this->settings->pagesLayout,
            $this->settings->components,
            $this->settings->contentEngine,
            $this->settings->authAccount,
            $this->settings->comms,
        ], static fn (string $s): bool => trim($s) !== '');

        if ($sections === []) {
            return $originalClaudeMd;
        }

        $preamble = self::MARKER."\n\n";
        $preamble .= "<!-- The following 10 sections were auto-prepended by WebFactory.\n";
        $preamble .= "     They define both the QUALITY baseline (design 2026, production-readiness,\n";
        $preamble .= "     a11y WCAG 2.2 AA, SEO/AEO 2026, OWASP security) and the COMPLETENESS\n";
        $preamble .= "     baseline (pages & layout, UI components, content engine, auth flows,\n";
        $preamble .= "     transactional comms) every generated platform must respect.\n";
        $preamble .= "     Edit them at /admin/manage-brief-defaults in the WebFactory admin. -->\n\n";
        $preamble .= implode("\n\n---\n\n", $sections);
        $preamble .= "\n\n---\n\n# ORIGINAL BRIEF (what the developer uploaded)\n\n";

        return $preamble.$originalClaudeMd;
    }

    /**
     * Convenience: read CLAUDE.md from disk, augment, write back. Skips
     * silently if the file does not exist (brief without CLAUDE.md is
     * already handled by BriefImporter as a soft case).
     */
    public function augmentFile(string $absolutePath): bool
    {
        if (! is_file($absolutePath) || ! is_writable($absolutePath)) {
            return false;
        }

        $original = (string) file_get_contents($absolutePath);
        $augmented = $this->augment($original);

        if ($augmented === $original) {
            return false;
        }

        return file_put_contents($absolutePath, $augmented) !== false;
    }
}
