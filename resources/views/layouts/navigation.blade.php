<nav x-data="{ open: false }" class="bg-[--color-card] border-b border-gray-100 dark:border-gray-700">
    @php
        $unreadNotifications = Auth::user()->unreadNotifications()->latest()->take(5)->get();
        $unreadNotificationCount = Auth::user()->unreadNotifications()->count();
    @endphp

    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-6 sm:h-7 md:h-8 lg:h-9 w-auto" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-6 lg:space-x-8 sm:-my-px ms-6 sm:ms-10 sm:flex">

                    @if (auth()->user()->isAdmin())
                        <div class="relative flex items-center group">
                            <button
                                class="inline-flex items-center px-1 pt-1 border-b-2 h-full text-xs md:text-sm font-medium leading-5 transition duration-150 ease-in-out
                                {{ request()->routeIs('admin.*')
                                    ? 'border-[--color-primary] text-[--color-text]'
                                    : 'border-transparent text-[--color-subtletext] hover:text-[--color-text] hover:border-[--color-border]' }}">
                                {{ __('Admin Panel') }}

                                <svg class="ms-1 h-4 w-4 fill-current" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div
                                class="absolute left-0 top-full z-50 hidden w-48 rounded-lg bg-[--color-card] shadow-lg ring-1 ring-black ring-opacity-5 group-hover:block">
                                <div class="py-1">
                                    <a href="{{ route('admin.leave-requests') }}"
                                        class="block px-4 py-2 text-xs md:text-sm text-[--color-text] hover:bg-[--color-surface-alt]">
                                        {{ __('View Leave Requests') }}
                                    </a>

                                    <a href="{{ route('admin.users') }}"
                                        class="block px-4 py-2 text-xs md:text-sm text-[--color-text] hover:bg-[--color-surface-alt]">
                                        {{ __('User Management') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif

                    <x-nav-link :href="route('dashboard')" class="text-xs md:text-sm" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    <div class="relative flex items-center group">
                        <button
                            class="inline-flex items-center px-1 pt-1 border-b-2 h-full text-xs md:text-sm font-medium leading-5 transition duration-150 ease-in-out
                            {{ request()->routeIs('leave.*')
                                ? 'border-[--color-primary] text-[--color-text]'
                                : 'border-transparent text-[--color-subtletext] hover:text-[--color-text] hover:border-[--color-border]' }}">
                            {{ __('Annual Leave') }}

                            <svg class="ms-1 h-4 w-4 fill-current" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div
                            class="absolute left-0 top-full z-50 hidden w-48 rounded-lg bg-[--color-card] shadow-lg ring-1 ring-black ring-opacity-5 group-hover:block">
                            <div class="py-1">
                                <a href="{{ route('leave.view') }}"
                                    class="block px-4 py-2 text-xs md:text-sm text-[--color-text] hover:bg-[--color-surface-alt]">
                                    {{ __('View Your Leave') }}
                                </a>

                                <a href="{{ route('leave.form') }}"
                                    class="block px-4 py-2 text-xs md:text-sm text-[--color-text] hover:bg-[--color-surface-alt]">
                                    {{ __('Request Leave') }}
                                </a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Notification and Settings Dropdowns -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 sm:gap-2">
                <x-dropdown align="right" width="w-80">
                    <x-slot name="trigger">
                        <button type="button"
                            class="relative inline-flex h-10 w-10 items-center justify-center rounded-lg text-[--color-subtletext] transition hover:bg-[--color-surface-alt] hover:text-[--color-text] focus:outline-none focus:ring-2 focus:ring-[--color-primary] focus:ring-offset-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                aria-hidden="true">
                                <path d="M10.268 21a2 2 0 0 0 3.464 0" />
                                <path
                                    d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326" />
                            </svg>
                            <span class="sr-only">{{ __('Notifications') }}</span>

                            @if ($unreadNotificationCount > 0)
                                <span
                                    class="absolute right-1 top-1 inline-flex min-h-4 min-w-4 items-center justify-center rounded-full bg-[--color-danger] px-1 text-[10px] font-semibold leading-none text-[--color-background]">
                                    {{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}
                                </span>
                            @endif
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="border-b border-[--color-border] px-4 py-3">
                            <p class="text-sm font-semibold text-[--color-text]">{{ __('Notifications') }}</p>
                            <p class="mt-1 text-xs text-[--color-subtletext]">
                                {{ $unreadNotificationCount }}
                                {{ \Illuminate\Support\Str::plural('unread item', $unreadNotificationCount) }}
                            </p>
                        </div>

                        @forelse ($unreadNotifications as $notification)
                            <x-notification-item :notification="$notification" />
                        @empty
                            <p class="px-4 py-6 text-sm text-[--color-subtletext]">
                                {{ __('No new notifications.') }}
                            </p>
                        @endforelse
                    </x-slot>
                </x-dropdown>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center px-3 py-2 border border-transparent text-xs md:text-sm leading-4 font-medium rounded-lg text-[--color-subtletext] hover:text-[--color-text] hover:bg-[--color-surface-alt] focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 rounded-lg text-[--color-subtletext] hover:text-[--color-text] hover:bg-[--color-surface-alt] focus:outline-none focus:bg-[--color-surface-alt]  focus:text-[--color-text]  transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{ 'block': open, 'hidden': !open }" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @if (auth()->user()->isAdmin())
                <x-responsive-nav-link :href="route('admin.leave-requests')" :active="request()->routeIs('admin.leave-requests')">
                    {{ __('View Leave Requests') }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('admin.users')" :active="request()->routeIs('admin.users')">
                    {{ __('User Management') }}
                </x-responsive-nav-link>
            @endif

            <x-responsive-nav-link :href="route('leave.view')" :active="request()->routeIs('leave.view')">
                {{ __('View Leave') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('leave.form')" :active="request()->routeIs('leave.form')">
                {{ __('Request Leave') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-[--color-border]">
            <div class="px-4">
                <div class="font-medium text-base text-[--color-text]">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-[--color-subtletext]">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 border-t border-[--color-border] px-4 py-3">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-[--color-text]">{{ __('Notifications') }}</p>
                    @if ($unreadNotificationCount > 0)
                        <span
                            class="inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-[--color-danger] px-1.5 text-xs font-semibold text-[--color-background]">
                            {{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}
                        </span>
                    @endif
                </div>

                <div class="mt-2 overflow-hidden rounded-lg border border-[--color-border]">
                    @forelse ($unreadNotifications as $notification)
                        <x-notification-item :notification="$notification" />
                    @empty
                        <p class="px-4 py-4 text-sm text-[--color-subtletext]">
                            {{ __('No new notifications.') }}
                        </p>
                    @endforelse
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
