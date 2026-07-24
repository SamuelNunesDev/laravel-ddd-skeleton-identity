<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3'

const page = usePage<{ appName: string }>()
</script>

<template>
  <Head title="Bootstrap ready" />

  <main class="grid min-h-screen place-items-center px-6">
    <section class="max-w-2xl rounded-2xl border border-slate-800 bg-slate-900 p-10 shadow-2xl">
      <p class="text-sm font-semibold uppercase tracking-[0.22em] text-cyan-400">
        M0 · Repository bootstrap
      </p>
      <h1 class="mt-4 text-4xl font-semibold tracking-tight">
        {{ page.props.appName }}
      </h1>
      <p class="mt-4 text-lg leading-8 text-slate-300">
        Laravel, Inertia, Vue, PostgreSQL, Redis, and the modular DDD structure are ready for the next
        authorized milestone.
      </p>
    </section>
  </main>
</template>
