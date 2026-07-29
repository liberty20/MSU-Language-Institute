<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/inertia-vue3';
import { Inertia } from '@inertiajs/inertia';
import { ref, computed } from 'vue';

const props = defineProps({
    users: Object,
    filters: Object,
});

const search   = ref(props.filters?.search ?? '');
const selectedUser = ref(null);
const reason   = ref('');
const showModal = ref(false);
const submitting = ref(false);

const applySearch = () => {
    Inertia.get(route('admin.impersonate.index'), { search: search.value }, { preserveState: true, replace: true });
};

const openModal = (user) => {
    selectedUser.value = user;
    reason.value = '';
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedUser.value = null;
};

const impersonateForm = useForm({ reason: '' });

const startImpersonation = () => {
    if (!selectedUser.value) return;
    impersonateForm.reason = reason.value;
    submitting.value = true;
    impersonateForm.post(route('admin.impersonate.start', selectedUser.value.id), {
        onFinish: () => { submitting.value = false; },
    });
};

const getRoleBadgeClass = (role) => {
    const map = {
        ict_administrator: 'badge-blue',
        executive_director: 'badge-purple',
        deputy_director: 'badge-indigo',
        coordinator: 'badge-green',
        secretary: 'badge-teal',
        student: 'badge-amber',
        client: 'badge-orange',
        language_expert: 'badge-cyan',
        part_time_staff: 'badge-gray',
    };
    return map[role] ?? 'badge-gray';
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Impersonate User" />

        <template #header>Impersonate User</template>

        <div class="max-w-5xl mx-auto space-y-6">
            <!-- Page header -->
            <div class="card">
                <div class="flex items-start gap-4">
                    <div class="icon-wrap-amber">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h1 class="page-title">Super Administrator — User Impersonation</h1>
                        <p class="page-sub">
                            Select a user to impersonate for system maintenance. You will inherit their permissions and see the system exactly as they do.
                            All impersonation sessions are fully audited.
                        </p>
                        <div class="warning-pill">
                            ⚠️ For authorised maintenance only. Every session is recorded.
                        </div>
                    </div>
                    <a :href="route('admin.impersonation.logs')" class="btn-secondary" id="link-impersonation-logs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Audit Logs
                    </a>
                </div>
            </div>

            <!-- Search -->
            <div class="card">
                <div class="flex gap-3 items-center">
                    <div class="relative flex-1">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input
                            id="search-users"
                            v-model="search"
                            type="text"
                            placeholder="Search by name or email…"
                            class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"
                            @keydown.enter="applySearch"
                        />
                    </div>
                    <button @click="applySearch" class="btn-primary" id="btn-search-users">Search</button>
                </div>
            </div>

            <!-- User table -->
            <div class="card overflow-hidden p-0">
                <table class="w-full text-sm">
                    <thead class="table-head">
                        <tr>
                            <th class="th">User</th>
                            <th class="th">Roles</th>
                            <th class="th">Category</th>
                            <th class="th">Status</th>
                            <th class="th text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users.data" :key="user.id" class="table-row">
                            <td class="td">
                                <div class="font-semibold text-gray-900">{{ user.name }}</div>
                                <div class="text-xs text-gray-400">{{ user.email }}</div>
                            </td>
                            <td class="td">
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="role in user.roles" :key="role.id"
                                          :class="['badge', getRoleBadgeClass(role.name)]">
                                        {{ role.name.replace(/_/g, ' ') }}
                                    </span>
                                    <span v-if="!user.roles?.length" class="text-xs text-gray-400 italic">No role</span>
                                </div>
                            </td>
                            <td class="td text-xs text-gray-600">{{ user.primary_category ?? '—' }}</td>
                            <td class="td">
                                <span :class="user.is_active ? 'status-active' : 'status-inactive'">
                                    {{ user.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="td text-right">
                                <button
                                    :id="`btn-impersonate-${user.id}`"
                                    @click="openModal(user)"
                                    class="btn-impersonate"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Impersonate
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!users.data?.length">
                            <td colspan="5" class="td text-center text-gray-400 py-10 italic">No users found.</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="users.last_page > 1" class="px-6 py-3 border-t border-gray-100 flex items-center justify-between text-sm text-gray-500">
                    <span>Page {{ users.current_page }} of {{ users.last_page }}</span>
                    <div class="flex gap-2">
                        <a v-if="users.prev_page_url" :href="users.prev_page_url" class="btn-page">← Prev</a>
                        <a v-if="users.next_page_url" :href="users.next_page_url" class="btn-page">Next →</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Impersonation confirmation modal -->
        <Teleport to="body">
            <Transition name="modal-fade">
                <div v-if="showModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="impersonate-modal-title">
                    <div class="modal-card">
                        <h2 id="impersonate-modal-title" class="modal-title">Confirm Impersonation</h2>
                        <p class="modal-sub">
                            You are about to impersonate
                            <strong>{{ selectedUser?.name }}</strong>
                            ({{ selectedUser?.email }}).
                            This action will be logged.
                        </p>

                        <div class="mt-4">
                            <label for="impersonate-reason" class="block text-sm font-semibold text-gray-700 mb-1">
                                Reason for access <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                id="impersonate-reason"
                                v-model="reason"
                                rows="3"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-300"
                                placeholder="Describe the maintenance reason (min 10 characters)…"
                                maxlength="500"
                            ></textarea>
                            <p v-if="impersonateForm.errors.reason" class="text-xs text-red-600 mt-1">{{ impersonateForm.errors.reason }}</p>
                            <p class="text-xs text-gray-400 mt-0.5 text-right">{{ reason.length }}/500</p>
                        </div>

                        <div class="modal-actions mt-5">
                            <button @click="closeModal" class="btn-cancel" id="btn-cancel-impersonate">Cancel</button>
                            <button
                                id="btn-confirm-impersonate"
                                @click="startImpersonation"
                                :disabled="reason.length < 10 || submitting"
                                class="btn-confirm"
                            >
                                <span v-if="submitting" class="spinner"></span>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                Start Impersonation
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.card { @apply bg-white rounded-2xl border border-gray-100 shadow-sm p-6; }
.page-title { @apply text-xl font-bold text-gray-900; }
.page-sub { @apply text-sm text-gray-500 mt-1 leading-relaxed; }
.warning-pill { @apply mt-2 inline-block px-3 py-1 bg-amber-50 border border-amber-200 text-amber-700 rounded-full text-xs font-semibold; }
.icon-wrap-amber { @apply w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600 flex-shrink-0; }
.btn-primary { @apply flex items-center gap-1.5 px-4 py-2.5 bg-blue-700 text-white rounded-lg text-sm font-semibold hover:bg-blue-800 transition; }
.btn-secondary { @apply flex items-center gap-1.5 px-4 py-2 border border-gray-200 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 transition; }
.table-head { @apply bg-gray-50 border-b border-gray-100; }
.th { @apply px-5 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider; }
.td { @apply px-5 py-4; }
.table-row { @apply border-b border-gray-50 hover:bg-gray-50 transition-colors; }
.badge { @apply inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide; }
.badge-blue   { @apply bg-blue-100 text-blue-700; }
.badge-purple { @apply bg-purple-100 text-purple-700; }
.badge-indigo { @apply bg-indigo-100 text-indigo-700; }
.badge-green  { @apply bg-green-100 text-green-700; }
.badge-teal   { @apply bg-teal-100 text-teal-700; }
.badge-amber  { @apply bg-amber-100 text-amber-700; }
.badge-orange { @apply bg-orange-100 text-orange-700; }
.badge-cyan   { @apply bg-cyan-100 text-cyan-700; }
.badge-gray   { @apply bg-gray-100 text-gray-600; }
.status-active   { @apply inline-block px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full font-semibold; }
.status-inactive { @apply inline-block px-2 py-0.5 bg-gray-100 text-gray-500 text-xs rounded-full font-semibold; }
.btn-impersonate { @apply flex items-center gap-1.5 px-3 py-1.5 bg-amber-600 text-white rounded-lg text-xs font-bold hover:bg-amber-700 transition; }
.btn-page { @apply px-3 py-1 border border-gray-200 text-gray-600 rounded-lg text-xs hover:bg-gray-50 transition; }

/* Modal */
.modal-backdrop { @apply fixed inset-0 z-50 flex items-center justify-center p-4; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); }
.modal-card { @apply bg-white rounded-2xl shadow-2xl p-7 max-w-md w-full; }
.modal-title { @apply text-lg font-bold text-gray-900 mb-1; }
.modal-sub { @apply text-sm text-gray-500; }
.modal-actions { @apply flex gap-3 justify-end; }
.btn-cancel  { @apply px-4 py-2 border border-gray-200 text-gray-600 rounded-lg text-sm font-semibold hover:bg-gray-50 transition; }
.btn-confirm { @apply flex items-center gap-2 px-5 py-2 bg-amber-600 text-white rounded-lg text-sm font-bold hover:bg-amber-700 transition disabled:opacity-50 disabled:cursor-not-allowed; }
.spinner { @apply inline-block w-4 h-4 border-2 border-white/30 border-t-white rounded-full; animation: spin 0.6s linear infinite; }

.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.2s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }

@keyframes spin { to { transform: rotate(360deg); } }
</style>
