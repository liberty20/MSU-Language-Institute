<template>
    <Head title="Procurement Requests" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <span>Procurement Dashboard</span>
                <button class="bg-[#f5c242] text-[#0a1f44] hover:bg-yellow-500 px-4 py-2 rounded-lg font-semibold text-sm transition-colors shadow-sm">
                    + New Procurement Request
                </button>
            </div>
        </template>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex gap-4">
                <input type="text" placeholder="Search procurements..." class="border-gray-300 rounded-md shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500 w-64" />
                <select class="border-gray-300 rounded-md shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Statuses</option>
                    <option value="draft">Draft</option>
                    <option value="submitted">Submitted</option>
                    <option value="pending_approval">Pending Approval</option>
                    <option value="approved">Approved</option>
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                            <th class="px-6 py-4 font-semibold">Ref Number</th>
                            <th class="px-6 py-4 font-semibold">Title</th>
                            <th class="px-6 py-4 font-semibold">Requested By</th>
                            <th class="px-6 py-4 font-semibold">Est. Cost</th>
                            <th class="px-6 py-4 font-semibold">Priority</th>
                            <th class="px-6 py-4 font-semibold">Status</th>
                            <th class="px-6 py-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        <tr v-if="!procurements || procurements.data.length === 0">
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">No procurement requests found.</td>
                        </tr>
                        <tr v-for="request in (procurements?.data || [])" :key="request.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ request.reference_number }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ request.title }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ request.requested_by_user?.name }}</td>
                            <td class="px-6 py-4 font-semibold text-[#0a1f44]">${{ request.estimated_cost || '0.00' }}</td>
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
                                        'bg-yellow-100 text-yellow-800': request.status === 'pending_approval' || request.status === 'submitted',
                                        'bg-green-100 text-green-800': request.status === 'approved' || request.status === 'fulfilled',
                                        'bg-red-100 text-red-800': request.status === 'rejected',
                                        'bg-gray-100 text-gray-800': request.status === 'draft'
                                    }">
                                    {{ request.status.replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="route('procurement-requests.show', request.id)" class="text-blue-600 hover:text-blue-900 font-medium text-sm">Review</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between" v-if="procurements?.total > 0">
                <span class="text-sm text-gray-500">
                    Showing {{ procurements.from }} to {{ procurements.to }} of {{ procurements.total }} results
                </span>
                <div class="flex gap-1" v-if="procurements.links">
                    <Link v-for="(link, i) in procurements.links" :key="i" :href="link.url || '#'" 
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
    procurements: Object
});
</script>
