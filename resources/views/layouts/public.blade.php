<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">

    <title>@yield('title', 'WebFactory')</title>
    <meta name="description" content="@yield('description', 'AI-powered web platform factory.')">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,600,700&display=swap" rel="stylesheet">

    {{-- Sprint 11 — design-system tokens injected as CSS variables --}}
    <style>
        :root {
            --color-primary: {{ \App\Settings\AppearanceSettings::class ? app(\App\Settings\AppearanceSettings::class)->colorPrimary : '#4F46E5' }};
            --font-heading: {{ app(\App\Settings\AppearanceSettings::class)->fontHeading ?? 'Inter, sans-serif' }};
            --radius-md: {{ app(\App\Settings\AppearanceSettings::class)->radiusMd ?? '0.5rem' }};
        }
        body { font-family: var(--font-heading); margin: 0; padding: 0; color: #0f172a; background: #ffffff; }
        .container { max-width: 72rem; margin-inline: auto; padding: 1.5rem; }
        a { color: var(--color-primary); }
        button.cta {
            background: var(--color-primary); color: #ffffff;
            padding: 0.75rem 1.5rem; border: none; border-radius: var(--radius-md);
            font-weight: 600; cursor: pointer;
        }
    </style>

    @yield('head')
</head>
<body class="h-full">
    <header class="container">
        <nav style="display: flex; justify-content: space-between; align-items: center;">
            <a href="/" class="brand" style="font-weight: 700; font-size: 1.25rem; text-decoration: none; color: #0f172a;">WebFactory</a>
            <button class="cta" data-open-automation-modal>@lang('public.cta_request_automation', [], 'Request automation')</button>
        </nav>
    </header>

    <main class="container" id="main">
        @yield('content')
    </main>

    <footer class="container" style="border-top: 1px solid #e2e8f0; margin-top: 3rem; padding-top: 1.5rem; color: #64748b; font-size: 0.875rem;">
        <p>&copy; {{ date('Y') }} WebFactory. All rights reserved.</p>
    </footer>

    {{-- Automation modal stub — full Vue/Alpine impl lives in the per-site frontend (Sprint 6 brief) --}}
    <dialog id="automation-modal" style="border: none; border-radius: var(--radius-md); padding: 2rem; max-width: 32rem; width: 100%;">
        <form method="POST" action="/api/v1/automation-requests" id="automation-form">
            @csrf
            <h2>@lang('public.modal_title', [], 'Request automation')</h2>
            <p>@lang('public.modal_intro', [], 'Tell us your need — we reply within 24 business hours.')</p>
            {{-- Real fields wired up by the per-site Vue/Alpine layer --}}
            <button type="button" onclick="document.getElementById('automation-modal').close()">Close</button>
        </form>
    </dialog>

    <script>
        document.querySelectorAll('[data-open-automation-modal]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var modal = document.getElementById('automation-modal');
                if (modal && typeof modal.showModal === 'function') {
                    modal.showModal();
                }
            });
        });
    </script>
</body>
</html>
