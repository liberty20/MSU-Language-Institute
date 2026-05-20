<template>
    <div class="min-h-screen bg-gray-50 flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-[#0a1f44] text-white hidden md:flex md:flex-col shadow-xl">
            <div class="p-6 flex items-center justify-center border-b border-[#1c355d]">
                <h1 class="text-2xl font-bold tracking-wider text-[#f5c242]">MSUNLI</h1>
            </div>
            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                <Link :href="route('dashboard')" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors" :class="{ 'bg-[#1c355d] text-[#f5c242]': route().current('dashboard'), 'hover:bg-[#152a4d] text-gray-300': !route().current('dashboard') }">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Dashboard
                </Link>
                
                <Link :href="route('service-requests.index')" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors" :class="{ 'bg-[#1c355d] text-[#f5c242]': route().current('service-requests.*'), 'hover:bg-[#152a4d] text-gray-300': !route().current('service-requests.*') }">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    Service Requests
                </Link>

                <Link :href="route('quotations.index')" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors" :class="{ 'bg-[#1c355d] text-[#f5c242]': route().current('quotations.*'), 'hover:bg-[#152a4d] text-gray-300': !route().current('quotations.*') }">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Quotations
                </Link>
                <Link :href="route('clients.index')" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors" :class="{ 'bg-[#1c355d] text-[#f5c242]': route().current('clients.*'), 'hover:bg-[#152a4d] text-gray-300': !route().current('clients.*') }">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Clients
                </Link>

                <Link :href="route('assignments.index')" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors" :class="{ 'bg-[#1c355d] text-[#f5c242]': route().current('assignments.*'), 'hover:bg-[#152a4d] text-gray-300': !route().current('assignments.*') }">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Assignments
                </Link>

                <Link :href="route('tasks.index')" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors" :class="{ 'bg-[#1c355d] text-[#f5c242]': route().current('tasks.*'), 'hover:bg-[#152a4d] text-gray-300': !route().current('tasks.*') }">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    Tasks
                </Link>

                <Link :href="route('procurement-requests.index')" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors" :class="{ 'bg-[#1c355d] text-[#f5c242]': route().current('procurement-requests.*'), 'hover:bg-[#152a4d] text-gray-300': !route().current('procurement-requests.*') }">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Procurement
                </Link>

                <Link :href="route('reports.index')" class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors" :class="{ 'bg-[#1c355d] text-[#f5c242]': route().current('reports.*'), 'hover:bg-[#152a4d] text-gray-300': !route().current('reports.*') }">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Reports
                </Link>
            </nav>
            <div class="p-4 border-t border-[#1c355d]">
                <div class="flex items-center gap-3 px-4 py-2">
                    <div class="w-8 h-8 rounded-full bg-[#f5c242] text-[#0a1f44] flex items-center justify-center font-bold">
                        {{ $page.props.auth.user.name.charAt(0) }}
                    </div>
                    <div>
                        <p class="text-sm font-medium">{{ $page.props.auth.user.name }}</p>
                        <p class="text-xs text-gray-400 truncate w-32">{{ $page.props.auth.user.email }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 z-10">
                <div class="px-6 py-4 flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-800 tracking-tight">
                        <slot name="header" />
                    </h2>
                    
                    <div class="flex items-center gap-4">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button class="flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-gray-700 transition duration-150 ease-in-out">
                                    <span>{{ $page.props.auth.user.name }}</span>
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </button>
                            </template>

                            <template #content>
                                <DropdownLink :href="route('logout')" method="post" as="button">
                                    Log Out
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link } from '@inertiajs/inertia-vue3';

const showingNavigationDropdown = ref(false);
</script>
