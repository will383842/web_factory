<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-filament::section>
            <x-slot name="heading">Pages</x-slot>
            <div class="text-3xl font-semibold">{{ $summary['pages']['total'] }}</div>
            <div class="text-sm text-gray-500">{{ $summary['pages']['published'] }} published</div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Articles</x-slot>
            <div class="text-3xl font-semibold">{{ $summary['articles']['total'] }}</div>
            <div class="text-sm text-gray-500">
                {{ $summary['articles']['published'] }} published · {{ $summary['articles']['pillar'] }} pillar
            </div>
            <div class="mt-2 text-xs text-gray-400">
                avg quality: {{ $summary['articles']['avg_quality'] }}/100
                · avg words: {{ $summary['articles']['avg_word_count'] }}
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">FAQs</x-slot>
            <div class="text-3xl font-semibold">{{ $summary['faqs']['total'] }}</div>
            <div class="text-sm text-gray-500">
                {{ $summary['faqs']['published'] }} published · {{ $summary['faqs']['featured'] }} featured
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">News</x-slot>
            <div class="text-3xl font-semibold">{{ $summary['news']['total'] }}</div>
            <div class="text-sm text-gray-500">{{ $summary['news']['active'] }} active</div>
        </x-filament::section>

        <x-filament::section class="md:col-span-2">
            <x-slot name="heading">Average AEO score (last 50 published articles)</x-slot>
            <div class="text-4xl font-bold {{ $average_aeo_score >= 80 ? 'text-success-600' : ($average_aeo_score >= 60 ? 'text-warning-600' : 'text-danger-600') }}">
                {{ $average_aeo_score }}/100
            </div>
            <div class="text-xs text-gray-400 mt-1">≥ 80 auto-publish · 60-79 auto-optimize · &lt; 60 regenerate</div>
        </x-filament::section>

        <x-filament::section class="md:col-span-2">
            <x-slot name="heading">Knowledge base</x-slot>
            <div class="text-3xl font-semibold">{{ $summary['kb_chunks'] }}</div>
            <div class="text-sm text-gray-500">vectorized chunks (pgvector 384-dim)</div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
