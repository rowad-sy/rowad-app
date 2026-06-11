<x-layouts.app>
    @php
        $centersCount = \App\Models\Center::count();
        $projectsCount = \App\Models\Project::count();
        $usersCount = \App\Models\User::count();
        $groupsCount = \App\Models\Group::count();
    @endphp
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div>
            <flux:heading>{{ __('messages.dashboard') }}</flux:heading>
            <flux:subheading>{{ __('messages.rowad_foundation') }}</flux:subheading>
        </div>

        <flux:separator />

        <div class="grid auto-rows-min gap-4 md:grid-cols-4">
            <a href="{{ route('admin.centers') }}" wire:navigate class="relative overflow-hidden rounded-xl border border-neutral-200 bg-white p-6 transition hover:shadow-md dark:border-neutral-700 dark:bg-zinc-900">
                <div class="flex items-center gap-4">
                    <div class="rounded-lg bg-blue-100 p-3 dark:bg-blue-900">
                        <flux:icon.building-storefront class="size-6 text-blue-600 dark:text-blue-300" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ $centersCount }}</p>
                        <p class="text-sm text-neutral-500">{{ __('messages.centers') }}</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('admin.projects') }}" wire:navigate class="relative overflow-hidden rounded-xl border border-neutral-200 bg-white p-6 transition hover:shadow-md dark:border-neutral-700 dark:bg-zinc-900">
                <div class="flex items-center gap-4">
                    <div class="rounded-lg bg-green-100 p-3 dark:bg-green-900">
                        <flux:icon.briefcase class="size-6 text-green-600 dark:text-green-300" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ $projectsCount }}</p>
                        <p class="text-sm text-neutral-500">{{ __('messages.projects') }}</p>
                    </div>
                </div>
            </a>

            <a href="#" class="relative overflow-hidden rounded-xl border border-neutral-200 bg-white p-6 transition hover:shadow-md dark:border-neutral-700 dark:bg-zinc-900">
                <div class="flex items-center gap-4">
                    <div class="rounded-lg bg-amber-100 p-3 dark:bg-amber-900">
                        <flux:icon.users class="size-6 text-amber-600 dark:text-amber-300" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ $usersCount }}</p>
                        <p class="text-sm text-neutral-500">{{ __('messages.users') }}</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('admin.groups') }}" wire:navigate class="relative overflow-hidden rounded-xl border border-neutral-200 bg-white p-6 transition hover:shadow-md dark:border-neutral-700 dark:bg-zinc-900">
                <div class="flex items-center gap-4">
                    <div class="rounded-lg bg-purple-100 p-3 dark:bg-purple-900">
                        <flux:icon.shield-check class="size-6 text-purple-600 dark:text-purple-300" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ $groupsCount }}</p>
                        <p class="text-sm text-neutral-500">{{ __('messages.groups') }}</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</x-layouts.app>
