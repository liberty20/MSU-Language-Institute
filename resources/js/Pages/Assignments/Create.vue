<template>
    <Head title="New Assignment" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('assignments.index')" class="text-gray-400 hover:text-[#0a1f44] transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <span class="text-gray-400">/</span>
                <span>New Assignment</span>
            </div>
        </template>

        <div class="max-w-2xl mx-auto">
            <form @submit.prevent="submit" class="space-y-6">

                <!-- Service Request -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-6 h-6 bg-[#0a1f44] text-white text-xs rounded-full flex items-center justify-center font-bold">1</span>
                        Service Request
                    </h2>
                    <div class="relative" ref="srDropdownRef">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Request <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input
                                type="text"
                                v-model="srSearch"
                                @focus="srDropdownOpen = true"
                                @input="srDropdownOpen = true"
                                :placeholder="selectedSrLabel || 'Search by reference number or title…'"
                                :class="[
                                    'w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm pr-10',
                                    form.service_request_id && !srSearch ? 'text-gray-900' : ''
                                ]"
                            />
                            <!-- Clear / Chevron icon -->
                            <button v-if="form.service_request_id" type="button" @click.stop="clearSrSelection"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-red-500 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            <span v-else class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 pointer-events-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </span>
                        </div>
                        <!-- Dropdown list -->
                        <div v-if="srDropdownOpen" class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                            <div v-if="filteredServiceRequests.length === 0" class="px-4 py-3 text-sm text-gray-500 italic">
                                No matching requests found.
                            </div>
                            <button
                                v-for="sr in filteredServiceRequests"
                                :key="sr.id"
                                type="button"
                                @mousedown.prevent="selectSr(sr)"
                                :class="[
                                    'w-full text-left px-4 py-2.5 text-sm hover:bg-indigo-50 transition flex items-center gap-2',
                                    form.service_request_id === sr.id ? 'bg-indigo-50 font-semibold text-[#0a1f44]' : 'text-gray-700'
                                ]"
                            >
                                <svg v-if="form.service_request_id === sr.id" class="w-4 h-4 text-indigo-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                <span>{{ sr.reference_number }} — {{ sr.title }}</span>
                            </button>
                        </div>
                        <p v-if="form.errors.service_request_id" class="text-red-500 text-xs mt-1">{{ form.errors.service_request_id }}</p>
                    </div>
                </div>

                <!-- Recipient Selection -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-6 h-6 bg-[#0a1f44] text-white text-xs rounded-full flex items-center justify-center font-bold">2</span>
                        Recipient Assignment
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Assign To Type <span class="text-red-500">*</span></label>
                            <select v-model="form.assign_to_type" required
                                    class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm">
                                <option value="staff">Staff Member</option>
                                <option value="coordinator">Coordinator</option>
                            </select>
                            <p v-if="form.errors.assign_to_type" class="text-red-500 text-xs mt-1">{{ form.errors.assign_to_type }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Assign To <span class="text-red-500">*</span></label>
                            <select v-model="form.assigned_to" required
                                    class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm">
                                <option value="">Choose a recipient…</option>
                                <optgroup v-for="group in staffGroups" :key="group.label" :label="group.label">
                                    <option v-for="recipient in group.members" :key="recipient.id" :value="recipient.id">
                                        {{ recipient.name }} {{ recipient.email ? `(${recipient.email})` : '' }}
                                    </option>
                                </optgroup>
                            </select>
                            <p v-if="form.errors.assigned_to" class="text-red-500 text-xs mt-1">{{ form.errors.assigned_to }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role in Task <span class="text-red-500">*</span></label>
                            <select v-model="form.role_in_task" required
                                    class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm">
                                <option value="">Select role…</option>
                                <option value="translator">Translator</option>
                                <option value="editor">Editor</option>
                                <option value="reviewer">Reviewer</option>
                                <option value="brailler">Brailler</option>
                                <option value="interpreter">Interpreter</option>
                                <option value="transcriber">Transcriber</option>
                                <option value="project_lead">Project Lead</option>
                                <option value="sign_language_expert">Sign Language Expert</option>
                                <option value="quality_checker">Quality Checker</option>
                            </select>
                            <p v-if="form.errors.role_in_task" class="text-red-500 text-xs mt-1">{{ form.errors.role_in_task }}</p>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-6 h-6 bg-[#0a1f44] text-white text-xs rounded-full flex items-center justify-center font-bold">3</span>
                        Instructions
                    </h2>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Assignment Notes</label>
                        <textarea v-model="form.notes" rows="4"
                                  class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm resize-none"
                                  placeholder="Specific instructions, deadlines, or expectations for this assignment…"></textarea>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pb-6">
                    <Link :href="route('assignments.index')"
                          class="px-5 py-2.5 border border-gray-300 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </Link>
                    <button type="submit" :disabled="form.processing"
                            class="px-6 py-2.5 bg-[#0a1f44] text-white rounded-xl text-sm font-bold hover:bg-[#0a1f44]/80 transition disabled:opacity-50 shadow-sm flex items-center gap-2">
                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Create Assignment
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/inertia-vue3';
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    serviceRequests: Array,
    staff: Array,
    coordinators: Array,
    preselectedServiceRequestId: [String, Number],
});

const form = useForm({
    service_request_id: props.preselectedServiceRequestId || '',
    assign_to_type: 'staff',
    assigned_to: '',
    role_in_task: '',
    notes: '',
});

// ── Searchable Service Request Dropdown ──
const srSearch = ref('');
const srDropdownOpen = ref(false);
const srDropdownRef = ref(null);

const filteredServiceRequests = computed(() => {
    const query = srSearch.value.toLowerCase().trim();
    if (!query) return props.serviceRequests || [];
    return (props.serviceRequests || []).filter(sr => {
        const ref = (sr.reference_number || '').toLowerCase();
        const title = (sr.title || '').toLowerCase();
        return ref.includes(query) || title.includes(query);
    });
});

const selectedSrLabel = computed(() => {
    if (!form.service_request_id) return '';
    const sr = (props.serviceRequests || []).find(s => s.id === form.service_request_id);
    return sr ? `${sr.reference_number} — ${sr.title}` : '';
});

const selectSr = (sr) => {
    form.service_request_id = sr.id;
    srSearch.value = '';
    srDropdownOpen.value = false;
};

const clearSrSelection = () => {
    form.service_request_id = '';
    srSearch.value = '';
};

// Close dropdown when clicking outside
const handleClickOutside = (e) => {
    if (srDropdownRef.value && !srDropdownRef.value.contains(e.target)) {
        srDropdownOpen.value = false;
    }
};
onMounted(() => document.addEventListener('mousedown', handleClickOutside));
onBeforeUnmount(() => document.removeEventListener('mousedown', handleClickOutside));

// Watch type selection and reset selected user
watch(() => form.assign_to_type, () => {
    form.assigned_to = '';
});

// Compute active list of members depending on selection type
const eligibleRecipients = computed(() => {
    if (form.assign_to_type === 'coordinator') {
        return props.coordinators || [];
    }
    return props.staff || [];
});

// Group recipients for optgroup formatting
const staffGroups = computed(() => {
    const label = form.assign_to_type === 'coordinator' ? 'All Coordinators' : 'All Staff';
    return [{ label: label, members: eligibleRecipients.value }];
});

const submit = () => {
    form.post(route('assignments.store'));
};
</script>
