<template>
    <Head title="Payment Details" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 w-full">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 leading-tight">Payment Details</h2>
                    <p class="text-sm text-gray-500 mt-1">View bank accounts and submit transaction proofs for approved quotations.</p>
                </div>
                <Link :href="route('payments.create')" 
                      class="px-5 py-2.5 bg-[#f5c242] hover:bg-[#d4af37] text-[#0a1f44] font-bold rounded-xl text-sm transition shadow-sm flex items-center gap-2 hover:scale-[1.02] transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Upload Proof of Payment
                </Link>
            </div>
        </template>

        <div class="space-y-8">
            <!-- Bank Accounts Cards Section -->
            <div>
                <h3 class="text-lg font-bold text-[#0a1f44] mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#d4af37]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    MSU Language Institute Bank Accounts
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="bank in bankAccounts" :key="bank.id" 
                         class="relative overflow-hidden bg-gradient-to-br from-[#0a1f44] to-[#1e3a6a] text-white rounded-2xl p-6 shadow-md border border-[#f5c242]/20 flex flex-col justify-between h-56 transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                        <!-- Card background watermark -->
                        <div class="absolute right-0 bottom-0 opacity-10 translate-x-4 translate-y-4">
                            <svg class="w-48 h-48" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                        </div>
                        
                        <div>
                            <div class="flex justify-between items-start">
                                <span class="text-xs font-semibold tracking-wider text-[#f5c242] uppercase bg-[#f5c242]/10 px-2.5 py-1 rounded-md border border-[#f5c242]/20">MSU Account</span>
                                <div class="flex flex-col gap-1 items-end">
                                    <span class="text-sm font-bold text-white/80">{{ bank.bank_name }}</span>
                                    <span class="text-[10px] font-bold text-white/90 bg-[#f5c242]/25 px-1.5 py-0.5 rounded border border-[#f5c242]/40 tracking-wider uppercase font-mono">{{ bank.currency || 'USD' }}</span>
                                </div>
                            </div>
                            <div class="mt-4">
                                <span class="text-xs text-white/50 block uppercase tracking-wider">Account Number</span>
                                <span class="text-xl font-mono font-bold tracking-widest text-[#f5c242] block mt-0.5">{{ bank.account_number }}</span>
                            </div>
                        </div>

                        <div class="border-t border-white/10 pt-3 mt-4 flex justify-between text-xs text-white/70">
                            <div>
                                <span class="block text-[10px] text-white/40 uppercase">Account Name</span>
                                <span class="font-semibold text-white mt-0.5 block">{{ bank.account_name }}</span>
                            </div>
                            <div v-if="bank.branch_code" class="text-right">
                                <span class="block text-[10px] text-white/40 uppercase">Branch</span>
                                <span class="font-semibold text-white mt-0.5 block">{{ bank.branch_code }}</span>
                            </div>
                            <div v-if="bank.swift_code" class="text-right">
                                <span class="block text-[10px] text-white/40 uppercase">SWIFT</span>
                                <span class="font-semibold text-white mt-0.5 block">{{ bank.swift_code }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="bankAccounts.length === 0" 
                         class="col-span-full bg-white rounded-2xl border border-dashed border-gray-300 p-8 text-center text-gray-500">
                        No active banking details configured by the finance department. Please contact us for assistance.
                    </div>
                </div>
            </div>

            <!-- Transaction History Section -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-bold text-[#0a1f44]">Upload History</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Track your submitted proofs of payment and verification outcomes.</p>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-4 font-semibold">Date Submitted</th>
                                <th class="px-6 py-4 font-semibold">Quotation Ref</th>
                                <th class="px-6 py-4 font-semibold">Service Request</th>
                                <th class="px-6 py-4 font-semibold">Bank Used</th>
                                <th class="px-6 py-4 font-semibold">Amount Paid</th>
                                <th class="px-6 py-4 font-semibold">Proof File</th>
                                <th class="px-6 py-4 font-semibold">Verification Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="payment in payments.data" :key="payment.id" class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ new Date(payment.created_at).toLocaleDateString() }}
                                </td>
                                <td class="px-6 py-4 font-medium text-[#0a1f44]">
                                    {{ payment.quotation.reference_number }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ payment.quotation.service_request.title }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ payment.bank_used }}
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-900">
                                    {{ payment.quotation?.currency || 'USD' }} {{ Number(payment.amount_paid).toLocaleString(undefined, {minimumFractionDigits: 2}) }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <a :href="route('payments.proof', payment.id)" target="_blank" 
                                       class="text-[#d4af37] hover:text-[#0a1f44] font-semibold inline-flex items-center gap-1.5 transition">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        View Attachment
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1">
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-full uppercase tracking-wide w-fit"
                                            :class="{
                                                'bg-yellow-100 text-yellow-800': payment.status === 'pending',
                                                'bg-green-100 text-green-800': payment.status === 'verified',
                                                'bg-red-100 text-red-800': payment.status === 'rejected'
                                            }">
                                            {{ payment.status }}
                                        </span>
                                        <span v-if="payment.status === 'rejected' && payment.notes" class="text-xs text-red-600 italic">
                                            Reason: {{ payment.notes }}
                                        </span>
                                        <span v-if="payment.status === 'verified' && payment.verified_at" class="text-[10px] text-gray-500">
                                            Verified: {{ new Date(payment.verified_at).toLocaleDateString() }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="payments.data.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    You have not uploaded any proofs of payment yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="payments.links && payments.links.length > 3" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <div class="text-xs text-gray-500">
                        Showing {{ payments.from }} to {{ payments.to }} of {{ payments.total }} transactions
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
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/inertia-vue3';

defineProps({
    bankAccounts: Array,
    payments: Object,
});
</script>
