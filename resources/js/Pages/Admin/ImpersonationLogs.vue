<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/inertia-vue3';

const props = defineProps({
    logs: Object,
});

const formatDate = (d) => {
    if (!d) return '—';
    return new Date(d).toLocaleString(undefined, {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit', second: '2-digit',
    });
};

const formatDuration = (seconds) => {
    if (seconds == null) return '—';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    if (h > 0) return `${h}h ${m}m ${s}s`;
    if (m > 0) return `${m}m ${s}s`;
    return `${s}s`;
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Impersonation Audit Logs" />
        <template #header>Impersonation Audit Logs</template>

        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Header card -->
            <div class="card">
                <div class="flex items-center gap-4">
                    <div class="icon-wrap">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="page-title">Impersonation Session Logs</h1>
                        <p class="page-sub">Full audit trail of all Super Administrator impersonation sessions.</p>
                    </div>
                    <div class="ml-auto">
                        <a :href="route('admin.impersonate.index')" class="btn-back">
                            ← Back to Impersonation
                        </a>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="card overflow-hidden p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[900px]">
                        <thead class="table-head">
                            <tr>
                                <th class="th">#</th>
                                <th class="th">Administrator</th>
                                <th class="th">Impersonated User</th>
                                <th class="th">Reason</th>
                                <th class="th">Started At</th>
                                <th class="th">Ended At</th>
                                <th class="th">Duration</th>
                                <th class="th">IP Address</th>
                                <th class="th">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in logs.data" :key="log.id" class="table-row">
                                <td class="td text-gray-400 text-xs">{{ log.id }}</td>
                                <td class="td">
                                    <div class="font-semibold text-gray-900">{{ log.impersonator?.name ?? '—' }}</div>
                                    <div class="text-xs text-gray-400">{{ log.impersonator?.email }}</div>
                                </td>
                                <td class="td">
                                    <div class="font-semibold text-gray-900">{{ log.impersonated?.name ?? '—' }}</div>
                                    <div class="text-xs text-gray-400">{{ log.impersonated?.email }}</div>
                                </td>
                                <td class="td max-w-[220px]">
                                    <p class="text-xs text-gray-600 leading-relaxed line-clamp-3" :title="log.reason">{{ log.reason }}</p>
                                </td>
                                <td class="td text-xs text-gray-600 whitespace-nowrap">{{ formatDate(log.started_at) }}</td>
                                <td class="td text-xs text-gray-600 whitespace-nowrap">{{ formatDate(log.ended_at) }}</td>
                                <td class="td">
                                    <span class="font-mono text-xs" :class="log.ended_at ? 'text-green-700' : 'text-amber-600'">
                                        {{ formatDuration(log.duration_seconds) }}
                                    </span>
                                </td>
                                <td class="td text-xs font-mono text-gray-500">{{ log.ip_address ?? '—' }}</td>
                                <td class="td">
                                    <span :class="log.ended_at ? 'status-ended' : 'status-active'">
                                        {{ log.ended_at ? 'Ended' : 'Active' }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!logs.data?.length">
                                <td colspan="9" class="td text-center text-gray-400 py-12 italic">
                                    No impersonation sessions recorded yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="logs.last_page > 1" class="px-6 py-3 border-t border-gray-100 flex items-center justify-between text-sm text-gray-500">
                    <span>Showing {{ logs.from }}–{{ logs.to }} of {{ logs.total }} records</span>
                    <div class="flex gap-2">
                        <a v-if="logs.prev_page_url" :href="logs.prev_page_url" class="btn-page">← Prev</a>
                        <a v-if="logs.next_page_url" :href="logs.next_page_url" class="btn-page">Next →</a>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.card       { @apply bg-white rounded-2xl border border-gray-100 shadow-sm p-6; }
.icon-wrap  { @apply w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600 flex-shrink-0; }
.page-title { @apply text-xl font-bold text-gray-900; }
.page-sub   { @apply text-sm text-gray-500 mt-0.5; }
.btn-back   { @apply text-sm text-gray-500 hover:text-blue-700 font-medium transition; }
.table-head { @apply bg-gray-50 border-b border-gray-100; }
.th         { @apply px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider; }
.td         { @apply px-4 py-4; }
.table-row  { @apply border-b border-gray-50 hover:bg-gray-50 transition-colors; }
.status-active { @apply inline-block px-2.5 py-0.5 bg-amber-100 text-amber-700 text-xs rounded-full font-semibold; }
.status-ended  { @apply inline-block px-2.5 py-0.5 bg-green-100 text-green-700 text-xs rounded-full font-semibold; }
.btn-page   { @apply px-3 py-1.5 border border-gray-200 text-gray-600 rounded-lg text-xs hover:bg-gray-50 transition; }
</style>
