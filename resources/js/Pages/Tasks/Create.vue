<template>
    <Head title="New Task" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('tasks.index')" class="text-gray-400 hover:text-[#0a1f44] transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <span class="text-gray-400">/</span>
                <span>New Task</span>
            </div>
        </template>

        <div class="max-w-2xl mx-auto">
            <form @submit.prevent="submit" class="space-y-6">

                <!-- Assignment -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-6 h-6 bg-[#0a1f44] text-white text-xs rounded-full flex items-center justify-center font-bold">1</span>
                        Link to Assignment
                    </h2>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select Assignment <span class="text-red-500">*</span></label>
                        <select v-model="form.assignment_id" required
                                class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm">
                            <option value="">Choose an assignment…</option>
                            <option v-for="assignment in assignments" :key="assignment.id" :value="assignment.id">
                                {{ assignment.service_request?.reference_number }} — {{ assignment.role_in_task }} ({{ assignment.assignee?.name }})
                            </option>
                        </select>
                        <p v-if="form.errors.assignment_id" class="text-red-500 text-xs mt-1">{{ form.errors.assignment_id }}</p>
                    </div>
                </div>

                <!-- Task Details -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-6 h-6 bg-[#0a1f44] text-white text-xs rounded-full flex items-center justify-center font-bold">2</span>
                        Task Details
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Task Title <span class="text-red-500">*</span></label>
                            <input v-model="form.title" type="text" required placeholder="e.g., Translate Chapter 1"
                                   class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm" />
                            <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea v-model="form.description" rows="3" placeholder="Provide task specifics..."
                                      class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm resize-none"></textarea>
                            <p v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                                <select v-model="form.priority"
                                        class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                                <input v-model="form.due_date" type="date"
                                       class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm" />
                                <p v-if="form.errors.due_date" class="text-red-500 text-xs mt-1">{{ form.errors.due_date }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pb-6">
                    <Link :href="route('tasks.index')"
                          class="px-5 py-2.5 border border-gray-300 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </Link>
                    <button type="submit" :disabled="form.processing"
                            class="px-6 py-2.5 bg-[#0a1f44] text-white rounded-xl text-sm font-bold hover:bg-[#0a1f44]/80 transition disabled:opacity-50 shadow-sm flex items-center gap-2">
                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Create Task
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/inertia-vue3';

const props = defineProps({
    assignments: Array,
    preselectedAssignmentId: [String, Number],
});

const form = useForm({
    assignment_id: props.preselectedAssignmentId || '',
    title: '',
    description: '',
    priority: 'medium',
    due_date: '',
});

const submit = () => {
    form.post(route('tasks.store'));
};
</script>
