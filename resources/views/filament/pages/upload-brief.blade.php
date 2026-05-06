<x-filament-panels::page>
    <form wire:submit="submit" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end gap-2">
            <x-filament::button type="submit" size="lg" icon="heroicon-o-arrow-up-tray">
                Create project & extract brief
            </x-filament::button>
        </div>
    </form>

    <div class="mt-8 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-600 dark:bg-gray-900/40 dark:text-gray-300">
        <p class="font-semibold">Next steps after submit</p>
        <ol class="mt-2 list-decimal pl-5 space-y-1">
            <li>You will land on the project's edit page with a one-click <em>Open in VS Code</em> button.</li>
            <li>VS Code opens on <code>C:\Users\willi\Documents\Projets\&lt;slug&gt;</code> with <code>CLAUDE.md</code> at the root.</li>
            <li>In VS Code, press <kbd>Ctrl+Shift+P</kbd> → <em>Claude Code: Start</em> (or use the Claude Code panel directly).</li>
            <li>Claude Code reads <code>CLAUDE.md</code> + <code>docs/</code>, generates the project under your eyes.</li>
            <li>Once the code looks good, come back here and click <em>Push to GitHub</em> (Phase 2).</li>
        </ol>
        <p class="mt-3 text-xs text-gray-500">Prefer a terminal? <code>cd</code> into the folder and run <code>claude</code> — same result.</p>
    </div>
</x-filament-panels::page>
