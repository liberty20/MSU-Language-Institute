<template>
    <AuthenticatedLayout>
        <template #header>
            My Assignments
        </template>

        <div class="space-y-8">
            <div class="bg-white p-6 rounded-2xl border border-gray-150 shadow-sm flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                <div>
                    <h3 class="text-lg font-bold text-brand-blue">Short Course Assignment Tasks</h3>
                    <p class="text-sm text-gray-500">Download assignment questions, submit your responses, and review grades and instructor feedback.</p>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="assignments.length === 0" class="py-16 text-center bg-white rounded-2xl border border-gray-150 p-8 shadow-sm">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <h4 class="font-bold text-brand-blue text-base">No Assigned Assignments Found</h4>
                <p class="text-xs text-gray-400 mt-1">Assignments will appear here once your instructors publish coursework for your active intakes.</p>
            </div>

            <!-- Assignments Grid -->
            <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div v-for="asg in assignments" :key="asg.id" class="bg-white rounded-2xl border border-gray-150 overflow-hidden flex flex-col shadow-sm hover:shadow-md transition">
                    <div class="p-6 flex-grow space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="px-2.5 py-0.5 text-[0.65rem] font-bold bg-[#0a1f44]/10 text-brand-blue rounded-full uppercase tracking-wider">
                                {{ asg.course_code }}
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                :class="{
                                    'bg-green-50 text-green-700 border border-green-200': asg.status === 'graded',
                                    'bg-blue-50 text-blue-700 border border-blue-200': asg.status === 'submitted',
                                    'bg-amber-50 text-amber-700 border border-amber-200': asg.status === 'pending',
                                    'bg-red-50 text-red-700 border border-red-200': asg.status === 'overdue'
                                }">
                                {{ asg.status }}
                            </span>
                        </div>

                        <div>
                            <h3 class="text-lg font-black text-brand-blue leading-snug">{{ asg.title }}</h3>
                            <p class="text-xs text-gray-400 font-semibold mt-1">Course: {{ asg.course_title }}</p>
                        </div>

                        <p class="text-xs text-gray-600 leading-relaxed">{{ asg.description || 'No additional description provided.' }}</p>

                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-150 space-y-2 text-xs text-gray-650 font-medium">
                            <div class="flex justify-between">
                                <span class="text-gray-400">Due Date:</span>
                                <span class="text-gray-800 font-bold">{{ asg.due_date }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Max Score:</span>
                                <span class="text-gray-800 font-bold">{{ asg.max_marks }} Marks</span>
                            </div>
                            <div v-if="asg.attachment_path" class="flex justify-between items-center pt-1 border-t border-gray-200 mt-2">
                                <span class="text-gray-400">Reference File:</span>
                                <a :href="'/storage/' + asg.attachment_path" target="_blank" class="text-blue-600 font-bold hover:underline flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Download Material
                                </a>
                            </div>
                        </div>

                        <!-- Graded feedback -->
                        <div v-if="asg.status === 'graded'" class="bg-green-50/50 border border-green-200/50 p-4 rounded-xl space-y-2 text-xs">
                            <div class="flex justify-between items-center font-bold text-green-800 border-b border-green-200/50 pb-1.5">
                                <span>Graded Marks:</span>
                                <span class="text-sm font-extrabold">{{ asg.submission.marks_obtained }} / {{ asg.max_marks }}</span>
                            </div>
                            <p class="text-gray-700 italic mt-1 leading-relaxed">
                                <strong>Feedback:</strong> {{ asg.submission.feedback || 'No comments left.' }}
                            </p>
                        </div>
                    </div>

                    <!-- Footer Submission Panel -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-150 flex flex-col gap-3">
                        <div v-if="asg.submission" class="flex justify-between items-center text-xs">
                            <div>
                                <span class="text-gray-400 font-semibold block uppercase">Submission File</span>
                                <a :href="'/storage/' + asg.submission.attachment_path" target="_blank" class="text-blue-600 font-bold hover:underline flex items-center gap-1 mt-0.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    View Submission
                                </a>
                            </div>
                            <div class="text-right">
                                <span class="text-gray-400 font-semibold block uppercase">Submitted Date</span>
                                <span class="text-gray-700 font-semibold mt-0.5 block">{{ asg.submission.submitted_at }}</span>
                            </div>
                        </div>

                        <div v-else>
                            <button @click="openSubmitModal(asg)" class="w-full bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold text-xs py-2.5 px-4 rounded-xl transition shadow text-center flex justify-center items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                Submit Assignment
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submission Modal -->
        <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeModal">
            <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden border border-gray-150">
                <div class="bg-[#0a1f44] text-white px-6 py-4 flex justify-between items-center border-b border-[#f5c242]/20">
                    <div>
                        <h3 class="text-base font-bold">Submit Assignment response</h3>
                        <p class="text-[10px] text-[#f5c242] mt-0.5">{{ selectedAsg?.course_code }} - {{ selectedAsg?.title }}</p>
                    </div>
                    <button @click="closeModal" class="text-gray-300 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="submitAssignment" class="p-6 space-y-4">
                    <div v-if="flashError" class="bg-red-50 border border-red-200 text-red-700 p-3 rounded-lg text-xs font-semibold">
                        {{ flashError }}
                    </div>

                    <!-- File Upload -->
                    <div>
                        <label class="block text-xs font-bold text-gray-750 uppercase tracking-wide mb-1.5">Upload Submission File *</label>
                        <input type="file" @change="handleFileUpload" required class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#0a1f44]/10 file:text-brand-blue hover:file:bg-[#0a1f44]/20" />
                        <span class="text-[10px] text-gray-400 block mt-1">Supported formats: PDF, ZIP, DOC, DOCX, PNG, JPG (Max 10MB)</span>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-xs font-bold text-gray-750 uppercase tracking-wide mb-1.5">Additional Student Notes (Optional)</label>
                        <textarea v-model="form.notes" rows="3" class="w-full text-xs rounded-xl border-gray-350 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="Enter comments or submission notes..."></textarea>
                    </div>

                    <div class="pt-4 border-t border-gray-150 flex justify-end gap-3">
                        <button type="button" @click="closeModal" class="px-4 py-2 rounded-full border border-gray-300 font-semibold text-gray-700 hover:bg-gray-50 transition text-xs">Cancel</button>
                        <button type="submit" :disabled="submitting" class="px-5 py-2 rounded-full bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold transition shadow text-xs disabled:opacity-50">
                            {{ submitting ? 'Uploading Submission...' : 'Upload Work' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, reactive } from 'vue';
import { Inertia } from '@inertiajs/inertia';

defineProps({
    assignments: Array,
});

const modalOpen = ref(false);
const selectedAsg = ref(null);
const fileUpload = ref(null);
const submitting = ref(false);
const flashError = ref('');

const form = reactive({
    notes: '',
});

const openSubmitModal = (asg) => {
    selectedAsg.value = asg;
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
    selectedAsg.value = null;
    fileUpload.value = null;
    form.notes = '';
    flashError.value = '';
};

const handleFileUpload = (e) => {
    const file = e.target.files[0];
    if (file && file.size > 10 * 1024 * 1024) {
        flashError.value = 'Validation Error: File size exceeds the maximum 10MB limit.';
        e.target.value = '';
        fileUpload.value = null;
        return;
    }
    fileUpload.value = file;
    flashError.value = '';
};

const submitAssignment = () => {
    if (!fileUpload.value) {
        flashError.value = 'Please upload a submission file.';
        return;
    }

    submitting.value = true;
    flashError.value = '';

    const formData = new FormData();
    formData.append('attachment', fileUpload.value);
    formData.append('notes', form.notes);

    Inertia.post(route('student.assignments.submit', selectedAsg.value.id), formData, {
        onSuccess: () => {
            submitting.value = false;
            closeModal();
        },
        onError: (err) => {
            submitting.value = false;
            if (Object.keys(err).length > 0) {
                flashError.value = Object.values(err).join(' ');
            } else {
                flashError.value = 'An error occurred during upload. Please try again.';
            }
        }
    });
};
</script>
