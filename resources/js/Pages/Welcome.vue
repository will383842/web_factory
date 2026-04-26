<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
  laravelVersion: string;
  phpVersion: string;
}>();
</script>

<template>
  <Head title="Welcome" />
  <main class="min-h-screen flex items-center justify-center px-6 py-12">
    <section
      class="max-w-xl w-full rounded-[var(--radius-card)] bg-[var(--color-surface)] shadow-sm border border-gray-200 p-8 space-y-6"
    >
      <header>
        <p class="text-xs uppercase tracking-widest text-[var(--color-brand-600)] font-semibold">WebFactory</p>
        <h1 class="text-3xl font-semibold mt-1">Sprint 0 — scaffolding ready</h1>
      </header>
      <p class="text-gray-600 leading-relaxed">
        Laravel {{ laravelVersion }} · PHP {{ phpVersion }} · Filament v4 · Inertia + Vue 3 · Tailwind v4 · Pinia.
      </p>
      <p class="text-gray-600 leading-relaxed">
        Modular monolith DDD avec 11 bounded contexts. Onion architecture vérifiée par
        <code class="bg-gray-100 px-1.5 py-0.5 rounded text-sm font-mono">Pest ArchTest</code>.
      </p>
      <div class="flex gap-3">
        <a
          href="/admin"
          class="inline-flex items-center justify-center rounded-md bg-[var(--color-brand-600)] px-4 py-2 text-sm font-medium text-white hover:bg-[var(--color-brand-700)] transition"
        >
          Filament admin
        </a>
        <a
          href="/up"
          class="inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
        >
          Health check
        </a>
      </div>
    </section>
  </main>
</template>
