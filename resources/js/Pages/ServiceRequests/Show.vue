<template>
    <Head :title="`Request ${serviceRequest.reference_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('service-requests.index')" class="text-gray-400 hover:text-[#0a1f44] transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </Link>
                    <span class="text-gray-400">/</span>
                    <span>{{ serviceRequest.reference_number }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide"
                        :class="{
                            'bg-yellow-100 text-yellow-800': serviceRequest.status === 'pending',
                            'bg-blue-100 text-blue-800': serviceRequest.status === 'in_progress',
                            'bg-green-100 text-green-800': serviceRequest.status === 'completed',
                            'bg-purple-100 text-purple-800': serviceRequest.status === 'quoted',
                            'bg-teal-100 text-teal-800': serviceRequest.status === 'approved',
                            'bg-orange-100 text-orange-800': serviceRequest.status === 'review',
                            'bg-gray-100 text-gray-700': serviceRequest.status === 'cancelled',
                        }">
                        {{ serviceRequest.status.replace(/_/g, ' ') }}
                    </span>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Request Details -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#0a1f44]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Request Details
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Title</p>
                            <p class="text-gray-900 font-medium">{{ serviceRequest.title }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Description</p>
                            <p class="text-gray-700 whitespace-pre-wrap leading-relaxed">{{ serviceRequest.description }}</p>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 pt-2 border-t border-gray-100">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Service Category</p>
                                <p class="text-gray-800 font-medium capitalize">{{ serviceRequest.service_category.replace(/_/g, ' ') }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Priority</p>
                                <span class="px-2.5 py-1 rounded text-xs font-bold uppercase"
                                    :class="{
                                        'bg-red-100 text-red-800': serviceRequest.priority === 'urgent',
                                        'bg-orange-100 text-orange-800': serviceRequest.priority === 'high',
                                        'bg-blue-100 text-blue-800': serviceRequest.priority === 'medium',
                                        'bg-gray-100 text-gray-600': serviceRequest.priority === 'low',
                                    }">
                                    {{ serviceRequest.priority }}
                                </span>
                            </div>
                            <div v-if="serviceRequest.deadline">
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Deadline</p>
                                <p class="text-gray-800 font-medium">{{ serviceRequest.deadline }}</p>
                            </div>
                            <div v-if="serviceRequest.source_language">
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Source Language</p>
                                <p class="text-gray-800 font-medium">{{ serviceRequest.source_language }}</p>
                            </div>
                            <div v-if="serviceRequest.target_language">
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Target Language</p>
                                <p class="text-gray-800 font-medium">{{ serviceRequest.target_language }}</p>
                            </div>
                            <div v-if="serviceRequest.estimated_hours">
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Est. Hours</p>
                                <p class="text-gray-800 font-medium">{{ serviceRequest.estimated_hours }}h</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assignments -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#0a1f44]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Assignments
                        </h2>
                        <Link v-if="canManage" :href="route('assignments.create', { service_request_id: serviceRequest.id })"
                              class="text-xs bg-[#0a1f44] text-white px-3 py-1.5 rounded-lg hover:bg-[#0a1f44]/80 transition font-medium">
                            + Assign Staff
                        </Link>
                    </div>
                    <div v-if="serviceRequest.assignments && serviceRequest.assignments.length > 0" class="space-y-3">
                        <div v-for="assignment in serviceRequest.assignments" :key="assignment.id"
                             class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-[#0a1f44] flex items-center justify-center text-white font-bold text-sm">
                                    {{ assignment.assignee?.name?.charAt(0) }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 text-sm">{{ assignment.assignee?.name }}</p>
                                    <p class="text-xs text-gray-500 capitalize">{{ assignment.role_in_task }}</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium capitalize"
                                :class="{
                                    'bg-blue-100 text-blue-700': assignment.status === 'in_progress',
                                    'bg-green-100 text-green-700': assignment.status === 'completed',
                                    'bg-yellow-100 text-yellow-700': assignment.status === 'assigned',
                                }">
                                {{ assignment.status.replace(/_/g, ' ') }}
                            </span>
                        </div>
                    </div>
                    <p v-else class="text-gray-500 text-sm">No staff assigned yet.</p>
                </div>

                <!-- Quotations -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#0a1f44]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Quotations
                        </h2>
                        <Link v-if="canManage" :href="route('quotations.create', { service_request_id: serviceRequest.id })"
                              class="text-xs bg-[#f5c242] text-[#0a1f44] px-3 py-1.5 rounded-lg hover:bg-yellow-400 transition font-bold">
                            + Create Quotation
                        </Link>
                    </div>
                    <div v-if="serviceRequest.quotations && serviceRequest.quotations.length > 0" class="space-y-3">
                        <div v-for="quotation in serviceRequest.quotations" :key="quotation.id"
                             class="flex items-center justify-between p-4 border border-gray-200 rounded-xl hover:border-[#0a1f44]/30 transition">
                            <div>
                                <p class="font-bold text-gray-900">{{ quotation.reference_number }}</p>
                                <p class="text-sm text-gray-500">Valid until: {{ quotation.valid_until }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-[#0a1f44] text-lg">{{ quotation.currency }} {{ Number(quotation.amount).toLocaleString() }}</p>
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium capitalize"
                                    :class="{
                                        'bg-green-100 text-green-700': quotation.status === 'approved',
                                        'bg-yellow-100 text-yellow-700': quotation.status === 'pending_approval',
                                        'bg-gray-100 text-gray-600': quotation.status === 'draft',
                                        'bg-red-100 text-red-700': quotation.status === 'rejected',
                                    }">
                                    {{ quotation.status.replace(/_/g, ' ') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-gray-500 text-sm">No quotations created yet.</p>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">

                <!-- Client Info -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Client</h3>
                    <div v-if="serviceRequest.client">
                        <p class="font-bold text-gray-900">{{ serviceRequest.client.organization || serviceRequest.client.contact_person }}</p>
                        <p class="text-sm text-gray-500 mt-1">{{ serviceRequest.client.email }}</p>
                        <p class="text-sm text-gray-500">{{ serviceRequest.client.phone }}</p>
                        <p class="text-xs capitalize bg-blue-50 text-blue-700 px-2 py-1 rounded mt-2 inline-block font-medium">{{ serviceRequest.client.client_type }}</p>
                        <Link :href="route('clients.show', serviceRequest.client.id)"
                              class="block mt-3 text-xs text-[#0a1f44] hover:text-[#f5c242] font-medium transition">
                            View Client Profile →
                        </Link>
                    </div>
                </div>

                <!-- Meta Info -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Details</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Submitted by</span>
                            <span class="font-medium text-gray-900">{{ serviceRequest.submitter?.name || 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Assigned to</span>
                            <span class="font-medium text-gray-900">{{ serviceRequest.assignedTo?.name || 'Unassigned' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Created</span>
                            <span class="font-medium text-gray-900">{{ new Date(serviceRequest.created_at).toLocaleDateString() }}</span>
                        </div>
                        <div v-if="serviceRequest.completed_at" class="flex justify-between">
                            <span class="text-gray-500">Completed</span>
                            <span class="font-medium text-green-700">{{ new Date(serviceRequest.completed_at).toLocaleDateString() }}</span>
                        </div>
                    </div>
                </div>

                <!-- Status Actions -->
                <div v-if="canManage" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Actions</h3>
                    <div class="space-y-2">
                        <Link :href="route('service-requests.edit', serviceRequest.id)"
                              class="block w-full text-center bg-[#0a1f44] text-white py-2.5 px-4 rounded-xl text-sm font-semibold hover:bg-[#0a1f44]/80 transition">
                            Edit Request
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/inertia-vue3';
import { computed } from 'vue';

const props = defineProps({
    serviceRequest: Object,
});

const canManage = computed(() => {
    const perms = window._inertia?.page?.props?.auth?.permissions || [];
    return perms.includes('manage service_requests') || perms.includes('manage system');
});
</script>
