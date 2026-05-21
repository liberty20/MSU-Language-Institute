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
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Request <span class="text-red-500">*</span></label>
                        <select v-model="form.service_request_id" required
                                class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm">
                            <option value="">Choose a service request…</option>
                            <option v-for="sr in serviceRequests" :key="sr.id" :value="sr.id">
                                {{ sr.reference_number }} — {{ sr.title }}
                            </option>
                        </select>
                        <p v-if="form.errors.service_request_id" class="text-red-500 text-xs mt-1">{{ form.errors.service_request_id }}</p>
                    </div>
                </div>

                <!-- Staff Selection -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-6 h-6 bg-[#0a1f44] text-white text-xs rounded-full flex items-center justify-center font-bold">2</span>
                        Staff Member
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Assign To <span class="text-red-500">*</span></label>
                            <select v-model="form.assigned_to" required
                                    class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm">
                                <option value="">Choose a staff member…</option>
                                <optgroup v-for="group in staffGroups" :key="group.label" :label="group.label">
                                    <option v-for="staff in group.members" :key="staff.id" :value="staff.id">
                                        {{ staff.name }} {{ staff.email ? `(${staff.email})` : '' }}
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
import { computed } from 'vue';

const props = defineProps({
    serviceRequests: Array,
    staff: Array,
    preselectedServiceRequestId: [String, Number],
});

const form = useForm({
    service_request_id: props.preselectedServiceRequestId || '',
    assigned_to: '',
    role_in_task: '',
    notes: '',
});

// Group staff by their roles for better UX
const staffGroups = computed(() => {
    const all = props.staff || [];
    return [{ label: 'All Staff', members: all }];
});

const submit = () => {
    form.post(route('assignments.store'));
};
</script>
