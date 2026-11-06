<script setup>
import { ref } from 'vue';
import AppBrand from '@/Components/AppBrand.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);

// Hauptnavigation; weitere Punkte (Teams) kommen mit den jeweiligen Schritten dazu.
const navigation = [
    { route: 'episodes.index', label: 'nav.episodes' },
    { route: 'teams.index', label: 'nav.teams' },
    { route: 'dashboard', label: 'nav.statistics' },
];
</script>

<template>
    <div class="min-h-screen bg-app">
        <nav class="sticky top-0 z-30 border-b border-line bg-bar/95 backdrop-blur">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between gap-4">
                    <div class="flex items-center gap-8">
                        <Link :href="route('episodes.index')" class="rounded-lg">
                            <AppBrand />
                        </Link>

                        <div class="hidden gap-6 sm:flex">
                            <NavLink
                                v-for="item in navigation"
                                :key="item.route"
                                :href="route(item.route)"
                                :active="route().current(item.route)"
                            >
                                {{ $t(item.label) }}
                            </NavLink>
                        </div>
                    </div>

                    <div class="hidden sm:block">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="inline-flex min-h-11 items-center gap-1 rounded-lg px-3 text-sm font-semibold text-muted transition hover:bg-surface hover:text-fg"
                                >
                                    {{ $page.props.auth.user.name }}
                                    <svg
                                        class="h-4 w-4"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                        aria-hidden="true"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M5.3 7.3a1 1 0 0 1 1.4 0L10 10.6l3.3-3.3a1 1 0 1 1 1.4 1.4l-4 4a1 1 0 0 1-1.4 0l-4-4a1 1 0 0 1 0-1.4Z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>
                            </template>

                            <template #content>
                                <DropdownLink :href="route('profile.edit')">
                                    {{ $t('nav.profile') }}
                                </DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">
                                    {{ $t('nav.logout') }}
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>

                    <!-- Menü-Button (Smartphone) -->
                    <button
                        type="button"
                        class="-me-2 inline-flex h-11 w-11 items-center justify-center rounded-lg text-muted transition hover:bg-surface hover:text-fg sm:hidden"
                        :aria-expanded="showingNavigationDropdown"
                        :aria-label="$t('nav.menu')"
                        @click="showingNavigationDropdown = !showingNavigationDropdown"
                    >
                        <svg
                            class="h-6 w-6"
                            stroke="currentColor"
                            fill="none"
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <path
                                v-if="!showingNavigationDropdown"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                            <path
                                v-else
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Menü (Smartphone) -->
            <div v-show="showingNavigationDropdown" class="border-t border-line pb-2 sm:hidden">
                <div class="py-2">
                    <ResponsiveNavLink
                        v-for="item in navigation"
                        :key="item.route"
                        :href="route(item.route)"
                        :active="route().current(item.route)"
                    >
                        {{ $t(item.label) }}
                    </ResponsiveNavLink>
                </div>
                <div class="border-t border-line pt-2">
                    <div class="px-4 py-2">
                        <div class="font-semibold text-fg">
                            {{ $page.props.auth.user.name }}
                        </div>
                        <div class="text-sm text-muted">
                            {{ $page.props.auth.user.email }}
                        </div>
                    </div>
                    <ResponsiveNavLink
                        :href="route('profile.edit')"
                        :active="route().current('profile.edit')"
                    >
                        {{ $t('nav.profile') }}
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                        {{ $t('nav.logout') }}
                    </ResponsiveNavLink>
                </div>
            </div>
        </nav>

        <header v-if="$slots.header" class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
            <slot name="header" />
        </header>

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <slot />
        </main>

        <FlashMessage />
    </div>
</template>
