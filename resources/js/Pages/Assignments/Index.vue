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

        <div class="space-y-6">
            <!-- If staff outside AOS, show user-specific operational assignments dashboard -->
            <template v-if="is_staff_outside_aos">
                <div v-if="assignments.data.length === 0" class="bg-white rounded-2xl p-8 text-center text-gray-500 border border-gray-150 shadow-sm font-semibold">
                    No assignments have been assigned to you yet.
                </div>
                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-for="assignment in assignments.data" :key="assignment.id" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-150 space-y-6 hover:shadow-md transition duration-200 flex flex-col justify-between">
                        <div class="space-y-4">
                            <!-- Card Header -->
                            <div class="flex justify-between items-start gap-4">
                                <div>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-blue-50 text-brand-blue border border-blue-150 inline-block mb-1.5">
                                        {{ assignment.service_request?.service_category?.replace('_', ' ') }}
                                    </span>
                                    <h3 class="text-base font-extrabold text-gray-800 leading-snug line-clamp-2" :title="assignment.service_request?.title">
                                        {{ assignment.service_request?.title || 'Language Service Task' }}
                                    </h3>
                                    <p class="text-xs text-gray-400 font-mono mt-1">Ref: {{ assignment.service_request?.reference_number }}</p>
                                    <p v-if="assignment.service_request?.description" class="text-xs text-gray-500 line-clamp-3 mt-2">
                                        {{ assignment.service_request.description }}
                                    </p>
                                    <p v-else-if="assignment.notes" class="text-xs text-gray-500 line-clamp-3 mt-2">
                                        {{ assignment.notes }}
                                    </p>
                                </div>
                                <!-- Priority Badge -->
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide flex-shrink-0"
                                    :class="{
                                        'bg-red-50 text-red-700 border border-red-150': assignment.service_request?.priority === 'urgent' || assignment.service_request?.priority === 'high',
                                        'bg-blue-50 text-blue-700 border border-blue-150': assignment.service_request?.priority === 'medium',
                                        'bg-gray-50 text-gray-650 border border-gray-200': assignment.service_request?.priority === 'low'
                                    }">
                                    {{ assignment.service_request?.priority }}
                                </span>
                            </div>

                            <!-- Technical Meta fields (Assigned On / Due Date / Status) -->
                            <div class="grid grid-cols-3 gap-4 py-3 border-y border-gray-50 text-xs">
                                <div>
                                    <span class="block text-gray-450 font-bold uppercase tracking-wide mb-0.5">Assigned On</span>
                                    <span class="text-gray-850 font-semibold text-sm block mt-0.5">
                                        {{ assignment.created_at ? formatDateShort(assignment.created_at) : 'N/A' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="block text-gray-450 font-bold uppercase tracking-wide mb-0.5">Due Date</span>
                                    <span class="text-gray-850 font-semibold text-sm block mt-0.5">
                                        {{ assignment.service_request?.deadline ? formatDateShort(assignment.service_request.deadline) : 'None' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="block text-gray-450 font-bold uppercase tracking-wide mb-0.5">Status</span>
                                    <span class="px-2.5 py-0.5 rounded-full inline-block font-bold capitalize mt-0.5 text-[10px] text-center w-full"
                                        :class="{
                                            'bg-yellow-50 text-yellow-700 border border-yellow-150': assignment.status === 'assigned' || assignment.status === 'pending',
                                            'bg-blue-50 text-blue-700 border border-blue-150': assignment.status === 'accepted' || assignment.status === 'in_progress',
                                            'bg-green-50 text-green-700 border border-green-150': assignment.status === 'completed',
                                            'bg-red-50 text-red-700 border border-red-150': assignment.status === 'rejected'
                                        }">
                                        {{ assignment.status?.replace('_', ' ') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Completion Progress section -->
                            <div class="space-y-1.5">
                                <div class="flex justify-between text-xs font-bold text-gray-500">
                                    <span>Completion Progress</span>
                                    <span>{{ getProgressText(assignment) }}</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                    <div class="bg-brand-blue h-2 rounded-full transition-all duration-300" :style="{ width: getProgressPercent(assignment) + '%' }"></div>
                                </div>
                            </div>

                            <!-- Sub-Tasks Checklist -->
                            <div v-if="assignment.tasks?.length" class="space-y-2 pt-2">
                                <h4 class="text-xs font-bold text-gray-800 uppercase tracking-wide">Sub-Tasks Checklist ({{ assignment.tasks.length }})</h4>
                                <div class="space-y-2 max-h-32 overflow-y-auto pr-1">
                                    <div v-for="task in assignment.tasks" :key="task.id" class="p-2 border border-gray-100 rounded-lg flex items-center justify-between text-[11px] hover:bg-gray-50/50">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <svg v-if="task.status === 'completed'" class="w-4 h-4 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                            <div v-else class="w-3.5 h-3.5 rounded border border-gray-300 bg-white flex-shrink-0"></div>
                                            <span class="font-medium text-gray-800 truncate" :class="{ 'line-through text-gray-400': task.status === 'completed' }" :title="task.title">{{ task.title }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5 flex-shrink-0">
                                            <span class="px-1.5 py-0.5 rounded text-[8px] font-bold uppercase tracking-wider"
                                                  :class="{
                                                      'bg-red-50 text-red-650 text-red-600': task.priority === 'high' || task.priority === 'urgent',
                                                      'bg-blue-50 text-blue-600': task.priority === 'medium',
                                                      'bg-gray-50 text-gray-500': task.priority === 'low'
                                                  }">
                                                {{ task.priority }}
                                            </span>
                                            <span v-if="task.due_date" class="text-[9px] text-gray-400 font-semibold">
                                                Due {{ formatDateShort(task.due_date) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-xs text-gray-450 italic py-2">No sub-tasks configured.</div>
                        </div>

                        <div class="pt-4 border-t border-gray-50 flex justify-end">
                            <Link :href="route('assignments.show', assignment.id)" class="w-full text-center bg-[#0a1f44] text-white hover:bg-[#152a4d] px-4 py-2.5 rounded-xl font-bold text-xs transition shadow-sm">
                                Manage &amp; Submit Work &rarr;
                            </Link>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Default Administrator / Director Table Layout -->
            <div v-else class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
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
            </div>

            <!-- Shared Pagination -->
            <div class="px-6 py-4 bg-white border border-gray-100 rounded-xl shadow-sm flex items-center justify-between" v-if="assignments.total > 0">
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
    filters: Object,
    is_staff_outside_aos: Boolean,
});

const formatDateShort = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
};

const getProgressPercent = (assignment) => {
    const total = assignment.tasks?.length || 0;
    if (total === 0) {
        return assignment.status === 'completed' ? 100 : (assignment.status === 'in_progress' || assignment.status === 'accepted' ? 50 : 10);
    }
    const completed = assignment.tasks.filter(t => t.status === 'completed').length;
    return Math.round((completed / total) * 100);
};

const getProgressText = (assignment) => {
    const total = assignment.tasks?.length || 0;
    if (total === 0) {
        return assignment.status === 'completed' ? '100% Completed' : (assignment.status === 'in_progress' || assignment.status === 'accepted' ? '50% In Progress' : '10% Assigned');
    }
    const completed = assignment.tasks.filter(t => t.status === 'completed').length;
    return `${completed}/${total} Tasks (${Math.round((completed / total) * 100)}%)`;
};

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
