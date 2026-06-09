<template>
    <Head title="System Settings & Logs" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <span>Deputy Settings Portal</span>
                <span class="text-xs font-semibold px-3 py-1 bg-[#0a1f44]/10 text-[#0a1f44] rounded-full uppercase tracking-wider">
                    Deputy Director Access
                </span>
            </div>
        </template>

        <div class="space-y-8">
            <!-- Tabs Navigation -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button @click="activeTab = 'config'" 
                        :class="[
                            activeTab === 'config' ? 'border-brand-gold text-brand-blue font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all'
                        ]">
                        System Configuration
                    </button>
                    <button @click="activeTab = 'mail'" 
                        :class="[
                            activeTab === 'mail' ? 'border-brand-gold text-brand-blue font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all'
                        ]">
                        Mail Delivery Monitor
                    </button>
                    <button @click="activeTab = 'reset'" 
                        :class="[
                            activeTab === 'reset' ? 'border-brand-gold text-brand-blue font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all'
                        ]">
                        Database & Reset
                    </button>
                </nav>
            </div>

            <!-- Tab 1: System Configuration -->
            <div v-show="activeTab === 'config'" class="space-y-6">
                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                    <h3 class="text-lg font-bold text-brand-blue mb-2">Portal Options</h3>
                    <p class="text-sm text-gray-500 mb-6">Modify default environment variables and access controls for the MSULI institute.</p>

                    <form @submit.prevent="saveConfig" class="space-y-6 max-w-2xl">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Site Name</label>
                                <input v-model="configForm.site_name" type="text" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm" required />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Administrative Email</label>
                                <input v-model="configForm.admin_email" type="email" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm" required />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Support Phone</label>
                                <input v-model="configForm.support_phone" type="text" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm" required />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Max Upload Size (MB)</label>
                                <input v-model="configForm.max_upload_size" type="number" min="1" max="100" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm" required />
                            </div>
                            <div class="flex items-center gap-2 py-2">
                                <input v-model="configForm.maintenance_mode" type="checkbox" id="maintenance_mode" class="rounded border-gray-300 text-brand-blue focus:ring-brand-blue" />
                                <label for="maintenance_mode" class="text-sm font-bold text-gray-700 select-none">Enable System Maintenance Mode</label>
                            </div>
                            <div class="flex items-center gap-2 py-2">
                                <input v-model="configForm.allow_registrations" type="checkbox" id="allow_registrations" class="rounded border-gray-300 text-brand-blue focus:ring-brand-blue" />
                                <label for="allow_registrations" class="text-sm font-bold text-gray-700 select-none">Allow Public Student Registrations</label>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-100 flex justify-end">
                            <button type="submit" class="px-6 py-2.5 bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold rounded-xl transition shadow text-sm">
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tab 2: Mail Delivery Monitor -->
            <div v-show="activeTab === 'mail'" class="space-y-6">
                <!-- Statistics cards -->
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                        <div class="text-xs font-semibold text-gray-450 uppercase tracking-wider">Total Dispatched</div>
                        <div class="text-2xl font-black text-brand-blue mt-1">{{ stats.total }}</div>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                        <div class="text-xs font-semibold text-gray-450 uppercase tracking-wider">Delivered / Sent</div>
                        <div class="text-2xl font-black text-green-600 mt-1">{{ stats.sent + stats.delivered }}</div>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                        <div class="text-xs font-semibold text-gray-450 uppercase tracking-wider">Pending Queue</div>
                        <div class="text-2xl font-black text-amber-500 mt-1">{{ stats.pending }}</div>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                        <div class="text-xs font-semibold text-gray-450 uppercase tracking-wider">Bounced</div>
                        <div class="text-2xl font-black text-purple-600 mt-1">{{ stats.bounced }}</div>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm col-span-2 lg:col-span-1">
                        <div class="text-xs font-semibold text-gray-450 uppercase tracking-wider">Failures</div>
                        <div class="text-2xl font-black text-red-600 mt-1">{{ stats.failed }}</div>
                    </div>
                </div>

                <!-- Filters and logs table -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex flex-wrap gap-4 items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-bold text-brand-blue">Delivery Logs</span>
                            <div class="flex rounded-lg border border-gray-200 bg-white p-1 text-xs">
                                <button v-for="st in ['all', 'sent', 'pending', 'failed', 'bounced']" :key="st"
                                        @click="filterStatus(st)"
                                        class="px-3 py-1.5 rounded-md font-semibold transition uppercase"
                                        :class="activeStatus === st ? 'bg-[#0a1f44] text-white shadow-sm' : 'text-gray-500 hover:text-brand-blue'">
                                    {{ st }}
                                </button>
                            </div>
                        </div>

                        <!-- Search -->
                        <div class="relative w-full md:w-64">
                            <input v-model="searchQuery" @input="applySearch" type="text" placeholder="Search logs..." 
                                   class="w-full border-gray-200 rounded-xl text-sm focus:border-brand-gold focus:ring-brand-gold pl-9" />
                            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500">
                                    <th class="px-6 py-4 font-semibold">Recipient</th>
                                    <th class="px-6 py-4 font-semibold">Subject & Type</th>
                                    <th class="px-6 py-4 font-semibold">Status</th>
                                    <th class="px-6 py-4 font-semibold">Details / Error</th>
                                    <th class="px-6 py-4 font-semibold text-right">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-sm">
                                <tr v-for="log in emailLogs.data" :key="log.id" class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900">{{ log.recipient_email }}</div>
                                        <div class="text-xs text-gray-400 mt-0.5" v-if="log.sender_email">From: {{ log.sender_email }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-brand-blue">{{ log.subject }}</div>
                                        <div class="text-xs text-gray-400 mt-0.5 capitalize">{{ log.message_type.replace(/_/g, ' ') }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-full uppercase tracking-wider"
                                            :class="{
                                                'bg-green-100 text-green-700': log.status === 'sent' || log.status === 'delivered',
                                                'bg-amber-100 text-amber-700': log.status === 'pending',
                                                'bg-purple-100 text-purple-700': log.status === 'bounced',
                                                'bg-red-100 text-red-700': log.status === 'failed'
                                            }">
                                            {{ log.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-xs max-w-xs truncate" :title="log.error_message">
                                        <span class="text-gray-500" v-if="log.status === 'failed'">{{ log.error_message || 'Unknown network error' }}</span>
                                        <span class="text-gray-400 italic" v-else>Dispatched successfully</span>
                                    </td>
                                    <td class="px-6 py-4 text-right text-gray-500 text-xs">
                                        {{ formatDateTime(log.created_at) }}
                                    </td>
                                </tr>
                                <tr v-if="emailLogs.data.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                        No email logs found matching the filters.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between" v-if="emailLogs.links">
                        <span class="text-sm text-gray-500">Showing {{ emailLogs.from }} to {{ emailLogs.to }} of {{ emailLogs.total }}</span>
                        <div class="flex gap-1">
                            <Link v-for="(link, k) in emailLogs.links" :key="k"
                                  :href="link.url || '#'"
                                  class="px-3 py-1 rounded border text-sm transition"
                                  :class="link.active ? 'bg-[#0a1f44] border-[#0a1f44] text-white' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
                                  v-html="link.label">
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Database & Reset -->
            <div v-show="activeTab === 'reset'" class="space-y-6">
                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                    <h3 class="text-lg font-bold text-red-600 mb-2">Danger Zone - System Data Reset</h3>
                    <p class="text-sm text-gray-500 mb-6">Wipe out operational data models. This will clean the system records to start fresh without sample data.</p>

                    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-xl mb-6">
                        <div class="flex gap-3">
                            <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <div>
                                <h4 class="text-sm font-bold text-red-800">Critical warning: Permanent Deletion</h4>
                                <p class="text-xs text-red-700 mt-1">
                                    Performing this operation will permanently delete:
                                </p>
                                <ul class="list-disc pl-5 text-xs text-red-700 mt-1 space-y-0.5">
                                    <li>All Clients and their linked User accounts</li>
                                    <li>All Quotations and Service Requests</li>
                                    <li>All Assignments, Instructor Tasks, and Subtasks</li>
                                    <li>All Client Payments and proof records</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <button @click="confirmReset = true" class="bg-red-600 hover:bg-red-700 text-white font-bold px-6 py-2.5 rounded-xl transition shadow text-sm">
                        Reset Operational Database
                    </button>
                </div>
            </div>
        </div>

        <!-- Confirm Reset Modal -->
        <div v-if="confirmReset" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl p-6 space-y-4">
                <div class="flex items-center gap-3 text-red-650">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <h3 class="text-lg font-bold">Confirm Database Reset?</h3>
                </div>
                <p class="text-sm text-gray-500 leading-relaxed">
                    Are you absolutely sure you want to clean the system? This will delete all client histories, request workflows, billing quotations, and instructor assignments. <strong>This action cannot be undone.</strong>
                </p>
                <div class="pt-4 flex justify-end gap-3">
                    <button @click="confirmReset = false" class="px-4 py-2 border border-gray-300 rounded-xl font-semibold text-gray-700 hover:bg-gray-50 transition text-sm">
                        Cancel
                    </button>
                    <button @click="executeReset" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition shadow text-sm">
                        Yes, Reset Data
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/inertia-vue3';
import { Inertia } from '@inertiajs/inertia';
import { ref, computed } from 'vue';

const props = defineProps({
    emailLogs: Object,
    stats: Object,
    config: Object,
    filters: Object,
});

const activeTab = ref('config');
const confirmReset = ref(false);

const configForm = ref({
    site_name: props.config.site_name || '',
    admin_email: props.config.admin_email || '',
    support_phone: props.config.support_phone || '',
    max_upload_size: props.config.max_upload_size || 10,
    maintenance_mode: props.config.maintenance_mode === 1 || props.config.maintenance_mode === true,
    allow_registrations: props.config.allow_registrations === 1 || props.config.allow_registrations === true,
});

const searchQuery = ref(props.filters.search || '');
const activeStatus = computed(() => props.filters.status || 'all');

const saveConfig = () => {
    Inertia.post(route('deputy.settings.config'), configForm.value, {
        preserveState: true,
        onSuccess: () => {
            // Toast / success handles automatically in layout flashes
        }
    });
};

const filterStatus = (status) => {
    Inertia.get(route('deputy.settings'), {
        status: status,
        search: searchQuery.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const applySearch = () => {
    Inertia.get(route('deputy.settings'), {
        status: activeStatus.value,
        search: searchQuery.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const executeReset = () => {
    confirmReset.value = false;
    Inertia.post(route('deputy.settings.reset'), {}, {
        onSuccess: () => {
            activeTab.value = 'config';
        }
    });
};

const formatDateTime = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) + 
           ' ' + date.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
};
</script>
