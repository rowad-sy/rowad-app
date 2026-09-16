<x-layouts.app>
    <div class="p-6">
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Dashboard</h1>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">Welcome back, {{ auth()->user()->name }}.</p>

        <a href="{{ route('admin.home') }}" wire:navigate class="mt-6 inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
            Go to the platform
        </a>
    </div>
</x-layouts.app>