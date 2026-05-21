<template>
    <Head title="New Procurement Request" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('procurement.index')" class="text-gray-400 hover:text-[#0a1f44] transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <span class="text-gray-400">/</span>
                <span>New Procurement Request</span>
            </div>
        </template>

        <div class="max-w-3xl mx-auto">
            <form @submit.prevent="submit" class="space-y-6">

                <!-- Request Info -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-6 h-6 bg-[#0a1f44] text-white text-xs rounded-full flex items-center justify-center font-bold">1</span>
                        Request Details
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                            <input v-model="form.title" type="text" required placeholder="Short summary of request"
                                   class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm" />
                            <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description <span class="text-red-500">*</span></label>
                            <textarea v-model="form.description" rows="4" required placeholder="Detailed description of items or services needed..."
                                      class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm resize-none"></textarea>
                            <p v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</p>
                        </div>
                    </div>
                </div>

                <!-- Financial & Justification -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-6 h-6 bg-[#0a1f44] text-white text-xs rounded-full flex items-center justify-center font-bold">2</span>
                        Justification & Costs
                    </h2>
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Cost (USD) <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">$</span>
                                    </div>
                                    <input v-model="form.estimated_cost" type="number" step="0.01" min="0" required
                                           class="pl-7 w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm" placeholder="0.00" />
                                </div>
                                <p v-if="form.errors.estimated_cost" class="text-red-500 text-xs mt-1">{{ form.errors.estimated_cost }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                                <select v-model="form.priority"
                                        class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Justification <span class="text-red-500">*</span></label>
                            <textarea v-model="form.justification" rows="3" required placeholder="Why is this purchase necessary for operations?"
                                      class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm resize-none"></textarea>
                            <p v-if="form.errors.justification" class="text-red-500 text-xs mt-1">{{ form.errors.justification }}</p>
                        </div>
                        <div v-if="departments && departments.length > 0">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                            <select v-model="form.department_id"
                                    class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm">
                                <option value="">Select department (optional)</option>
                                <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pb-6">
                    <Link :href="route('procurement.index')"
                          class="px-5 py-2.5 border border-gray-300 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </Link>
                    <button type="submit" :disabled="form.processing"
                            class="px-6 py-2.5 bg-[#f5c242] text-[#0a1f44] rounded-xl text-sm font-bold hover:bg-yellow-400 transition disabled:opacity-50 shadow-sm flex items-center gap-2">
                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/inertia-vue3';

defineProps({
    departments: Array
});

const form = useForm({
    title: '',
    description: '',
    estimated_cost: '',
    justification: '',
    priority: 'medium',
    department_id: '',
});

const submit = () => {
    form.post(route('procurement.store'));
};
</script>
