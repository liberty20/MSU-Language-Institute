<template>
    <AuthenticatedLayout>
        <template #header>
            System Audit Trail
        </template>

        <div class="space-y-6">
            <!-- Premium Header Info Card -->
            <div class="bg-gradient-to-r from-[#0a1f44] to-[#0c2859] text-white p-6 rounded-2xl border border-gray-150 shadow-md flex items-center justify-between">
                <div class="space-y-1">
                    <span class="inline-block py-0.5 px-3 rounded-full bg-white/10 text-brand-gold text-xs font-bold tracking-widest uppercase mb-1">
                        Administrative Security Monitoring
                    </span>
                    <h3 class="text-xl font-bold">Refined System Operations Audit Log</h3>
                    <p class="text-sm text-gray-300">Concise tracking of administrative changes, sensitive record modifications, credentials changes, and workflow state transitions.</p>
                </div>
                <div class="w-16 h-16 rounded-full bg-white/10 flex items-center justify-center text-brand-gold text-2xl shadow flex-shrink-0">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
            </div>

            <!-- Controls and Filters Grid -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-4">
                <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-2">Filter and Scope Audit Records</h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- General search -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-gray-500 uppercase">Search Description</label>
                        <div class="relative">
                            <input
                                v-model="filters.search"
                                @input="handleFilterChange"
                                type="text"
                                placeholder="Search action text..."
                                class="w-full pl-8 pr-3 py-2 text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm"
                            />
                            <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </span>
                        </div>
                    </div>

                    <!-- User Performing -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-gray-500 uppercase">User Performing Action</label>
                        <input
                            v-model="filters.user"
                            @input="handleFilterChange"
                            type="text"
                            placeholder="Name or email..."
                            class="w-full px-3 py-2 text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm"
                        />
                    </div>

                    <!-- Affected User -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-gray-500 uppercase">Affected User / Record</label>
                        <input
                            v-model="filters.affected_user"
                            @input="handleFilterChange"
                            type="text"
                            placeholder="Recipient or subject name..."
                            class="w-full px-3 py-2 text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm"
                        />
                    </div>

                    <!-- Action Type -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-gray-500 uppercase">Action Type</label>
                        <input
                            v-model="filters.action_type"
                            @input="handleFilterChange"
                            type="text"
                            placeholder="e.g. Password, Created..."
                            class="w-full px-3 py-2 text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm"
                        />
                    </div>

                    <!-- Module Filter -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-gray-500 uppercase">System Module</label>
                        <select
                            v-model="filters.module"
                            @change="handleFilterChange"
                            class="w-full px-3 py-2 text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm"
                        >
                            <option value="">All Modules</option>
                            <option value="User Management">User Management</option>
                            <option value="Course & Enrollment">Course &amp; Enrollment</option>
                            <option value="Approval Workflows">Approval Workflows</option>
                            <option value="Quotation Management">Quotation Management</option>
                            <option value="System Administration">System Administration</option>
                        </select>
                    </div>

                    <!-- Start Date -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-gray-500 uppercase">Start Date</label>
                        <input
                            v-model="filters.date_start"
                            @change="handleFilterChange"
                            type="date"
                            class="w-full px-3 py-2 text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm"
                        />
                    </div>

                    <!-- End Date -->
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-gray-500 uppercase">End Date</label>
                        <input
                            v-model="filters.date_end"
                            @change="handleFilterChange"
                            type="date"
                            class="w-full px-3 py-2 text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm"
                        />
                    </div>

                    <!-- Clear Action -->
                    <div class="flex items-end justify-end">
                        <button
                            @click="clearFilters"
                            class="w-full py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition flex items-center justify-center gap-1.5 shadow-sm"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            Reset Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Premium Display Grid -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-6">
                <!-- Table -->
                <div class="overflow-x-auto rounded-xl border border-gray-100">
                    <table class="w-full text-left border-collapse min-w-max">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500 font-bold select-none">
                                <th class="px-6 py-4">Date &amp; Time</th>
                                <th class="px-6 py-4">User</th>
                                <th class="px-6 py-4">Action Type</th>
                                <th class="px-6 py-4">Affected Record</th>
                                <th class="px-6 py-4">Module</th>
                                <th class="px-6 py-4">Previous Value</th>
                                <th class="px-6 py-4">New Value</th>
                                <th class="px-6 py-4">IP Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50/50 transition">
                                <!-- Date & Time -->
                                <td class="px-6 py-4 whitespace-nowrap text-xs font-semibold text-gray-600">
                                    {{ log.created_at }}
                                </td>
                                
                                <!-- User Performing Action -->
                                <td class="px-6 py-4">
                                    <div class="space-y-0.5">
                                        <p class="font-bold text-gray-800">{{ log.user_performing }}</p>
                                    </div>
                                </td>
                                
                                <!-- Action Type -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded text-[10px] uppercase font-bold tracking-wider"
                                          :class="{
                                              'bg-blue-50 text-blue-700': log.action.toLowerCase().includes('create') || log.action.toLowerCase().includes('add'),
                                              'bg-amber-50 text-amber-700': log.action.toLowerCase().includes('update') || log.action.toLowerCase().includes('change') || log.action.toLowerCase().includes('reassign') || log.action.toLowerCase().includes('recommend'),
                                              'bg-red-50 text-red-700': log.action.toLowerCase().includes('delete') || log.action.toLowerCase().includes('reject') || log.action.toLowerCase().includes('deactivate') || log.action.toLowerCase().includes('failed'),
                                              'bg-green-50 text-green-700': log.action.toLowerCase().includes('approve') || log.action.toLowerCase().includes('activate') || log.action.toLowerCase().includes('enroll') || log.action.toLowerCase().includes('verify'),
                                              'bg-slate-100 text-slate-700': !['create', 'add', 'update', 'change', 'reassign', 'recommend', 'delete', 'reject', 'deactivate', 'failed', 'approve', 'activate', 'enroll', 'verify'].some(w => log.action.toLowerCase().includes(w))
                                          }">
                                        {{ log.action }}
                                    </span>
                                </td>
                                
                                <!-- Affected Record -->
                                <td class="px-6 py-4 text-xs font-bold text-gray-650 max-w-xs truncate" :title="log.description">
                                    {{ log.affected }}
                                </td>
                                
                                <!-- Module -->
                                <td class="px-6 py-4 whitespace-nowrap text-xs font-semibold">
                                    <span class="px-2 py-0.5 rounded-full text-[9px] uppercase tracking-widest font-black"
                                          :class="{
                                              'bg-purple-100 text-purple-700': log.module === 'User Management',
                                              'bg-blue-100 text-blue-700': log.module === 'Course & Enrollment',
                                              'bg-amber-100 text-amber-700': log.module === 'Approval Workflows',
                                              'bg-emerald-100 text-emerald-700': log.module === 'Quotation Management',
                                              'bg-slate-150 text-gray-600': log.module === 'System Administration'
                                          }">
                                        {{ log.module }}
                                    </span>
                                </td>
                                
                                <!-- Previous Value -->
                                <td class="px-6 py-4 max-w-xs font-mono text-xs text-gray-500 overflow-x-auto select-all leading-normal" :title="log.previous_value">
                                    <span v-if="log.previous_value.startsWith('{')" class="block bg-gray-50 p-1.5 rounded border border-gray-100 text-[10px] max-h-16 overflow-y-auto whitespace-pre-wrap select-all">
                                        {{ log.previous_value }}
                                    </span>
                                    <span v-else class="font-semibold">{{ log.previous_value }}</span>
                                </td>
                                
                                <!-- New Value -->
                                <td class="px-6 py-4 max-w-xs font-mono text-xs text-gray-500 overflow-x-auto select-all leading-normal" :title="log.new_value">
                                    <span v-if="log.new_value.startsWith('{')" class="block bg-gray-50 p-1.5 rounded border border-gray-100 text-[10px] max-h-16 overflow-y-auto whitespace-pre-wrap select-all">
                                        {{ log.new_value }}
                                    </span>
                                    <span v-else class="font-semibold">{{ log.new_value }}</span>
                                </td>
                                
                                <!-- IP Address -->
                                <td class="px-6 py-4 whitespace-nowrap font-mono text-xs text-gray-500 select-all">
                                    {{ log.ip_address }}
                                </td>
                            </tr>
                            <tr v-if="logs.data.length === 0">
                                <td colspan="8" class="px-6 py-12 text-center text-gray-400 italic">
                                    No administrative audit logs found matching the selected filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Premium Pagination -->
                <div v-if="logs.links && logs.links.length > 3" class="flex items-center justify-between border-t border-gray-100 pt-4">
                    <div class="text-xs text-gray-500 font-bold">
                        Showing {{ logs.from || 0 }} to {{ logs.to || 0 }} of {{ logs.total }} audit records
                    </div>
                    <div class="flex gap-1.5">
                        <template v-for="(link, idx) in logs.links" :key="idx">
                            <span v-if="link.url === null" v-html="link.label" class="px-3 py-1.5 text-xs text-gray-300 border border-gray-100 rounded-xl pointer-events-none select-none"></span>
                            <Link v-else :href="link.url" v-html="link.label" class="px-3 py-1.5 text-xs border rounded-xl font-bold transition duration-150"
                                  :class="link.active ? 'bg-[#0a1f44] text-white border-[#0a1f44] shadow-sm' : 'text-gray-600 border-gray-200 hover:bg-gray-50'"></Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import { Link } from '@inertiajs/inertia-vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    logs: Object,
    filters: Object,
});

// Reactive filters state
const filters = reactive({
    search: props.filters.search || '',
    user: props.filters.user || '',
    action_type: props.filters.action_type || '',
    module: props.filters.module || '',
    affected_user: props.filters.affected_user || '',
    date_start: props.filters.date_start || '',
    date_end: props.filters.date_end || '',
});

let filterTimeout = null;

const handleFilterChange = () => {
    if (filterTimeout) clearTimeout(filterTimeout);
    
    // Debounce type inputs to prevent repetitive network requests
    filterTimeout = setTimeout(() => {
        Inertia.get(route('admin.audit-trail'), { ...filters }, {
            preserveState: true,
            replace: true,
        });
    }, 400);
};

const clearFilters = () => {
    filters.search = '';
    filters.user = '';
    filters.action_type = '';
    filters.module = '';
    filters.affected_user = '';
    filters.date_start = '';
    filters.date_end = '';
    
    Inertia.get(route('admin.audit-trail'), {}, {
        preserveState: true,
        replace: true,
    });
};
</script>
