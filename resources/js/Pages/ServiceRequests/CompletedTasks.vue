<template>
    <Head title="Completed Tasks Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <span>{{ $page.props.auth.roles.includes('client') ? 'My Completed Tasks' : 'Completed Tasks Registry' }}</span>
                <span class="text-xs bg-[#d4af37]/25 text-[#d4af37] border border-[#d4af37]/35 px-3 py-1 rounded-full font-semibold uppercase tracking-wider">
                    {{ $page.props.auth.roles.includes('client') ? 'Client View' : 'Administrative View' }}
                </span>
            </div>
        </template>

        <div class="space-y-6 max-w-7xl mx-auto pb-12">
            <!-- Filter Bar -->
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-150 flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[240px] relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input v-model="form.search" @input="debouncedSearch" type="text" placeholder="Search tasks by title, reference, or client..." 
                           class="w-full pl-9 pr-4 py-2 border-gray-250 rounded-xl shadow-sm text-xs focus:border-[#0a1f44] focus:ring-[#0a1f44]" />
                </div>
                
                <div class="w-48">
                    <select v-model="form.category" @change="submitFilters"
                            class="w-full border-gray-250 rounded-xl shadow-sm text-xs focus:border-[#0a1f44] focus:ring-[#0a1f44] capitalize">
                        <option value="">All Services</option>
                        <option value="translation">Translation</option>
                        <option value="editing">Editing</option>
                        <option value="brailling">Brailling</option>
                        <option value="sign_language">Sign Language</option>
                        <option value="consultancy">Consultancy</option>
                        <option value="short_courses">Short Courses</option>
                    </select>
                </div>

                <button v-if="form.search || form.category" @click="resetFilters"
                        class="px-4 py-2 text-xs text-[#0a1f44] hover:bg-gray-100 rounded-xl font-bold flex items-center gap-1.5 transition border border-gray-200">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Clear Filters
                </button>
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
                                    No completed tasks found.
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
                                        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 w-fit bg-green-100 text-green-800 border border-green-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                            Delivered
                                        </span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-4 text-right">
                                        <Link :href="request.action_url" 
                                              class="inline-flex items-center gap-1 bg-[#0a1f44] hover:bg-[#152a4d] text-white px-4 py-1.5 rounded-lg text-xs font-bold transition shadow-sm">
                                            <span>{{ $page.props.auth.roles.includes('client') ? 'View Details' : 'Manage & Deliver' }}</span>
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
import { reactive } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import { debounce } from 'lodash';

const props = defineProps({
    serviceRequests: Object,
    filters: Object
});

const form = reactive({
    search: props.filters?.search || '',
    category: props.filters?.category || '',
});

const submitFilters = () => {
    Inertia.get(route('completed-tasks.index'), {
        search: form.search,
        category: form.category
    }, {
        preserveState: true,
        replace: true
    });
};

const debouncedSearch = debounce(submitFilters, 300);

const resetFilters = () => {
    form.search = '';
    form.category = '';
    submitFilters();
};

const countStaffFiles = (request) => {
    if (!request.assignments) return 0;
    return request.assignments.reduce((acc, curr) => acc + (curr.documents?.length || 0), 0);
};

const getApprovedQuotationRef = (request) => {
    const q = request.quotations?.find(quot => quot.status === 'approved');
    return q ? `Quot-${q.id}` : 'N/A';
};

const getDeliverableFilename = (request) => {
    if (!request.assignments) return 'N/A';
    for (const assignment of request.assignments) {
        if (assignment.documents && assignment.documents.length > 0) {
            return assignment.documents[0].filename;
        }
    }
    return 'N/A';
};

const getPaymentStatusLabel = (request) => {
    if (!request.payments) return 'Unpaid';
    const hasVerified = request.payments.some(p => p.status === 'verified');
    if (hasVerified) return 'Verified';
    const hasPending = request.payments.some(p => p.status === 'pending');
    if (hasPending) return 'Pending Verification';
    return 'Unpaid';
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
