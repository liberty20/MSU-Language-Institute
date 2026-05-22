<template>
    <Head :title="`Quotation ${quotation.reference_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('quotations.index')" class="text-gray-400 hover:text-[#0a1f44] transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </Link>
                    <span class="text-gray-400">/</span>
                    <span>{{ quotation.reference_number }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide"
                        :class="mappedStatus.class">
                        {{ mappedStatus.label }}
                    </span>
                    <button @click="printQuotation"
                            class="flex items-center gap-1.5 text-xs border border-gray-300 text-gray-600 px-3 py-1.5 rounded-lg hover:bg-gray-50 transition font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print
                    </button>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Quotation Document -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Header Block -->
                <div id="quotation-print" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <!-- MSU Header -->
                    <div class="bg-[#0a1f44] text-white p-8">
                        <div class="flex items-start justify-between">
                            <div>
                                <h1 class="text-2xl font-black tracking-tight">MSUNLI</h1>
                                <p class="text-[#f5c242] font-semibold text-sm">Midlands State University National Language Institute</p>
                                <p class="text-white/60 text-xs mt-2">Senga Road, Gweru, Zimbabwe</p>
                            </div>
                            <div class="text-right">
                                <p class="text-3xl font-black text-[#f5c242] tracking-widest">QUOTATION</p>
                                <p class="text-white/80 text-sm mt-1">{{ quotation.reference_number }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Bill To / Details Row -->
                    <div class="p-8 grid grid-cols-2 gap-8 border-b border-gray-100">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">Bill To</p>
                            <p class="font-bold text-gray-900 text-lg">{{ quotation.service_request?.client?.organization || quotation.service_request?.client?.contact_person }}</p>
                            <p class="text-sm text-gray-600">{{ quotation.service_request?.client?.email }}</p>
                            <p class="text-sm text-gray-600">{{ quotation.service_request?.client?.phone }}</p>
                            <p class="text-sm text-gray-600 mt-1">{{ quotation.service_request?.client?.address }}</p>
                        </div>
                        <div class="text-right space-y-2">
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wider">Date Issued</p>
                                <p class="font-medium text-gray-900">{{ new Date(quotation.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'long', year: 'numeric' }) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wider">Valid Until</p>
                                <p class="font-medium text-gray-900">{{ new Date(quotation.valid_until).toLocaleDateString('en-GB', { day: '2-digit', month: 'long', year: 'numeric' }) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wider">Service Request</p>
                                <p class="font-medium text-[#0a1f44]">{{ quotation.service_request?.reference_number }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wider">Prepared By</p>
                                <p class="font-medium text-gray-900">{{ quotation.preparedBy?.name }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Service Description -->
                    <div v-if="quotation.description" class="px-8 pt-6 pb-2">
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">Service Description</p>
                        <p class="text-gray-700 text-sm whitespace-pre-wrap leading-relaxed bg-gray-50 p-4 rounded-xl">{{ quotation.description }}</p>
                    </div>

                    <!-- Line Items Table -->
                    <div class="px-8 py-6">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-[#0a1f44]/5 text-[#0a1f44] text-xs uppercase tracking-wider">
                                    <th class="py-3 px-4 text-left font-bold rounded-l-lg">Description</th>
                                    <th class="py-3 px-4 text-right font-bold">Qty</th>
                                    <th class="py-3 px-4 text-right font-bold">Unit Price</th>
                                    <th class="py-3 px-4 text-right font-bold rounded-r-lg">Amount</th>
                                </tr>
                            </thead>
                            <tbody v-if="parsedLineItems.length > 0" class="divide-y divide-gray-100">
                                <tr v-for="(item, i) in parsedLineItems" :key="i" class="hover:bg-gray-50">
                                    <td class="py-3 px-4 text-gray-800">{{ item.description }}</td>
                                    <td class="py-3 px-4 text-right text-gray-600">{{ item.qty }}</td>
                                    <td class="py-3 px-4 text-right text-gray-600">{{ quotation.currency }} {{ Number(item.unit_price).toFixed(2) }}</td>
                                    <td class="py-3 px-4 text-right font-medium text-gray-900">{{ quotation.currency }} {{ (item.qty * item.unit_price).toFixed(2) }}</td>
                                </tr>
                            </tbody>
                            <tbody v-else>
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-gray-500 text-sm">Service fee as quoted below</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-[#0a1f44]">
                                    <td colspan="3" class="pt-4 px-4 text-right font-black text-[#0a1f44] uppercase tracking-wide">TOTAL DUE</td>
                                    <td class="pt-4 px-4 text-right font-black text-[#0a1f44] text-xl">
                                        {{ quotation.currency }} {{ Number(quotation.amount).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Footer Notes -->
                    <div v-if="quotation.notes" class="px-8 pb-8">
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">Terms &amp; Notes</p>
                        <p class="text-xs text-gray-500 whitespace-pre-wrap leading-relaxed">{{ quotation.notes }}</p>
                    </div>
                </div>

                <!-- Approvals -->
                <div v-if="quotation.approvals && quotation.approvals.length > 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-base font-bold text-gray-900 mb-4">Approval Trail</h2>
                    <div class="space-y-3">
                        <div v-for="approval in quotation.approvals" :key="approval.id"
                             class="flex items-start gap-3 p-3 rounded-xl border border-gray-100">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                                 :class="{
                                     'bg-green-100 text-green-700': approval.status === 'approved',
                                     'bg-red-100 text-red-700': approval.status === 'rejected',
                                     'bg-yellow-100 text-yellow-700': approval.status === 'pending',
                                 }">
                                {{ approval.stage }}
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between">
                                    <p class="text-sm font-semibold text-gray-900">{{ approval.approver?.name }}</p>
                                    <span class="text-xs capitalize font-medium px-2 py-0.5 rounded"
                                          :class="{
                                              'text-green-700 bg-green-50': approval.status === 'approved',
                                              'text-red-700 bg-red-50': approval.status === 'rejected',
                                              'text-yellow-700 bg-yellow-50': approval.status === 'pending',
                                          }">{{ approval.status }}</span>
                                </div>
                                <p v-if="approval.comments" class="text-xs text-gray-500 mt-1">{{ approval.comments }}</p>
                                <p v-if="approval.approved_at" class="text-xs text-gray-400 mt-1">{{ new Date(approval.approved_at).toLocaleString() }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Creator Actions -->
                <div v-if="quotation.status === 'draft' && quotation.prepared_by === $page.props.auth.user.id" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-sm font-bold text-[#0a1f44] uppercase tracking-wider mb-4">Creator Actions</h3>
                    <div class="space-y-2">
                        <Link :href="route('quotations.edit', quotation.id)"
                              class="w-full bg-[#f5c242] text-[#0a1f44] py-2.5 px-4 rounded-xl text-sm font-bold hover:bg-yellow-400 transition flex items-center justify-center gap-2 shadow-sm shadow-yellow-100">
                            ✎ Edit Quotation
                        </Link>
                    </div>
                </div>

                <!-- Deputy Director Recommendation -->
                <div v-if="quotation.status === 'submitted' && $page.props.auth.roles.includes('deputy_director')" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-sm font-bold text-[#0a1f44] uppercase tracking-wider mb-4">Deputy Director Recommendation</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Comments / Recommendations</label>
                            <textarea v-model="approvalComment" rows="3"
                                      class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm resize-none"
                                      placeholder="Optional remarks…"></textarea>
                        </div>
                        <button @click="submitApproval('approved')"
                                class="w-full bg-green-600 text-white py-2.5 px-4 rounded-xl text-sm font-bold hover:bg-green-700 transition flex items-center justify-center gap-2 shadow-sm shadow-green-100">
                            ✓ Recommend &amp; Push to Director
                        </button>
                        <button @click="submitApproval('review')"
                                class="w-full bg-amber-600 text-white py-2.5 px-4 rounded-xl text-sm font-bold hover:bg-amber-700 transition flex items-center justify-center gap-2 shadow-sm shadow-amber-100">
                            ⟲ Return for Revision
                        </button>
                    </div>
                </div>

                <!-- Executive Director Final Approval -->
                <div v-if="(quotation.status === 'pending_approval' || quotation.status === 'submitted') && ($page.props.auth.roles.includes('executive_director') || $page.props.auth.roles.includes('ict_administrator'))" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-sm font-bold text-[#0a1f44] uppercase tracking-wider mb-4">Executive Director Final Approval</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Approval Comments</label>
                            <textarea v-model="approvalComment" rows="3"
                                      class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm resize-none"
                                      placeholder="Optional remarks…"></textarea>
                        </div>
                        <button @click="submitApproval('approved')"
                                class="w-full bg-green-600 text-white py-2.5 px-4 rounded-xl text-sm font-bold hover:bg-green-700 transition flex items-center justify-center gap-2 shadow-sm shadow-green-100">
                            ✓ Approve Quotation
                        </button>
                        <button @click="submitApproval('rejected')"
                                class="w-full bg-red-600 text-white py-2.5 px-4 rounded-xl text-sm font-bold hover:bg-red-700 transition flex items-center justify-center gap-2 shadow-sm shadow-red-100">
                            ✗ Reject Quotation
                        </button>
                    </div>
                </div>

                <!-- Summary Card -->
                <div class="bg-[#0a1f44] rounded-2xl p-6 text-white">
                    <p class="text-white/60 text-xs uppercase tracking-wider mb-1">Total Amount</p>
                    <p class="text-3xl font-black text-[#f5c242]">{{ quotation.currency }} {{ Number(quotation.amount).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</p>
                    <p class="text-white/60 text-xs mt-3">Valid until</p>
                    <p class="text-white font-semibold">{{ new Date(quotation.valid_until).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) }}</p>
                </div>

                <!-- Service Request Link -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-3">Linked Request</h3>
                    <p class="font-medium text-[#0a1f44]">{{ quotation.service_request?.reference_number }}</p>
                    <p class="text-sm text-gray-600 mt-1">{{ quotation.service_request?.title }}</p>
                    <Link v-if="quotation.service_request" :href="route('service-requests.show', quotation.service_request.id)"
                          class="text-xs text-[#0a1f44] hover:text-[#f5c242] font-medium mt-3 inline-block transition">
                        View Request →
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/inertia-vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    quotation: Object,
});

const approvalComment = ref('');

const mappedStatus = computed(() => {
    if (props.quotation.status === 'draft') {
        return props.quotation.approvals && props.quotation.approvals.length > 0
            ? { label: 'Needs Revision', class: 'bg-orange-100 text-orange-800' }
            : { label: 'Draft', class: 'bg-gray-100 text-gray-700' };
    }
    if (props.quotation.status === 'submitted') {
        return { label: 'Pending Recommendation', class: 'bg-blue-100 text-blue-800' };
    }
    if (props.quotation.status === 'pending_approval') {
        return { label: 'Recommended', class: 'bg-yellow-100 text-yellow-800' };
    }
    if (props.quotation.status === 'approved') {
        return { label: 'Approved', class: 'bg-green-100 text-green-800' };
    }
    if (props.quotation.status === 'rejected') {
        return { label: 'Rejected', class: 'bg-red-100 text-red-800' };
    }
    return { label: props.quotation.status.replace(/_/g, ' '), class: 'bg-gray-100 text-gray-700' };
});

const parsedLineItems = computed(() => {
    if (!props.quotation.line_items) return [];
    try {
        const items = typeof props.quotation.line_items === 'string'
            ? JSON.parse(props.quotation.line_items)
            : props.quotation.line_items;
        return Array.isArray(items) ? items : [];
    } catch {
        return [];
    }
});

const approvalForm = useForm({
    status: '',
    comments: '',
});

const submitApproval = (decision) => {
    approvalForm.status = decision;
    approvalForm.comments = approvalComment.value;
    approvalForm.post(route('quotations.approve', props.quotation.id));
};

const printQuotation = () => {
    window.print();
};
</script>
