<template>
    <Head title="Quotations Management" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <span>Quotations Dashboard</span>
                <Link v-if="($page.props.auth.permissions.includes('manage quotations') || $page.props.auth.permissions.includes('manage system')) && !$page.props.auth.roles.includes('executive_director') && !$page.props.auth.roles.includes('deputy_director')"
                      :href="route('quotations.create')"
                      class="bg-brand-gold hover:bg-brand-gold-dark text-brand-blue font-bold py-2 px-4 rounded-lg shadow transition text-sm flex items-center gap-1">
                    + Generate Quotation
                </Link>
            </div>
        </template>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <!-- Filter Bar -->
            <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input v-model="form.search" @input="debouncedSearch" type="text" placeholder="Search by reference, client or request ref..." 
                           class="w-full border-gray-300 rounded-xl shadow-sm text-sm focus:border-[#0a1f44] focus:ring-[#0a1f44]" />
                </div>
                
                <div class="w-48">
                    <select v-model="form.status" @change="submitFilters"
                            class="w-full border-gray-300 rounded-xl shadow-sm text-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] capitalize">
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="submitted">Pending Recommendation</option>
                        <option value="pending_approval">Recommended</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>

                <button v-if="form.search || form.status" @click="resetFilters"
                        class="px-4 py-2 text-sm text-[#0a1f44] hover:text-[#0a1f44]/80 font-bold flex items-center gap-1.5 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Clear Filters
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                            <th class="px-6 py-4 font-semibold">Quotation Ref</th>
                            <th class="px-6 py-4 font-semibold">Client</th>
                            <th class="px-6 py-4 font-semibold">Service Request</th>
                            <th class="px-6 py-4 font-semibold">Amount</th>
                            <th class="px-6 py-4 font-semibold">Status</th>
                            <th class="px-6 py-4 font-semibold">Valid Until</th>
                            <th class="px-6 py-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        <tr v-if="quotations.data.length === 0">
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">No quotations found.</td>
                        </tr>
                        <tr v-for="quotation in quotations.data" :key="quotation.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ quotation.reference_number }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ quotation.service_request?.client?.organization || quotation.service_request?.client?.contact_person }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ quotation.service_request?.reference_number }}</td>
                            <td class="px-6 py-4 font-semibold text-[#0a1f44]">{{ quotation.currency }} {{ parseFloat(quotation.amount).toFixed(2) }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium capitalize"
                                    :class="getQuotationStatus(quotation).class">
                                    {{ getQuotationStatus(quotation).label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500">{{ new Date(quotation.valid_until).toLocaleDateString() }}</td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="route('quotations.show', quotation.id)" class="text-blue-600 hover:text-blue-900 font-medium text-sm">Review</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between" v-if="quotations.total > 0">
                <span class="text-sm text-gray-500">
                    Showing {{ quotations.from }} to {{ quotations.to }} of {{ quotations.total }} results
                </span>
                <div class="flex gap-1" v-if="quotations.links">
                    <Link v-for="(link, i) in quotations.links" :key="i" :href="link.url || '#'" 
                          class="px-3 py-1 rounded border text-sm" 
                          :class="link.active ? 'bg-[#0a1f44] text-white border-[#0a1f44]' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                          v-html="link.label">
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/inertia-vue3';
import { reactive } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import { debounce } from 'lodash';

const props = defineProps({
    quotations: Object,
    filters: Object
});

const form = reactive({
    search: props.filters?.search || '',
    status: props.filters?.status || '',
});

const submitFilters = () => {
    Inertia.get(route('quotations.index'), {
        search: form.search,
        status: form.status,
    }, {
        preserveState: true,
        replace: true
    });
};

const debouncedSearch = debounce(submitFilters, 300);

const resetFilters = () => {
    form.search = '';
    form.status = '';
    submitFilters();
};

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
