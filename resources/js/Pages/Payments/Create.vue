<template>
    <Head title="Submit Proof of Payment" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('payments.index')" class="text-gray-400 hover:text-[#0a1f44] transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-900 font-bold">Upload Payment Proof</span>
            </div>
        </template>

        <div class="max-w-4xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Form Card -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-[#0a1f44] mb-4 border-b border-gray-100 pb-3">Submit Payment Proof</h3>
                
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Approved Quotation -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Select Approved Quotation <span class="text-red-500">*</span></label>
                        <select v-model="form.quotation_id" @change="onQuotationChange" required
                                class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm">
                            <option :value="null" disabled>Choose an approved quotation...</option>
                            <option v-for="q in quotations" :key="q.id" :value="q.id">
                                {{ q.reference_number }} — {{ q.service_request.title }} ({{ q.currency }} {{ Number(q.amount).toLocaleString(undefined, {minimumFractionDigits: 2}) }})
                            </option>
                        </select>
                        <p v-if="form.errors.quotation_id" class="text-red-500 text-xs mt-1">{{ form.errors.quotation_id }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Amount Paid -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Amount Paid ({{ activeCurrency }}) <span class="text-red-500">*</span></label>
                            <div class="relative rounded-xl shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm font-semibold">{{ activeCurrency }}</span>
                                </div>
                                <input v-model="form.amount_paid" type="number" step="0.01" min="0.01" required
                                       class="pl-12 w-full border-gray-300 rounded-xl focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm"
                                       placeholder="0.00" />
                            </div>
                            <p v-if="form.errors.amount_paid" class="text-red-500 text-xs mt-1">{{ form.errors.amount_paid }}</p>
                        </div>

                        <!-- Bank Used -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">MSU Bank Deposited Into <span class="text-red-500">*</span></label>
                            <select v-model="form.bank_used" required
                                    class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm">
                                <option value="" disabled>Select target bank...</option>
                                <option v-for="bank in bankAccounts" :key="bank.id" :value="bank.bank_name">
                                    {{ bank.bank_name }} ({{ bank.account_number }}) - {{ bank.currency || 'USD' }}
                                </option>
                            </select>
                            <p v-if="form.errors.bank_used" class="text-red-500 text-xs mt-1">{{ form.errors.bank_used }}</p>
                        </div>
                    </div>

                    <!-- Proof File Uploader -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Proof of Payment File *</label>
                        
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 rounded-2xl transition-all duration-200 bg-gray-50/50"
                             :class="isDragging ? 'border-[#0a1f44] bg-[#0a1f44]/5 scale-[1.01]' : 'border-gray-300 border-dashed hover:border-[#0a1f44]/50'"
                             @dragover.prevent="onDragOver"
                             @dragleave.prevent="onDragLeave"
                             @drop.prevent="onDrop">
                            <div class="space-y-2 text-center">
                                <svg class="mx-auto h-12 w-12 transition-transform duration-200" :class="isDragging ? 'text-[#0a1f44] scale-110' : 'text-gray-400'" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                    <path d="M28 8H12a4 4 0 00-4 4v20a4 4 0 004 4h16m12-12v-8a4 4 0 00-4-4h-8m-12 8a4 4 0 004 4h4m12 0l-4-4m4 4l-4 4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600 justify-center">
                                    <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-semibold text-[#d4af37] hover:text-[#0a1f44] focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-[#0a1f44]">
                                        <span>Upload a file</span>
                                        <input id="file-upload" type="file" @change="onFileChange" class="sr-only" accept=".pdf,.png,.jpg,.jpeg" />
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">PDF, PNG, JPG, JPEG up to 10MB</p>
                                <div v-if="selectedFileName" class="mt-2 text-sm font-semibold text-[#0a1f44] bg-[#f5c242]/10 border border-[#f5c242]/20 px-3 py-1 rounded-lg w-fit mx-auto flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-[#d4af37]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A1 1 0 0112 2.586L15.414 6A1 1 0 0116 6.586V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/></svg>
                                    {{ selectedFileName }}
                                </div>
                            </div>
                        </div>
                        <p v-if="form.errors.file" class="text-red-500 text-xs mt-1">{{ form.errors.file }}</p>
                    </div>

                    <!-- Client Notes -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Additional Notes (Optional)</label>
                        <textarea v-model="form.notes" rows="3" 
                                  class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm"
                                  placeholder="Provide any transaction reference numbers or information to help us speed up verification..."></textarea>
                        <p v-if="form.errors.notes" class="text-red-500 text-xs mt-1">{{ form.errors.notes }}</p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <Link :href="route('payments.index')"
                              class="px-5 py-2.5 border border-gray-300 rounded-xl text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </Link>
                        <button type="submit" :disabled="form.processing"
                                class="px-6 py-2.5 bg-[#0a1f44] hover:bg-[#0a1f44]/90 text-white rounded-xl text-sm font-bold transition disabled:opacity-50 shadow-sm flex items-center gap-2">
                            <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Submit Proof
                        </button>
                    </div>
                </form>
            </div>

            <!-- Details Sidebar -->
            <div class="space-y-6">
                <!-- Helper tips -->
                <div class="bg-gradient-to-br from-[#0a1f44] to-[#143163] text-white rounded-2xl p-6 shadow-sm border border-[#f5c242]/20">
                    <h4 class="text-[#f5c242] font-bold text-base flex items-center gap-1.5 mb-3">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                        Verification Guide
                    </h4>
                    <ul class="space-y-2 text-sm text-white/80 list-disc list-inside">
                        <li>Ensure the uploaded document shows a successful status, amount, and date.</li>
                        <li>Verify that the amount typed matches the proof document exactly.</li>
                        <li>Payments are reviewed during operational hours by our finance division.</li>
                        <li>Your request will proceed to dispatch immediately once payment is verified.</li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/inertia-vue3';
import { ref, onMounted } from 'vue';

const props = defineProps({
    quotations: Array,
    selectedQuotationId: Number,
    bankAccounts: Array,
});

const selectedFileName = ref('');
const isDragging = ref(false);
const activeCurrency = ref('USD');

const form = useForm({
    quotation_id: null,
    amount_paid: '',
    bank_used: '',
    file: null,
    notes: '',
});

const onDragOver = () => {
    isDragging.value = true;
};

const onDragLeave = () => {
    isDragging.value = false;
};

const onDrop = (e) => {
    isDragging.value = false;
    const files = e.dataTransfer.files;
    if (files && files.length > 0) {
        const file = files[0];
        const allowedTypes = ['application/pdf', 'image/png', 'image/jpeg', 'image/jpg'];
        const allowedExtensions = ['.pdf', '.png', '.jpg', '.jpeg'];
        const extension = file.name.substring(file.name.lastIndexOf('.')).toLowerCase();
        
        if (allowedTypes.includes(file.type) || allowedExtensions.includes(extension)) {
            form.file = file;
            selectedFileName.value = file.name;
        } else {
            alert('Invalid file format. Please upload a PDF or an Image (PNG/JPG).');
        }
    }
};

onMounted(() => {
    if (props.selectedQuotationId) {
        form.quotation_id = props.selectedQuotationId;
        onQuotationChange();
    }
});

const onQuotationChange = () => {
    const q = props.quotations.find(item => item.id === form.quotation_id);
    if (q) {
        form.amount_paid = q.amount;
        activeCurrency.value = q.currency || 'USD';
    } else {
        activeCurrency.value = 'USD';
    }
};

const onFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.file = file;
        selectedFileName.value = file.name;
    }
};

const submit = () => {
    form.post(route('payments.store'));
};
</script>
