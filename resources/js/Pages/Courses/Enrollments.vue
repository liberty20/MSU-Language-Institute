<template>
    <AuthenticatedLayout>
        <template #header>
            Short Course Student Enrollments
        </template>

        <div class="space-y-8">
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                <h3 class="text-lg font-bold text-brand-blue">Enrollment & Billing Operations</h3>
                <p class="text-sm text-gray-500">Verify student bank transfer proofs, activate active statuses, and track/issue course certificates.</p>
            </div>

            <!-- Enrollments Table -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-4 font-semibold">Student Name</th>
                                <th class="px-6 py-4 font-semibold">Enrolled Course</th>
                                <th class="px-6 py-4 font-semibold">Scheduled Batch</th>
                                <th class="px-6 py-4 font-semibold">Payment Status</th>
                                <th class="px-6 py-4 font-semibold">Amount Paid</th>
                                <th class="px-6 py-4 font-semibold">Enrollment Status</th>
                                <th class="px-6 py-4 font-semibold">Certificate</th>
                                <th class="px-6 py-4 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <tr v-for="enrollment in enrollments.data" :key="enrollment.id" class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-brand-blue">{{ enrollment.user.name }}</div>
                                    <div class="text-xs text-gray-400 mt-0.5">{{ enrollment.user.email }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-gray-700">{{ enrollment.intake.course.title }}</div>
                                    <div class="text-xs text-gray-400 mt-0.5">Code: {{ enrollment.intake.course.code }}</div>
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-600">{{ enrollment.intake.name }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-full uppercase tracking-wider"
                                            :class="{
                                                'bg-green-50 text-green-700': enrollment.payment_status === 'verified',
                                                'bg-amber-50 text-amber-700': enrollment.payment_status === 'pending',
                                                'bg-red-50 text-red-700': enrollment.payment_status === 'failed'
                                            }">
                                            {{ enrollment.payment_status }}
                                        </span>
                                        <a v-if="enrollment.payment_proof_path" :href="'/storage/' + enrollment.payment_proof_path" target="_blank" class="p-1 text-brand-gold-dark hover:text-brand-blue hover:bg-gray-100 rounded transition" title="View Payment Proof">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </a>
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-bold text-gray-700">
                                    {{ enrollment.intake.course.currency }} {{ Number(enrollment.amount_paid).toLocaleString(undefined, {minimumFractionDigits: 2}) }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full uppercase tracking-wider"
                                        :class="{
                                            'bg-green-100 text-green-800': enrollment.enrollment_status === 'active',
                                            'bg-blue-100 text-blue-800': enrollment.enrollment_status === 'completed',
                                            'bg-amber-100 text-amber-800': enrollment.enrollment_status === 'pending',
                                            'bg-red-100 text-red-800': enrollment.enrollment_status === 'dropped'
                                        }">
                                        {{ enrollment.enrollment_status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div v-if="enrollment.certificate_code" class="space-y-0.5">
                                        <div class="text-xs font-bold text-brand-gold-dark">{{ enrollment.certificate_code }}</div>
                                        <div class="text-[0.65rem] text-gray-400">Issued: {{ formatDate(enrollment.certificate_issued_at) }}</div>
                                    </div>
                                    <div v-else class="text-xs text-gray-400 italic">Not Issued</div>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button v-if="enrollment.payment_status === 'pending'" @click="openVerifyModal(enrollment)" class="bg-[#0a1f44] hover:bg-[#0c2859] text-white text-xs font-bold px-3 py-1.5 rounded-full transition shadow">
                                        Verify Payment
                                    </button>
                                    <button v-if="enrollment.enrollment_status === 'active'" @click="issueCertificate(enrollment)" class="bg-[#f5c242] hover:bg-yellow-400 text-[#0a1f44] text-xs font-bold px-3 py-1.5 rounded-full transition shadow">
                                        Issue Cert
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="enrollments.data.length === 0">
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    No student enrollments found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Verify Payment Modal -->
        <div v-if="verifyModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="verifyModalOpen = false">
            <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden">
                <div class="bg-[#0a1f44] text-white px-6 py-4 flex justify-between items-center">
                    <h3 class="text-lg font-bold">Verify Payment Proof</h3>
                    <button @click="verifyModalOpen = false" class="text-gray-300 hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="submitVerify" class="p-6 space-y-4">
                    <div>
                        <p class="text-sm text-gray-600 mb-4">
                            Please review the uploaded receipt. Enter the exact verified amount paid by <strong class="text-brand-blue">{{ selectedEnrollment?.user.name }}</strong> for the course <strong>{{ selectedEnrollment?.intake.course.title }}</strong>.
                        </p>
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-150 flex items-center justify-between mb-4">
                            <div>
                                <span class="text-xs text-gray-400 font-semibold uppercase">Expected Tuition Fee</span>
                                <p class="text-base font-bold text-gray-700">{{ selectedEnrollment?.intake.course.currency }} {{ Number(selectedEnrollment?.intake.course.price).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</p>
                            </div>
                            <a v-if="selectedEnrollment?.payment_proof_path" :href="'/storage/' + selectedEnrollment.payment_proof_path" target="_blank" class="text-sm font-semibold text-brand-gold-dark hover:text-brand-blue flex items-center gap-1.5 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                Open Receipt
                            </a>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Verified Amount Paid ({{ selectedEnrollment?.intake.course.currency }})</label>
                            <input v-model="verifyForm.amount_paid" type="number" step="0.01" min="0" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm" required />
                        </div>
                    </div>
                    <div class="pt-4 border-t border-gray-150 flex justify-end gap-3">
                        <button type="button" @click="verifyModalOpen = false" class="px-5 py-2.5 rounded-full border border-gray-300 font-semibold text-gray-700 hover:bg-gray-50 transition text-sm">Cancel</button>
                        <button type="submit" class="px-5 py-2.5 rounded-full bg-green-600 hover:bg-green-700 text-white font-bold transition shadow text-sm">Approve & Activate</button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Inertia } from '@inertiajs/inertia';
import { ref, reactive } from 'vue';

defineProps({
    enrollments: Object,
});

const verifyModalOpen = ref(false);
const selectedEnrollment = ref(null);
const verifyForm = reactive({
    amount_paid: 0,
});

const openVerifyModal = (enrollment) => {
    selectedEnrollment.value = enrollment;
    verifyForm.amount_paid = enrollment.intake.course.price;
    verifyModalOpen.value = true;
};

const submitVerify = () => {
    Inertia.post(route('course-enrollments.verify', selectedEnrollment.value.id), verifyForm, {
        onSuccess: () => {
            verifyModalOpen.value = false;
        }
    });
};

const issueCertificate = (enrollment) => {
    if (confirm('Are you sure you want to issue a course completion certificate for ' + enrollment.user.name + '? This will mark their enrollment status as completed.')) {
        Inertia.post(route('course-enrollments.certificate', enrollment.id));
    }
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
};
</script>
