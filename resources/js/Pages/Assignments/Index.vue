<template>
    <Head title="Assignments" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <span>Assignments Board</span>
                <Link v-if="['executive_director', 'deputy_director', 'ict_administrator', 'admin_assistant', 'secretary'].some(r => $page.props.auth.roles.includes(r))" :href="route('assignments.create')" class="bg-[#0a1f44] text-white hover:bg-[#152a4d] px-4 py-2 rounded-lg font-semibold text-sm transition-colors shadow-sm inline-block">
                    + New Assignment
                </Link>
            </div>
        </template>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <!-- Filter Bar -->
            <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input v-model="form.client" @input="debouncedSearch" type="text" placeholder="Filter by client name..." 
                           class="w-full border-gray-300 rounded-xl shadow-sm text-sm focus:border-[#0a1f44] focus:ring-[#0a1f44]" />
                </div>
                
                <div class="w-48">
                    <select v-model="form.status" @change="submitFilters"
                            class="w-full border-gray-300 rounded-xl shadow-sm text-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] capitalize">
                        <option value="">All Statuses</option>
                        <option value="assigned">Assigned</option>
                        <option value="accepted">Accepted</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>

                <button v-if="form.client || form.status" @click="resetFilters"
                        class="px-4 py-2 text-sm text-[#0a1f44] hover:text-[#0a1f44]/80 font-bold flex items-center gap-1.5 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Clear Filters
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                            <th class="px-6 py-4 font-semibold">Service Request</th>
                            <th class="px-6 py-4 font-semibold">Assigned To</th>
                            <th class="px-6 py-4 font-semibold">Role</th>
                            <th class="px-6 py-4 font-semibold">Assigned By</th>
                            <th class="px-6 py-4 font-semibold">Status</th>
                            <th class="px-6 py-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        <tr v-if="assignments.data.length === 0">
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">No active assignments found.</td>
                        </tr>
                        <tr v-for="assignment in assignments.data" :key="assignment.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ assignment.service_request?.reference_number }}</td>
                            <td class="px-6 py-4 text-gray-600">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-800 flex items-center justify-center text-xs font-bold">
                                        {{ assignment.assigned_to?.name?.charAt(0) || 'U' }}
                                    </div>
                                    {{ assignment.assigned_to?.name }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600 capitalize">{{ (assignment.role_in_task || 'Staff').replace('_', ' ') }}</td>
                            <td class="px-6 py-4 text-gray-900">
                                {{ assignment.assigned_by?.name || 'Director' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium capitalize"
                                    :class="{
                                        'bg-yellow-100 text-yellow-800': assignment.status === 'assigned' || assignment.status === 'pending',
                                        'bg-blue-100 text-blue-800': assignment.status === 'accepted' || assignment.status === 'in_progress',
                                        'bg-green-100 text-green-800': assignment.status === 'completed',
                                        'bg-red-100 text-red-800': assignment.status === 'rejected'
                                    }">
                                    {{ assignment.status.replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="route('assignments.show', assignment.id)" class="text-blue-600 hover:text-blue-900 font-medium text-sm">Manage</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between" v-if="assignments.total > 0">
                <span class="text-sm text-gray-500">
                    Showing {{ assignments.from }} to {{ assignments.to }} of {{ assignments.total }} results
                </span>
                <div class="flex gap-1" v-if="assignments.links">
                    <Link v-for="(link, i) in assignments.links" :key="i" :href="link.url || '#'" 
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
import { reactive } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import { debounce } from 'lodash';

const props = defineProps({
    assignments: Object,
    filters: Object
});

const form = reactive({
    client: props.filters?.client || '',
    status: props.filters?.status || '',
});

const submitFilters = () => {
    Inertia.get(route('assignments.index'), {
        client: form.client,
        status: form.status,
    }, {
        preserveState: true,
        replace: true
    });
};

const debouncedSearch = debounce(submitFilters, 300);

const resetFilters = () => {
    form.client = '';
    form.status = '';
    submitFilters();
};
</script>
