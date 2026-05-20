<template>
    <Head title="Quotations Management" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <span>Quotations Dashboard</span>
                <button class="bg-[#f5c242] text-[#0a1f44] hover:bg-yellow-500 px-4 py-2 rounded-lg font-semibold text-sm transition-colors shadow-sm">
                    + Generate Quotation
                </button>
            </div>
        </template>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex gap-4">
                <input type="text" placeholder="Search quotations..." class="border-gray-300 rounded-md shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500 w-64" />
                <select class="border-gray-300 rounded-md shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Statuses</option>
                    <option value="draft">Draft</option>
                    <option value="pending_approval">Pending Approval</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
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
                                    :class="{
                                        'bg-yellow-100 text-yellow-800': quotation.status === 'pending_approval',
                                        'bg-green-100 text-green-800': quotation.status === 'approved',
                                        'bg-red-100 text-red-800': quotation.status === 'rejected',
                                        'bg-gray-100 text-gray-800': ['draft', 'expired'].includes(quotation.status)
                                    }">
                                    {{ quotation.status.replace('_', ' ') }}
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

defineProps({
    quotations: Object
});
</script>
