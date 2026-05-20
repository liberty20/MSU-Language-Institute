<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            Dashboard
        </template>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex items-center justify-between hover:shadow-md transition-shadow">
                <div>
                    <p class="text-sm font-medium text-gray-500 mb-1">Total Clients</p>
                    <p class="text-3xl font-bold text-[#0a1f44]">{{ stats.total_clients }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex items-center justify-between hover:shadow-md transition-shadow">
                <div>
                    <p class="text-sm font-medium text-gray-500 mb-1">Active Requests</p>
                    <p class="text-3xl font-bold text-[#f5c242]">{{ stats.active_requests }}</p>
                </div>
                <div class="w-12 h-12 bg-yellow-50 rounded-full flex items-center justify-center text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex items-center justify-between hover:shadow-md transition-shadow">
                <div>
                    <p class="text-sm font-medium text-gray-500 mb-1">Pending Tasks</p>
                    <p class="text-3xl font-bold text-red-500">{{ stats.pending_tasks }}</p>
                </div>
                <div class="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center text-red-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex items-center justify-between hover:shadow-md transition-shadow">
                <div>
                    <p class="text-sm font-medium text-gray-500 mb-1">Total Revenue</p>
                    <p class="text-3xl font-bold text-green-600">${{ parseFloat(stats.total_revenue || 0).toLocaleString() }}</p>
                </div>
                <div class="w-12 h-12 bg-green-50 rounded-full flex items-center justify-center text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="text-lg font-semibold text-[#0a1f44]">Recent Service Requests</h3>
                <Link :href="route('service-requests.index')" class="text-sm font-medium text-blue-600 hover:text-blue-800">View All</Link>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white text-gray-500 text-xs uppercase tracking-wider border-b border-gray-100">
                            <th class="px-6 py-4 font-semibold">Reference</th>
                            <th class="px-6 py-4 font-semibold">Client</th>
                            <th class="px-6 py-4 font-semibold">Service</th>
                            <th class="px-6 py-4 font-semibold">Status</th>
                            <th class="px-6 py-4 font-semibold text-right">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        <tr v-for="request in recentRequests" :key="request.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ request.reference }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ request.client }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ request.service }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium"
                                    :class="{
                                        'bg-yellow-100 text-yellow-800': request.status === 'pending',
                                        'bg-blue-100 text-blue-800': request.status === 'in_progress',
                                        'bg-green-100 text-green-800': request.status === 'completed',
                                        'bg-gray-100 text-gray-800': ['draft', 'cancelled'].includes(request.status)
                                    }">
                                    {{ request.status.replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-gray-500">{{ request.date }}</td>
                        </tr>
                        <tr v-if="!recentRequests || recentRequests.length === 0">
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">No recent requests found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/inertia-vue3';

defineProps({
    stats: Object,
    recentRequests: Array
});
</script>
