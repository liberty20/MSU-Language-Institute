<template>
    <Head title="Completed Tasks Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <span>Completed Tasks Registry</span>
                <span class="text-xs bg-[#d4af37]/25 text-[#d4af37] border border-[#d4af37]/35 px-3 py-1 rounded-full font-semibold uppercase tracking-wider">
                    Administrative View
                </span>
            </div>
        </template>

        <div class="space-y-6 max-w-7xl mx-auto pb-12">
            <!-- Filter Bar -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-150 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Filter Registry:</span>
                    <Link :href="route('completed-tasks.index')" 
                          class="px-4 py-1.5 rounded-xl text-xs font-bold transition shadow-sm border"
                          :class="!$page.props.value?.filters?.status ? 'bg-[#0a1f44] text-white border-[#0a1f44]' : 'bg-white text-gray-600 border-gray-250 hover:bg-gray-50'">
                        All Tasks
                    </Link>
                    <Link :href="route('completed-tasks.index', { status: 'review' })" 
                          class="px-4 py-1.5 rounded-xl text-xs font-bold transition shadow-sm border flex items-center gap-2"
                          :class="$page.props.value?.filters?.status === 'review' ? 'bg-[#0a1f44] text-white border-[#0a1f44]' : 'bg-white text-gray-600 border-gray-250 hover:bg-gray-50'">
                        <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                        Awaiting Delivery (Review)
                    </Link>
                    <Link :href="route('completed-tasks.index', { status: 'completed' })" 
                          class="px-4 py-1.5 rounded-xl text-xs font-bold transition shadow-sm border flex items-center gap-2"
                          :class="$page.props.value?.filters?.status === 'completed' ? 'bg-[#0a1f44] text-white border-[#0a1f44]' : 'bg-white text-gray-600 border-gray-250 hover:bg-gray-50'">
                        <span class="w-2 h-2 rounded-full bg-green-400"></span>
                        Successfully Delivered
                    </Link>
                </div>
            </div>

            <!-- Main Registry Grid -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-150 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-400 text-xs font-bold uppercase tracking-wider border-b border-gray-150">
                                <th class="px-6 py-4">Task Details</th>
                                <th class="px-6 py-4">Client</th>
                                <th class="px-6 py-4">Assigned Experts</th>
                                <th class="px-6 py-4">Documents</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <tr v-if="serviceRequests.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500 font-medium italic">
                                    No completed or review-stage tasks found matching criteria.
                                </td>
                            </tr>
                            <tr v-for="request in serviceRequests.data" :key="request.id" class="hover:bg-gray-50/50 transition duration-150">
                                <!-- Task Details -->
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono font-bold text-gray-900 text-xs bg-gray-100 px-2 py-0.5 rounded">
                                                {{ request.reference_number }}
                                            </span>
                                            <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">
                                                {{ request.service_category?.replace('_', ' ') }}
                                            </span>
                                        </div>
                                        <p class="font-bold text-gray-800 text-sm line-clamp-1">
                                            {{ request.title }}
                                        </p>
                                        <p class="text-xs text-gray-400">
                                            Last active: {{ new Date(request.updated_at).toLocaleDateString() }}
                                        </p>
                                    </div>
                                </td>

                                <!-- Client -->
                                <td class="px-6 py-4">
                                    <div class="space-y-0.5">
                                        <p class="font-semibold text-gray-900 text-sm">
                                            {{ request.client?.organization || request.client?.contact_person || 'Private Client' }}
                                        </p>
                                        <p class="text-xs text-gray-400">
                                            {{ request.client?.email || 'No email' }}
                                        </p>
                                    </div>
                                </td>

                                <!-- Assigned Experts -->
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1 max-w-xs">
                                        <span v-if="!request.assignments?.length" class="text-xs text-gray-400 italic">Unassigned</span>
                                        <span v-else v-for="assign in request.assignments" :key="assign.id" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs bg-[#0a1f44]/5 text-[#0a1f44] border border-[#0a1f44]/10 font-medium">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#d4af37]"></span>
                                            {{ assign.assigned_to?.name || 'Staff' }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Documents Info -->
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <span class="text-xs font-semibold text-gray-600 block flex items-center gap-1">
                                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                            {{ request.documents?.length || 0 }} client source(s)
                                        </span>
                                        <span class="text-xs font-semibold text-gray-600 block flex items-center gap-1">
                                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            {{ countStaffFiles(request) }} completed file(s)
                                        </span>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 w-fit"
                                        :class="{
                                            'bg-amber-100 text-amber-800 border border-amber-200': request.status === 'review',
                                            'bg-green-100 text-green-800 border border-green-200': request.status === 'completed',
                                        }">
                                        <span class="w-1.5 h-1.5 rounded-full"
                                              :class="{
                                                  'bg-amber-500 animate-pulse': request.status === 'review',
                                                  'bg-green-500': request.status === 'completed',
                                              }"></span>
                                        {{ request.status === 'review' ? 'Awaiting Delivery' : 'Delivered' }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right">
                                    <Link :href="route('service-requests.show', request.id)" 
                                          class="inline-flex items-center gap-1 bg-[#0a1f44] hover:bg-[#152a4d] text-white px-4 py-1.5 rounded-lg text-xs font-bold transition shadow-sm">
                                        <span>Manage & Deliver</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginations -->
                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between" v-if="serviceRequests.total > 0">
                    <span class="text-sm text-gray-500">
                        Showing {{ serviceRequests.from }} to {{ serviceRequests.to }} of {{ serviceRequests.total }} results
                    </span>
                    <div class="flex gap-1" v-if="serviceRequests.links">
                        <Link v-for="(link, i) in serviceRequests.links" :key="i" :href="link.url || '#'" 
                              class="px-3 py-1 rounded border text-sm" 
                              :class="link.active ? 'bg-[#0a1f44] text-white border-[#0a1f44]' : 'bg-white text-gray-600 border-gray-250 hover:bg-gray-50'"
                              v-html="link.label">
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/inertia-vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    serviceRequests: Object
});

const countStaffFiles = (request) => {
    if (!request.assignments) return 0;
    return request.assignments.reduce((acc, curr) => acc + (curr.documents?.length || 0), 0);
};
</script>

<style scoped>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;  
    overflow: hidden;
}
</style>
