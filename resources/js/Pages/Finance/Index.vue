<template>
    <Head title="Finance Management" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 w-full">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 leading-tight">Finance Management</h2>
                    <p class="text-sm text-gray-500 mt-1">Manage bank details, verify client payments, and track institutional revenue.</p>
                </div>
            </div>
        </template>

        <div class="space-y-8">
            <!-- Bank Accounts Manager & Creator Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Creator/Editor Form -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 h-fit">
                    <h3 class="text-lg font-bold text-[#0a1f44] mb-4 border-b border-gray-100 pb-3 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#d4af37]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ isEditing ? 'Edit Bank Account' : 'Add Bank Account' }}
                    </h3>
                    
                    <form @submit.prevent="saveBankAccount" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Bank Name *</label>
                            <input v-model="bankForm.bank_name" type="text" required
                                   class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm"
                                   placeholder="e.g. CBZ Bank" />
                            <p v-if="bankForm.errors.bank_name" class="text-red-500 text-xs mt-1">{{ bankForm.errors.bank_name }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Account Name *</label>
                            <input v-model="bankForm.account_name" type="text" required
                                   class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm"
                                   placeholder="e.g. MSU Language Institute" />
                            <p v-if="bankForm.errors.account_name" class="text-red-500 text-xs mt-1">{{ bankForm.errors.account_name }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Account Number *</label>
                            <input v-model="bankForm.account_number" type="text" required
                                   class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm"
                                   placeholder="e.g. 011234567890" />
                            <p v-if="bankForm.errors.account_number" class="text-red-500 text-xs mt-1">{{ bankForm.errors.account_number }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Currency *</label>
                            <select v-model="bankForm.currency" required
                                    class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm">
                                <option value="" disabled>Select currency...</option>
                                <option value="USD">USD - US Dollar</option>
                                <option value="ZWG">ZWG - Zimbabwe Gold</option>
                                <option value="ZAR">ZAR - South African Rand</option>
                            </select>
                            <p v-if="bankForm.errors.currency" class="text-red-500 text-xs mt-1">{{ bankForm.errors.currency }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Branch Code</label>
                                <input v-model="bankForm.branch_code" type="text"
                                       class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm"
                                       placeholder="e.g. 6101" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">SWIFT Code</label>
                                <input v-model="bankForm.swift_code" type="text"
                                       class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm"
                                       placeholder="e.g. CBZUZWHA" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Custom Instructions</label>
                            <textarea v-model="bankForm.instructions" rows="2"
                                      class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm"
                                      placeholder="Payment advice or references details..."></textarea>
                        </div>

                        <div class="flex items-center">
                            <input id="bank_is_active" v-model="bankForm.is_active" type="checkbox" class="rounded text-[#0a1f44] focus:ring-[#0a1f44]">
                            <label for="bank_is_active" class="ml-2 text-sm text-gray-700 font-semibold">Mark Account Active</label>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-3">
                            <button v-if="isEditing" type="button" @click="resetBankForm"
                                    class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                                Cancel
                            </button>
                            <button type="submit" :disabled="bankForm.processing"
                                    class="px-5 py-2 bg-[#0a1f44] hover:bg-[#0a1f44]/90 text-white rounded-xl text-xs font-bold transition shadow-sm">
                                {{ isEditing ? 'Update Details' : 'Save Details' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Accounts Grid List -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-[#0a1f44] mb-4 border-b border-gray-100 pb-3 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#d4af37]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            Active Banking Details
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-for="bank in bankAccounts" :key="bank.id" 
                                 class="p-4 border rounded-2xl shadow-sm transition hover:shadow-md flex flex-col justify-between"
                                 :class="bank.is_active ? 'border-gray-200' : 'border-red-200 bg-red-50/20'">
                                <div>
                                    <div class="flex justify-between items-start">
                                        <h4 class="font-bold text-[#0a1f44]">{{ bank.bank_name }}</h4>
                                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-full tracking-wide"
                                              :class="bank.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                            {{ bank.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    <div class="mt-2 text-xs space-y-1">
                                        <p class="text-gray-500"><strong class="text-gray-700">Account:</strong> {{ bank.account_name }}</p>
                                        <p class="text-gray-500"><strong class="text-gray-700">Number:</strong> {{ bank.account_number }}</p>
                                        <p class="text-gray-500"><strong class="text-gray-700">Currency:</strong> <span class="px-1.5 py-0.5 bg-gray-150 text-[#0a1f44] font-bold rounded text-[10px]">{{ bank.currency || 'USD' }}</span></p>
                                        <p v-if="bank.branch_code" class="text-gray-500"><strong class="text-gray-700">Branch:</strong> {{ bank.branch_code }}</p>
                                        <p v-if="bank.swift_code" class="text-gray-500"><strong class="text-gray-700">SWIFT:</strong> {{ bank.swift_code }}</p>
                                    </div>
                                </div>

                                <div class="mt-4 border-t border-gray-100 pt-3 flex justify-end gap-2 text-xs">
                                    <button @click="editBankAccount(bank)" 
                                            class="text-[#d4af37] hover:text-[#0a1f44] font-bold transition">
                                        Edit
                                    </button>
                                    <button @click="toggleBankActive(bank)" 
                                            class="font-bold transition"
                                            :class="bank.is_active ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800'">
                                        {{ bank.is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </div>
                            </div>
                            
                            <div v-if="bankAccounts.length === 0" 
                                 class="col-span-full border-2 border-dashed border-gray-200 rounded-2xl py-12 text-center text-gray-500">
                                No bank accounts configured. Use the form on the left to add active accounts.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Client Proofs Verification Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="text-lg font-bold text-[#0a1f44]">Verify Client Transactions</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Audit transaction proofs and update verification statuses.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-4 font-semibold">Submit Date</th>
                                <th class="px-6 py-4 font-semibold">Client Name</th>
                                <th class="px-6 py-4 font-semibold">Quotation Ref</th>
                                <th class="px-6 py-4 font-semibold">Service</th>
                                <th class="px-6 py-4 font-semibold">Bank Selected</th>
                                <th class="px-6 py-4 font-semibold">Amount Submitted</th>
                                <th class="px-6 py-4 font-semibold">Proof Attachment</th>
                                <th class="px-6 py-4 font-semibold">Status</th>
                                <th class="px-6 py-4 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="p in payments.data" :key="p.id" class="hover:bg-gray-50 transition text-sm">
                                <td class="px-6 py-4 text-gray-600">
                                    {{ new Date(p.created_at).toLocaleDateString() }}
                                </td>
                                <td class="px-6 py-4 font-semibold text-[#0a1f44]">
                                    {{ p.client.name }}
                                </td>
                                <td class="px-6 py-4 font-mono font-medium text-gray-600">
                                    {{ p.quotation.reference_number }}
                                </td>
                                <td class="px-6 py-4 text-gray-700 max-w-xs truncate">
                                    {{ p.quotation.service_request.title }}
                                </td>
                                <td class="px-6 py-4 text-gray-600">
                                    {{ p.bank_used }}
                                </td>
                                <td class="px-6 py-4 font-bold text-gray-900">
                                     {{ p.quotation?.currency || 'USD' }} {{ Number(p.amount_paid).toLocaleString(undefined, {minimumFractionDigits: 2}) }}
                                </td>
                                <td class="px-6 py-4">
                                    <a :href="route('payments.proof', p.id)" target="_blank" 
                                       class="text-[#d4af37] hover:text-[#0a1f44] font-semibold inline-flex items-center gap-1 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        View Proof
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-full uppercase tracking-wide w-fit"
                                              :class="{
                                                  'bg-yellow-100 text-yellow-800': p.status === 'pending',
                                                  'bg-green-100 text-green-800': p.status === 'verified',
                                                  'bg-red-100 text-red-800': p.status === 'rejected'
                                              }">
                                            {{ p.status }}
                                        </span>
                                        <span v-if="p.status === 'rejected' && p.notes" class="text-xs text-red-600 italic">
                                            Reason: {{ p.notes }}
                                        </span>
                                        <span v-if="p.status === 'verified' && p.verified_by" class="text-[10px] text-gray-500">
                                            By: {{ p.verified_by.name }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div v-if="p.status === 'pending'" class="flex gap-2 justify-end">
                                        <button @click="verifyPayment(p.id, 'approve')" 
                                                class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-bold transition shadow-sm">
                                            Verify
                                        </button>
                                        <button @click="openRejectionPrompt(p.id)" 
                                                class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-bold transition shadow-sm">
                                            Reject
                                        </button>
                                    </div>
                                    <span v-else class="text-xs text-gray-400 font-semibold">Reviewed</span>
                                </td>
                            </tr>
                            <tr v-if="payments.data.length === 0">
                                <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                    No client proofs of payment have been submitted yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="payments.links && payments.links.length > 3" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <div class="text-xs text-gray-500">
                        Showing {{ payments.from }} to {{ payments.to }} of {{ payments.total }} transaction submissions
                    </div>
                    <div class="flex gap-1.5">
                        <template v-for="(link, key) in payments.links" :key="key">
                            <div v-if="link.url === null" class="px-3 py-1.5 border border-gray-200 rounded-lg text-xs text-gray-400 cursor-not-allowed" v-html="link.label"></div>
                            <Link v-else :href="link.url" 
                                  class="px-3 py-1.5 border rounded-lg text-xs font-semibold transition"
                                  :class="link.active ? 'bg-[#0a1f44] border-[#0a1f44] text-white' : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
                                  v-html="link.label"></Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rejection Comment Modal -->
        <div v-if="showRejectionModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-gray-100">
                <h4 class="text-[#0a1f44] font-bold text-lg mb-2">Reject Proof of Payment</h4>
                <p class="text-xs text-gray-500 mb-4">Please provide a descriptive reason detailing why the proof was rejected. This is shared with the client portal.</p>
                
                <textarea v-model="rejectionReason" rows="3" required
                          class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm mb-4"
                          placeholder="e.g. Unreadable receipt image, amount on receipt does not match quotation..."></textarea>
                
                <div class="flex justify-end gap-2.5">
                    <button @click="closeRejectionModal" 
                            class="px-4 py-2 border border-gray-300 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button @click="submitRejection" :disabled="!rejectionReason.trim()"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl text-xs transition shadow-sm disabled:opacity-50">
                        Confirm Rejection
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/inertia-vue3';
import { ref } from 'vue';

const props = defineProps({
    bankAccounts: Array,
    payments: Object,
    totalRevenue: Number,
    revenueByCurrency: Object,
});

const isEditing = ref(false);
const editingBankId = ref(null);

const bankForm = useForm({
    bank_name: '',
    account_name: '',
    account_number: '',
    currency: 'USD',
    branch_code: '',
    swift_code: '',
    instructions: '',
    is_active: true,
});

const paymentForm = useForm({
    action: '',
    notes: '',
});

// Bank CRUD actions
const editBankAccount = (bank) => {
    isEditing.value = true;
    editingBankId.value = bank.id;
    bankForm.bank_name = bank.bank_name;
    bankForm.account_name = bank.account_name;
    bankForm.account_number = bank.account_number;
    bankForm.currency = bank.currency || 'USD';
    bankForm.branch_code = bank.branch_code || '';
    bankForm.swift_code = bank.swift_code || '';
    bankForm.instructions = bank.instructions || '';
    bankForm.is_active = bank.is_active;
};

const resetBankForm = () => {
    isEditing.value = false;
    editingBankId.value = null;
    bankForm.reset();
    bankForm.currency = 'USD';
};

const saveBankAccount = () => {
    if (isEditing.value) {
        bankForm.put(route('finance.bank-accounts.update', editingBankId.value), {
            onSuccess: () => resetBankForm(),
        });
    } else {
        bankForm.post(route('finance.bank-accounts.store'), {
            onSuccess: () => resetBankForm(),
        });
    }
};

const toggleBankActive = (bank) => {
    bankForm.patch(route('finance.bank-accounts.toggle', bank.id));
};

// Payment Audit actions
const verifyPayment = (paymentId, action) => {
    paymentForm.action = action;
    paymentForm.notes = '';
    paymentForm.post(route('finance.payments.verify', paymentId));
};

// Rejection Prompt modal logic
const showRejectionModal = ref(false);
const rejectingPaymentId = ref(null);
const rejectionReason = ref('');

const openRejectionPrompt = (paymentId) => {
    rejectingPaymentId.value = paymentId;
    rejectionReason.value = '';
    showRejectionModal.value = true;
};

const closeRejectionModal = () => {
    showRejectionModal.value = false;
    rejectingPaymentId.value = null;
    rejectionReason.value = '';
};

const submitRejection = () => {
    if (!rejectionReason.value.trim()) return;
    
    paymentForm.action = 'reject';
    paymentForm.notes = rejectionReason.value;
    paymentForm.post(route('finance.payments.verify', rejectingPaymentId.value), {
        onSuccess: () => closeRejectionModal(),
    });
};
</script>
