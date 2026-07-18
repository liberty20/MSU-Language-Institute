<template>
    <Head title="Create Quotation" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('quotations.index')" class="text-gray-400 hover:text-[#0a1f44] transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <span class="text-gray-400">/</span>
                <span>New Quotation</span>
            </div>
        </template>

        <form @submit.prevent="submit" class="max-w-4xl mx-auto space-y-6">

            <!-- Service Request Selection -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <span class="w-6 h-6 bg-[#0a1f44] text-white text-xs rounded-full flex items-center justify-center font-bold">1</span>
                    Service Request
                </h2>
                <div class="relative" ref="srDropdownRef">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Linked Service Request <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input
                            type="text"
                            v-model="srSearch"
                            @focus="srDropdownOpen = true"
                            @input="srDropdownOpen = true"
                            :placeholder="selectedSrLabel || 'Search by reference number, title, or client…'"
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
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 pointer-events-none" v-else>
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
                            <span>{{ sr.reference_number }} — {{ sr.title }} ({{ sr.client?.organization || sr.client?.contact_person }})</span>
                        </button>
                    </div>
                    <p v-if="form.errors.service_request_id" class="text-red-500 text-xs mt-1">{{ form.errors.service_request_id }}</p>
                </div>
            </div>

            <!-- Quotation Details -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <span class="w-6 h-6 bg-[#0a1f44] text-white text-xs rounded-full flex items-center justify-center font-bold">2</span>
                    Quotation Details
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                        <select v-model="form.currency"
                                class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm">
                            <option value="USD">USD – US Dollar</option>
                            <option value="ZWG">ZWG – Zimbabwe Gold</option>
                            <option value="ZAR">ZAR – South African Rand</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Valid Until <span class="text-red-500">*</span></label>
                        <input v-model="form.valid_until" type="date" required
                               class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm" />
                        <p v-if="form.errors.valid_until" class="text-red-500 text-xs mt-1">{{ form.errors.valid_until }}</p>
                    </div>
                </div>
            </div>

            <!-- Line Items -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <span class="w-6 h-6 bg-[#0a1f44] text-white text-xs rounded-full flex items-center justify-center font-bold">3</span>
                    Line Items
                </h2>

                <!-- Desktop Table View (Hidden on mobile) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs uppercase tracking-wider text-gray-400 border-b border-gray-100">
                                <th class="pb-3 text-left font-semibold w-1/2">Description</th>
                                <th class="pb-3 text-right font-semibold w-24">Qty</th>
                                <th class="pb-3 text-right font-semibold w-32">Unit Price</th>
                                <th class="pb-3 text-right font-semibold w-32">Total</th>
                                <th class="pb-3 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(item, index) in lineItems" :key="index" class="py-2">
                                <td class="py-2 pr-3">
                                    <input v-model="item.description" type="text" placeholder="e.g. Translation (A4 pages)" required
                                           class="w-full border-gray-200 rounded-lg text-sm focus:border-[#0a1f44] focus:ring-[#0a1f44]" />
                                </td>
                                <td class="py-2 px-1">
                                    <input v-model.number="item.qty" type="number" min="1" step="0.5" required
                                           class="w-full border-gray-200 rounded-lg text-sm text-right focus:border-[#0a1f44] focus:ring-[#0a1f44]" />
                                </td>
                                <td class="py-2 px-1">
                                    <input v-model.number="item.unit_price" type="number" min="0" step="0.01" required
                                           class="w-full border-gray-200 rounded-lg text-sm text-right focus:border-[#0a1f44] focus:ring-[#0a1f44]" />
                                </td>
                                <td class="py-2 pl-1 text-right font-medium text-gray-900">
                                    {{ form.currency }} {{ (item.qty * item.unit_price).toFixed(2) }}
                                </td>
                                <td class="py-2 pl-2">
                                    <button type="button" @click="removeItem(index)" v-if="lineItems.length > 1"
                                            class="text-red-400 hover:text-red-600 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="pt-3">
                                    <button type="button" @click="addItem"
                                            class="text-xs text-[#0a1f44] hover:text-[#f5c242] font-semibold transition flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Add Line Item
                                    </button>
                                </td>
                            </tr>
                            <tr class="border-t-2 border-gray-200 mt-2">
                                <td colspan="3" class="pt-4 text-right font-bold text-gray-700 text-sm">TOTAL</td>
                                <td class="pt-4 text-right font-bold text-[#0a1f44] text-lg">
                                    {{ form.currency }} {{ grandTotal.toFixed(2) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Mobile Card-based View (Hidden on desktop) -->
                <div class="block md:hidden space-y-4">
                    <div v-for="(item, index) in lineItems" :key="index" class="p-4 bg-gray-50 border border-gray-150 rounded-xl space-y-3 relative">
                        <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                            <span class="text-xs font-bold text-gray-500">Item #{{ index + 1 }}</span>
                            <button type="button" @click="removeItem(index)" v-if="lineItems.length > 1"
                                    class="text-red-400 hover:text-red-650 transition flex items-center gap-1 text-xs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Delete
                            </button>
                        </div>
                        <div class="space-y-2">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Description</label>
                                <input v-model="item.description" type="text" placeholder="e.g. Translation (A4 pages)" required
                                       class="w-full border-gray-200 rounded-lg text-sm focus:border-[#0a1f44] focus:ring-[#0a1f44]" />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Quantity</label>
                                    <input v-model.number="item.qty" type="number" min="1" step="0.5" required
                                           class="w-full border-gray-200 rounded-lg text-sm focus:border-[#0a1f44] focus:ring-[#0a1f44]" />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 mb-1">Unit Price</label>
                                    <input v-model.number="item.unit_price" type="number" min="0" step="0.01" required
                                           class="w-full border-gray-200 rounded-lg text-sm focus:border-[#0a1f44] focus:ring-[#0a1f44]" />
                                </div>
                            </div>
                            <div class="pt-2 border-t border-gray-100 flex justify-between items-center text-xs font-bold text-gray-700">
                                <span>Total Price:</span>
                                <span class="text-sm font-extrabold text-gray-900">{{ form.currency }} {{ (item.qty * item.unit_price).toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="pt-2 flex justify-between items-center border-t border-gray-200">
                        <button type="button" @click="addItem"
                                class="text-xs text-[#0a1f44] hover:text-[#f5c242] font-black transition flex items-center gap-1 bg-gray-50 px-3 py-2 rounded-lg border border-gray-200 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Line Item
                        </button>
                        <div class="text-right">
                            <span class="text-xs text-gray-500 font-bold block">TOTAL ESTIMATE</span>
                            <span class="text-lg font-black text-[#0a1f44]">{{ form.currency }} {{ grandTotal.toFixed(2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <span class="w-6 h-6 bg-[#0a1f44] text-white text-xs rounded-full flex items-center justify-center font-bold">4</span>
                    Notes &amp; Terms
                </h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description / Breakdown</label>
                        <textarea v-model="form.description" rows="4" placeholder="Detailed breakdown or additional information…"
                                  class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm resize-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Internal Notes</label>
                        <textarea v-model="form.notes" rows="3" placeholder="Internal notes (not visible to client)…"
                                  class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm resize-none"></textarea>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pb-6">
                <Link :href="route('quotations.index')"
                      class="px-5 py-2.5 border border-gray-300 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    Cancel
                </Link>
                <button type="submit" :disabled="form.processing"
                        name="status" value="draft"
                        @click="form.status = 'draft'"
                        class="px-5 py-2.5 border border-[#0a1f44] text-[#0a1f44] rounded-xl text-sm font-semibold hover:bg-[#0a1f44]/5 transition disabled:opacity-50">
                    Save as Draft
                </button>
                <button type="submit" :disabled="form.processing"
                        @click="form.status = 'submitted'"
                        class="px-5 py-2.5 bg-[#f5c242] text-[#0a1f44] rounded-xl text-sm font-bold hover:bg-yellow-400 transition disabled:opacity-50 shadow-sm">
                    Submit for Approval
                </button>
            </div>
        </form>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/inertia-vue3';
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    serviceRequests: Array,
    preselectedServiceRequestId: [String, Number],
});

// ── Searchable Service Request Dropdown ──
const srSearch = ref('');
const srDropdownOpen = ref(false);
const srDropdownRef = ref(null);

const filteredServiceRequests = computed(() => {
    const query = srSearch.value.toLowerCase().trim();
    if (!query) return props.serviceRequests || [];
    return (props.serviceRequests || []).filter(sr => {
        const refNum = (sr.reference_number || '').toLowerCase();
        const title = (sr.title || '').toLowerCase();
        const clientName = (sr.client?.organization || sr.client?.contact_person || '').toLowerCase();
        return refNum.includes(query) || title.includes(query) || clientName.includes(query);
    });
});

const selectedSrLabel = computed(() => {
    if (!form.service_request_id) return '';
    const sr = (props.serviceRequests || []).find(s => s.id === form.service_request_id);
    return sr ? `${sr.reference_number} — ${sr.title} (${sr.client?.organization || sr.client?.contact_person})` : '';
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

const lineItems = ref([
    { description: '', qty: 1, unit_price: 0 }
]);

const form = useForm({
    service_request_id: props.preselectedServiceRequestId || '',
    currency: 'USD',
    valid_until: '',
    description: '',
    notes: '',
    status: 'draft',
    amount: 0,
    line_items: [],
});

const grandTotal = computed(() => {
    return lineItems.value.reduce((sum, item) => sum + (item.qty * item.unit_price), 0);
});

const addItem = () => {
    lineItems.value.push({ description: '', qty: 1, unit_price: 0 });
};

const removeItem = (index) => {
    lineItems.value.splice(index, 1);
};

const submit = () => {
    form.amount = grandTotal.value;
    form.line_items = lineItems.value;
    form.post(route('quotations.store'));
};
</script>
