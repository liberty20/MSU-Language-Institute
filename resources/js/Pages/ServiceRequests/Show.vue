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
                                    {{ (assignment.assigned_to?.name || assignment.assignee?.name || 'U').charAt(0) }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 text-sm">{{ assignment.assigned_to?.name || assignment.assignee?.name }}</p>
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

                <!-- Attachments Board -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6">
                    <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2 border-b border-gray-50 pb-3">
                        <svg class="w-5 h-5 text-[#0a1f44]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                        </svg>
                        Request Attachments & Source Files
                    </h2>

                    <!-- Client Upload Form -->
                    <form v-if="$page.props.auth.roles.includes('client') && serviceRequest.status !== 'completed'" @submit.prevent="submitAttachment" class="space-y-4 bg-gray-50/50 p-4 rounded-xl border border-gray-150">
                        <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Add New Attachment</div>
                        <div class="flex flex-col sm:flex-row gap-3 items-end">
                            <div class="flex-1 w-full space-y-1">
                                <input type="file" @change="handleAttachFileChange" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-[#0a1f44] hover:file:bg-blue-100 cursor-pointer" />
                                <input v-model="attachForm.description" placeholder="Brief description (e.g., source document PDF)" class="w-full text-xs border-gray-300 rounded-lg focus:border-brand-blue focus:ring-brand-blue" />
                            </div>
                            <button type="submit" :disabled="attachForm.processing || !attachForm.file" class="bg-[#0a1f44] hover:bg-[#152a4d] disabled:opacity-50 text-white text-xs font-bold px-4 py-2.5 rounded-lg transition shadow-sm shrink-0">
                                Upload
                            </button>
                        </div>
                        <div v-if="attachForm.errors.file" class="text-red-500 text-xs font-semibold mt-1">{{ attachForm.errors.file }}</div>
                    </form>

                    <!-- List of Request Files -->
                    <div class="space-y-4">
                        <!-- Source documents -->
                        <div>
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Source Documents</h3>
                            <div v-if="!serviceRequest.documents?.length" class="text-xs text-gray-500 italic">No source files uploaded.</div>
                            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div v-for="doc in serviceRequest.documents" :key="doc.id" class="flex items-center justify-between p-3 rounded-xl border border-gray-150 bg-white shadow-sm">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-[#0a1f44] flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold text-gray-900 truncate" :title="doc.filename">{{ doc.filename }}</p>
                                            <p class="text-[10px] text-gray-400 font-medium truncate">{{ doc.description || 'Client upload' }} • By {{ doc.uploader?.name || 'Client' }}</p>
                                        </div>
                                    </div>
                                    <a :href="route('documents.download', doc.id)" class="p-1.5 hover:bg-gray-100 rounded text-gray-500 transition" target="_blank"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg></a>
                                </div>
                            </div>
                        </div>

                        <!-- Staff deliverables -->
                        <div class="pt-4 border-t border-gray-100">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Completed Deliverables</h3>
                            <div v-if="!staffDeliverables.length" class="text-xs text-gray-500 italic">No final deliverables submitted yet.</div>
                            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div v-for="doc in staffDeliverables" :key="doc.id" class="flex items-center justify-between p-3 rounded-xl border border-green-150 bg-green-50/10 shadow-sm">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-8 h-8 rounded-lg bg-green-100 text-green-700 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold text-gray-900 truncate" :title="doc.filename">{{ doc.filename }}</p>
                                            <p class="text-[10px] text-gray-500 font-medium truncate">{{ doc.description || 'Completed Deliverable' }} • By {{ doc.uploader?.name || 'Staff' }}</p>
                                        </div>
                                    </div>
                                    <a :href="route('documents.download', doc.id)" class="p-1.5 hover:bg-green-100 rounded text-green-700 transition" target="_blank"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quotations -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#0a1f44]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Quotations
                        </h2>
                        <Link v-if="canCreateQuotation" :href="route('quotations.create', { service_request_id: serviceRequest.id })"
                              class="text-xs bg-[#f5c242] text-[#0a1f44] px-3 py-1.5 rounded-lg hover:bg-yellow-400 transition font-bold">
                            + Create Quotation
                        </Link>
                    </div>

                    <div v-if="serviceRequest.quotations && serviceRequest.quotations.length > 0" class="space-y-3">
                        <div v-for="quotation in serviceRequest.quotations" :key="quotation.id"
                             class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-[#0a1f44] flex items-center justify-center text-white font-bold text-sm">
                                    Q
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 text-sm">Quotation #{{ quotation.reference_number || quotation.id }}</p>
                                    <p class="text-xs text-gray-500">Amount: {{ quotation.currency }} {{ Number(quotation.amount).toFixed(2) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium capitalize"
                                    :class="getQuotationStatus(quotation).class">
                                    {{ getQuotationStatus(quotation).label }}
                                </span>
                                <Link :href="route('quotations.show', quotation.id)" class="text-xs text-[#0a1f44] hover:text-[#f5c242] font-semibold transition">
                                    View Details →
                                </Link>
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
                            <span class="font-medium text-gray-900">{{ serviceRequest.submitted_by?.name || serviceRequest.submitter?.name || 'N/A' }}</span>
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

                <!-- Director Delivery Card -->
                <div v-if="serviceRequest.status === 'review' && ['executive_director', 'deputy_director'].some(r => $page.props.auth.roles.includes(r))" class="bg-gradient-to-br from-amber-50 to-yellow-100/50 border border-yellow-200 rounded-2xl p-6 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-amber-800 uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        Deliver Completed Task
                    </h3>
                    <p class="text-xs text-gray-700 leading-relaxed">
                        The assigned staff members have uploaded the final deliverables and completed their task. Review the files on the left and click below to send them to the client.
                    </p>

                    <!-- Lock Warning if payment is not verified yet -->
                    <div v-if="!hasVerifiedPayment" class="p-3 bg-amber-50 border border-amber-250 rounded-xl flex items-start gap-2.5">
                        <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div class="text-xs">
                            <p class="font-bold text-amber-850">Delivery Locked</p>
                            <p class="text-amber-750 mt-0.5">Client has not uploaded proof of payment or the transaction is pending verification in the Finance module.</p>
                        </div>
                    </div>

                    <form @submit.prevent="deliverCompletedTask" class="space-y-3">
                        <textarea v-model="deliveryForm.notes" placeholder="Optional delivery notes for the client..." rows="2" class="w-full text-xs border-gray-300 rounded-lg focus:border-brand-blue focus:ring-brand-blue" :disabled="!hasVerifiedPayment"></textarea>
                        <button type="submit" :disabled="deliveryForm.processing || !hasVerifiedPayment" class="w-full bg-[#0a1f44] text-white hover:bg-[#152a4d] disabled:opacity-50 px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm flex items-center justify-center gap-2">
                            <span v-if="deliveryForm.processing" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            <span>Approve & Send to Client</span>
                        </button>
                    </form>
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
import { Head, Link, useForm, usePage } from '@inertiajs/inertia-vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    serviceRequest: Object,
});

const page = usePage();

const attachForm = useForm({
    file: null,
    description: '',
});

const deliveryForm = useForm({
    notes: '',
});

const handleAttachFileChange = (e) => {
    attachForm.file = e.target.files[0];
};

const submitAttachment = () => {
    attachForm.post(route('service-requests.attach', props.serviceRequest.id), {
        onSuccess: () => {
            attachForm.reset();
        }
    });
};

const deliverCompletedTask = () => {
    deliveryForm.post(route('service-requests.deliver', props.serviceRequest.id), {
        onSuccess: () => {
            deliveryForm.reset();
        }
    });
};

const staffDeliverables = computed(() => {
    if (!props.serviceRequest.assignments) return [];
    return props.serviceRequest.assignments.flatMap(a => a.documents || []);
});

const hasVerifiedPayment = computed(() => {
    if (!props.serviceRequest.payments) return false;
    return props.serviceRequest.payments.some(p => p.status === 'verified');
});

const canManage = computed(() => {
    const perms = page.props.value.auth.permissions || [];
    return perms.includes('manage service_requests') || perms.includes('manage system');
});

const canCreateQuotation = computed(() => {
    const roles = page.props.value.auth.roles || [];
    const perms = page.props.value.auth.permissions || [];
    return (perms.includes('manage quotations') || perms.includes('manage system')) &&
           !roles.includes('executive_director') &&
           !roles.includes('deputy_director');
});

const getQuotationStatus = (quotation) => {
    if (quotation.status === 'draft') {
        return quotation.approvals && quotation.approvals.length > 0
            ? { label: 'Needs Revision', class: 'bg-orange-100 text-orange-800' }
            : { label: 'Draft', class: 'bg-gray-100 text-gray-700' };
    }
    if (quotation.status === 'submitted') {
        return { label: 'Pending Recommendation', class: 'bg-blue-100 text-blue-800' };
    }
    if (quotation.status === 'pending_approval') {
        return { label: 'Recommended', class: 'bg-yellow-100 text-yellow-800' };
    }
    if (quotation.status === 'approved') {
        return { label: 'Approved', class: 'bg-green-100 text-green-800' };
    }
    if (quotation.status === 'rejected') {
        return { label: 'Rejected', class: 'bg-red-100 text-red-800' };
    }
    return { label: quotation.status.replace(/_/g, ' '), class: 'bg-gray-100 text-gray-700' };
};
</script>
