<template>
    <Head title="Service Requests" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <span>Service Requests</span>
                <Link :href="route('service-requests.create')" class="bg-[#f5c242] text-[#0a1f44] hover:bg-yellow-500 px-4 py-2 rounded-lg font-semibold text-sm transition-colors shadow-sm">
                    + New Request
                </Link>
            </div>
        </template>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <!-- Filter Bar -->
            <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex gap-4">
                <input type="text" placeholder="Search requests..." class="border-gray-300 rounded-md shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500 w-64" />
                <select class="border-gray-300 rounded-md shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                            <th class="px-6 py-4 font-semibold">Reference</th>
                            <th class="px-6 py-4 font-semibold">Client</th>
                            <th class="px-6 py-4 font-semibold">Service</th>
                            <th class="px-6 py-4 font-semibold">Priority</th>
                            <th class="px-6 py-4 font-semibold">Status</th>
                            <th class="px-6 py-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        <tr v-if="serviceRequests.data.length === 0">
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">No service requests found.</td>
                        </tr>
                        <tr v-for="request in serviceRequests.data" :key="request.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ request.reference_number }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ request.client?.organization || request.client?.contact_person }}</td>
                            <td class="px-6 py-4 text-gray-600 capitalize">{{ request.service_category.replace('_', ' ') }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded text-xs font-medium capitalize" 
                                    :class="{
                                        'bg-red-100 text-red-800': request.priority === 'urgent',
                                        'bg-orange-100 text-orange-800': request.priority === 'high',
                                        'bg-blue-100 text-blue-800': request.priority === 'medium',
                                        'bg-gray-100 text-gray-800': request.priority === 'low'
                                    }">
                                    {{ request.priority }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium capitalize"
                                    :class="{
                                        'bg-yellow-100 text-yellow-800': request.status === 'pending',
                                        'bg-blue-100 text-blue-800': request.status === 'in_progress',
                                        'bg-green-100 text-green-800': request.status === 'completed',
                                        'bg-gray-100 text-gray-800': ['draft', 'cancelled'].includes(request.status)
                                    }">
                                    {{ request.status.replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="route('service-requests.show', request.id)" class="text-blue-600 hover:text-blue-900 font-medium text-sm">View</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <span class="text-sm text-gray-500">
                    Showing {{ serviceRequests.from || 0 }} to {{ serviceRequests.to || 0 }} of {{ serviceRequests.total || 0 }} results
                </span>
                <div class="flex gap-1" v-if="serviceRequests.links">
                    <Link v-for="(link, i) in serviceRequests.links" :key="i" :href="link.url || '#'" 
                          class="px-3 py-1 rounded border text-sm" 
                          :class="link.active ? 'bg-[#0a1f44] text-white border-[#0a1f44]' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                          v-html="link.label">
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/inertia-vue3';

defineProps({
    serviceRequests: Object,
    filters: Object
});
</script>
