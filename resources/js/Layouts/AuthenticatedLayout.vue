<template>
    <div class="h-screen bg-gray-50 flex overflow-hidden relative">
        <!-- Sidebar Backdrop for Mobile -->
        <div v-if="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-20 md:hidden transition-opacity duration-300"></div>

        <!-- Sidebar -->
        <aside :class="[
            'w-64 bg-brand-blue text-white shadow-xl flex-shrink-0 h-full flex flex-col transition-all duration-300 z-30',
            'fixed md:static inset-y-0 left-0 transform md:transform-none md:flex md:flex-col',
            sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'
        ]">
            <div class="p-6 flex items-center justify-start gap-3 border-b border-brand-blue-light">
                <img src="/msu-logo-2.png" alt="MSU Logo" class="h-10 w-auto object-contain" />
                <h1 class="text-3xl font-extrabold tracking-widest text-white">MSUNLI</h1>
            </div>
            
            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                <Link :href="route('dashboard')" :class="navClass('dashboard')">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Dashboard
                </Link>

                <Link v-if="can('view service requests')" :href="route('service-requests.index')" :class="navClass('service-requests.*')">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    Service Requests
                </Link>

                <Link v-if="can('manage quotations')" :href="route('quotations.index')" :class="navClass('quotations.*')">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Quotations
                </Link>

                <Link v-if="can('manage clients')" :href="route('clients.index')" :class="navClass('clients.*')">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Clients
                </Link>

                <Link v-if="can('view assignments')" :href="route('assignments.index')" :class="navClass('assignments.*')">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    Assignments
                </Link>

                <Link v-if="can('view tasks')" :href="route('tasks.index')" :class="navClass('tasks.*')">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    Tasks
                </Link>

                <Link v-if="can('manage procurement') || can('approve procurement')" :href="route('procurement-requests.index')" :class="navClass('procurement-requests.*')">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Procurement
                </Link>

                <Link v-if="can('view reports')" :href="route('reports.index')" :class="navClass('reports.*')">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Reports
                </Link>

                <Link v-if="can('manage users')" :href="route('admin.users.index')" :class="navClass('admin.users.*')">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Admin Users
                </Link>
            </nav>

            <!-- User Profile Bottom -->
            <div class="p-4 border-t border-brand-blue-light">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-brand-gold text-brand-blue flex items-center justify-center font-bold text-lg shadow">
                        {{ $page.props.auth.user.name.charAt(0) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold truncate">{{ $page.props.auth.user.name }}</p>
                        <p class="text-xs text-gray-300 truncate uppercase">{{ $page.props.auth.roles[0] ? $page.props.auth.roles[0].replace('_', ' ') : 'User' }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-grow flex flex-col h-full overflow-hidden">
            <!-- Header -->
            <header class="bg-brand-blue md:bg-white shadow-sm border-b border-brand-blue-light md:border-gray-200 z-10 sticky top-0 flex-shrink-0">
                <div class="px-4 md:px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <!-- Mobile Hamburger Menu Button -->
                        <button @click="sidebarOpen = !sidebarOpen" class="md:hidden text-white hover:text-brand-gold focus:outline-none p-1 rounded transition duration-150 ease-in-out" aria-label="Toggle Sidebar">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-if="!sidebarOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        
                        <!-- Mobile Logo & Brand -->
                        <div class="flex items-center gap-2 md:hidden">
                            <img src="/msu-logo-2.png" alt="MSU Logo" class="h-8 w-auto object-contain bg-white p-0.5 rounded shadow-sm" />
                            <span class="text-xl font-bold tracking-wider text-white">MSUNLI</span>
                        </div>
                        
                        <!-- Desktop Page Title -->
                        <h2 class="hidden md:block text-2xl font-semibold text-brand-blue tracking-tight">
                            <slot name="header" />
                        </h2>
                    </div>
                    
                    <!-- Mobile Page Title -->
                    <div class="md:hidden text-white font-semibold text-base px-2">
                        <slot name="header" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button class="flex items-center gap-2 text-sm font-medium text-white md:text-gray-600 hover:text-brand-gold md:hover:text-brand-blue transition duration-150 ease-in-out focus:outline-none">
                                    <span>{{ $page.props.auth.user.name }}</span>
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </button>
                            </template>
                            <template #content>
                                <DropdownLink :href="route('logout')" method="post" as="button" class="text-red-600 font-medium">
                                    Log Out
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </header>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash.success" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mx-6 mt-6 rounded shadow-sm flex items-center" role="alert">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                {{ $page.props.flash.success }}
            </div>
            
            <div v-if="$page.props.flash.error" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mx-6 mt-6 rounded shadow-sm flex items-center" role="alert">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                {{ $page.props.flash.error }}
            </div>

            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-6">
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { usePage } from '@inertiajs/inertia-vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link } from '@inertiajs/inertia-vue3';

const page = usePage();
const sidebarOpen = ref(false);

const can = (permission) => {
    return page.props.value.auth.permissions.includes(permission) || page.props.value.auth.permissions.includes('manage system');
};

const navClass = (routeName) => {
    const isActive = route().current(routeName);
    return [
        'flex items-center px-4 py-3 rounded-xl transition-all duration-200 font-medium',
        isActive ? 'bg-brand-blue-light text-brand-gold shadow-md' : 'text-gray-300 hover:bg-brand-blue-mid hover:text-white hover:shadow'
    ];
};
</script>
