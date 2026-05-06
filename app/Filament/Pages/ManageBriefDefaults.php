<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Settings\BriefDefaultsSettings;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

/**
 * Sprint 28 — editor for the 5 markdown preambles that are auto-prepended
 * to every uploaded brief's CLAUDE.md. Edits take effect on the NEXT upload.
 */
final class ManageBriefDefaults extends SettingsPage
{
    protected static string $settings = BriefDefaultsSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Brief defaults';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?string $title = 'Brief defaults — preambles injected into every uploaded brief';

    protected static ?int $navigationSort = 7;

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Master switch')
                ->description('When OFF, briefs are uploaded as-is without injection. Useful for quick debugging — turn ON for production usage.')
                ->schema([
                    Toggle::make('injectionEnabled')
                        ->label('Inject preambles into uploaded briefs')
                        ->onColor('success')
                        ->default(true),
                ]),

            Section::make('1. Design system & UX (2026)')
                ->description('Tailwind v4 + shadcn/ui + Framer Motion + Heroicons + dark mode + responsive + tokens. Edit to your taste.')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Textarea::make('design')
                        ->label('Design preamble (Markdown)')
                        ->rows(20)
                        ->autosize()
                        ->extraInputAttributes(['class' => 'font-mono text-sm'])
                        ->required(),
                ]),

            Section::make('2. Production-readiness checklist')
                ->description('Infra, CI/CD, tests, observability, perf, GDPR. Verifier checks the machine-actionable items.')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Textarea::make('production')
                        ->label('Production preamble (Markdown)')
                        ->rows(20)
                        ->autosize()
                        ->extraInputAttributes(['class' => 'font-mono text-sm'])
                        ->required(),
                ]),

            Section::make('3. Accessibility (WCAG 2.2 AA)')
                ->description('Semantic HTML, keyboard nav, ARIA, contrasts, screen readers, prefers-reduced-motion.')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Textarea::make('accessibility')
                        ->label('A11y preamble (Markdown)')
                        ->rows(20)
                        ->autosize()
                        ->extraInputAttributes(['class' => 'font-mono text-sm'])
                        ->required(),
                ]),

            Section::make('4. SEO + AEO (Generative Search 2026)')
                ->description('Core Web Vitals, JSON-LD, AEO patterns for ChatGPT/Perplexity/Google AI, IndexNow, sitemap, hreflang.')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Textarea::make('seoAeo')
                        ->label('SEO/AEO preamble (Markdown)')
                        ->rows(20)
                        ->autosize()
                        ->extraInputAttributes(['class' => 'font-mono text-sm'])
                        ->required(),
                ]),

            Section::make('5. Security (OWASP Top 10 2024)')
                ->description('Headers, auth (Argon2id + 2FA), CSRF, XSS, SQLi, secrets, webhooks, dependency scanning.')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Textarea::make('security')
                        ->label('Security preamble (Markdown)')
                        ->rows(20)
                        ->autosize()
                        ->extraInputAttributes(['class' => 'font-mono text-sm'])
                        ->required(),
                ]),

            Section::make('6. Pages & Layout obligatoires')
                ->description('Header, footer, 25+ pages publiques + B2C + légales. Le verifier checke leur présence.')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Textarea::make('pagesLayout')
                        ->label('Pages & Layout preamble (Markdown)')
                        ->rows(20)
                        ->autosize()
                        ->extraInputAttributes(['class' => 'font-mono text-sm'])
                        ->required(),
                ]),

            Section::make('7. Composants UI imposés')
                ->description('Catalogue de 30+ composants : Hero, Features, PricingTable, BlogCard, CookieBanner, CommandMenu, etc.')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Textarea::make('components')
                        ->label('Components preamble (Markdown)')
                        ->rows(20)
                        ->autosize()
                        ->extraInputAttributes(['class' => 'font-mono text-sm'])
                        ->required(),
                ]),

            Section::make('8. Content Engine & Blog')
                ->description('Articles seed, structure éditoriale, calendrier publication, AEO patterns, RSS, sitemap blog.')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Textarea::make('contentEngine')
                        ->label('Content Engine preamble (Markdown)')
                        ->rows(20)
                        ->autosize()
                        ->extraInputAttributes(['class' => 'font-mono text-sm'])
                        ->required(),
                ]),

            Section::make('9. Auth & Account flows')
                ->description('Register, Login, Magic Link, 2FA, SSO, password reset, account settings, onboarding.')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Textarea::make('authAccount')
                        ->label('Auth & Account preamble (Markdown)')
                        ->rows(20)
                        ->autosize()
                        ->extraInputAttributes(['class' => 'font-mono text-sm'])
                        ->required(),
                ]),

            Section::make('10. Emails transactionnels & Notifications')
                ->description('15+ templates email obligatoires, 9 channels notifications, newsletter, in-app, RGPD.')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Textarea::make('comms')
                        ->label('Comms preamble (Markdown)')
                        ->rows(20)
                        ->autosize()
                        ->extraInputAttributes(['class' => 'font-mono text-sm'])
                        ->required(),
                ]),
        ]);
    }
}
