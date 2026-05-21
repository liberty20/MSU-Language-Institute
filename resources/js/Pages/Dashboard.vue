<template>
    <AuthenticatedLayout>
        <template #header>
            Dashboard
        </template>

        <div class="space-y-8">
            <!-- Stats Row -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Clients -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center gap-4 transition hover:shadow-md">
                    <div class="w-14 h-14 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Total Clients</p>
                        <h3 class="text-2xl font-bold text-brand-blue">{{ stats.total_clients }}</h3>
                    </div>
                </div>

                <!-- Active Requests -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center gap-4 transition hover:shadow-md">
                    <div class="w-14 h-14 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Active Requests</p>
                        <h3 class="text-2xl font-bold text-brand-blue">{{ stats.active_requests }}</h3>
                    </div>
                </div>

                <!-- Pending Tasks -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center gap-4 transition hover:shadow-md">
                    <div class="w-14 h-14 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Pending Tasks</p>
                        <h3 class="text-2xl font-bold text-brand-blue">{{ stats.pending_tasks }}</h3>
                    </div>
                </div>

                <!-- Approved Revenue -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center gap-4 transition hover:shadow-md">
                    <div class="w-14 h-14 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Approved Value</p>
                        <h3 class="text-2xl font-bold text-brand-blue">${{ Number(stats.total_revenue).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</h3>
                    </div>
                </div>
            </div>

            <!-- Recent Requests Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="text-lg font-bold text-brand-blue">Recent Service Requests</h3>
                    <Link :href="route('service-requests.index')" class="text-sm text-brand-gold-dark hover:text-brand-gold font-semibold transition">
                        View All &rarr;
                    </Link>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-4 font-semibold">Reference</th>
                                <th class="px-6 py-4 font-semibold">Client</th>
                                <th class="px-6 py-4 font-semibold">Service</th>
                                <th class="px-6 py-4 font-semibold">Priority</th>
                                <th class="px-6 py-4 font-semibold">Status</th>
                                <th class="px-6 py-4 font-semibold">Date</th>
                                <th class="px-6 py-4 font-semibold text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="req in recentRequests" :key="req.id" class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 font-medium text-brand-blue">{{ req.reference }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ req.client }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ req.service }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full uppercase tracking-wide"
                                        :class="{
                                            'bg-red-100 text-red-700': req.priority === 'urgent',
                                            'bg-orange-100 text-orange-700': req.priority === 'high',
                                            'bg-yellow-100 text-yellow-700': req.priority === 'medium',
                                            'bg-gray-100 text-gray-700': req.priority === 'low'
                                        }">
                                        {{ req.priority }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full uppercase tracking-wide"
                                        :class="{
                                            'bg-blue-100 text-blue-700': req.status === 'pending' || req.status === 'in_progress',
                                            'bg-green-100 text-green-700': req.status === 'completed' || req.status === 'approved',
                                            'bg-purple-100 text-purple-700': req.status === 'review' || req.status === 'quoted',
                                            'bg-red-100 text-red-700': req.status === 'cancelled'
                                        }">
                                        {{ req.status.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-500 text-sm">{{ req.date }}</td>
                                <td class="px-6 py-4 text-right">
                                    <Link :href="route('service-requests.show', req.id)" class="text-brand-gold-dark hover:text-brand-blue font-semibold text-sm transition">
                                        View
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="recentRequests.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    No recent service requests found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/inertia-vue3';

defineProps({
    stats: Object,
    recentRequests: Array
});
</script>
