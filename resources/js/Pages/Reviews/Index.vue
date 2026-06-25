<template>
    <Head title="Service Reviews" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <span>Satisfaction Feedback &amp; Reviews</span>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Header Summary Card -->
            <div class="bg-gradient-to-r from-[#0a1f44] to-[#122e60] rounded-2xl p-6 text-white relative overflow-hidden shadow-md">
                <div class="absolute right-0 top-0 w-64 h-64 bg-[#f5c242]/10 rounded-full blur-3xl -mr-20 -mt-20 pointer-events-none"></div>
                <div class="relative z-10 space-y-2 max-w-xl">
                    <h2 class="text-xl font-bold tracking-tight">Your Feedback Shapes Our Service</h2>
                    <p class="text-xs text-white/80 leading-relaxed">
                        Below is a list of all your completed language institute service requests. Sharing your honest rating and comments helps us maintain outstanding translation, brailling, and interpreting standards.
                    </p>
                </div>
            </div>

            <!-- Main Reviews Grid/List -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="text-sm font-bold text-gray-700">Completed Service Requests</h3>
                </div>

                <div class="divide-y divide-gray-100">
                    <div v-if="serviceRequests.length === 0" class="p-8 text-center text-gray-500 text-sm">
                        No completed service requests eligible for feedback at this time.
                    </div>

                    <div v-for="req in serviceRequests" :key="req.id" class="p-6 hover:bg-gray-50/60 transition duration-150 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="space-y-2 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-[#0a1f44] text-sm">{{ req.reference_number }}</span>
                                <span class="text-gray-300">|</span>
                                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 capitalize">{{ req.service_category.replace('_', ' ') }}</span>
                                <span class="text-gray-300">|</span>
                                <span class="text-xs text-gray-500 font-medium">Completed: {{ new Date(req.completed_at || req.updated_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) }}</span>
                            </div>
                            <h4 class="font-bold text-gray-900 text-base leading-tight">{{ req.title }}</h4>
                            
                            <!-- Display review comment if exists -->
                            <div v-if="req.rating" class="mt-2 text-xs text-gray-600 bg-gray-50 p-3 rounded-lg border border-gray-150 italic max-w-2xl">
                                "{{ req.review_comments || 'No comments provided.' }}"
                            </div>
                        </div>

                        <!-- Rating score and actions -->
                        <div class="flex items-center gap-4 flex-shrink-0 self-start md:self-center">
                            <!-- If already rated -->
                            <div v-if="req.rating" class="flex flex-col items-end gap-1">
                                <div class="flex gap-0.5">
                                    <svg v-for="star in 5" :key="star"
                                         class="w-5 h-5"
                                         :class="star <= req.rating ? 'text-amber-400 fill-current' : 'text-gray-200 fill-current'"
                                         viewBox="0 0 24 24"
                                         xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                    </svg>
                                </div>
                                <span class="text-xs text-amber-600 font-semibold bg-amber-50 px-2 py-0.5 rounded uppercase tracking-wider">
                                    {{ getRatingLabel(req.rating) }}
                                </span>
                            </div>

                            <!-- If not yet rated -->
                            <div v-else class="flex flex-col items-end gap-1">
                                <span class="text-xs text-gray-400 italic">No feedback submitted</span>
                            </div>

                            <!-- Interactive Button or Link -->
                            <button v-if="!req.rating"
                                    @click="openReviewModal(req)"
                                    class="px-4 py-2 text-xs font-bold rounded-lg transition-all focus:outline-none bg-amber-500 hover:bg-amber-600 text-white shadow-sm shadow-amber-200 active:scale-95">
                                ★ Write Review
                            </button>
                            <Link v-else
                                  :href="route('service-requests.show', req.id)"
                                  class="px-4 py-2 text-xs font-bold rounded-lg transition-all focus:outline-none bg-gray-100 hover:bg-gray-250 text-gray-700">
                                View Details
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Write Review Modal -->
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
                <div class="bg-white rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl border border-gray-100 flex flex-col relative transform transition-all duration-300 scale-100">
                    
                    <!-- Decorative Top Accent -->
                    <div class="h-2 bg-gradient-to-r from-amber-400 to-[#f5c242]"></div>

                    <!-- Header -->
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                        <div>
                            <h3 class="text-lg font-extrabold text-gray-900">Submit Service Review</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Reference: {{ selectedRequest?.reference_number }}</p>
                        </div>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition p-1 rounded-lg hover:bg-gray-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-5">
                        <!-- Request Quick Stats -->
                        <div class="bg-gray-50 rounded-xl p-4 border border-gray-100 space-y-2">
                            <h4 class="font-bold text-[#0a1f44] text-sm">{{ selectedRequest?.title }}</h4>
                            <div class="flex items-center gap-2 flex-wrap text-xs text-gray-500 font-medium">
                                <span class="capitalize">{{ selectedRequest?.service_category.replace('_', ' ') }}</span>
                                <span>•</span>
                                <span>Completed {{ new Date(selectedRequest?.completed_at || selectedRequest?.updated_at).toLocaleDateString() }}</span>
                            </div>
                        </div>

                        <!-- Star Rating Picker -->
                        <div class="flex flex-col gap-1.5">
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Your Rating</span>
                            <div class="flex items-center gap-1.5">
                                <button v-for="star in 5" :key="star"
                                        type="button"
                                        @click="setRating(star)"
                                        @mouseover="hoverRating = star"
                                        @mouseleave="hoverRating = 0"
                                        class="p-1 focus:outline-none transition transform hover:scale-110 duration-150">
                                    <svg class="w-8 h-8 transition-colors duration-200"
                                         :class="star <= (hoverRating || rating) ? 'text-amber-400 fill-current' : 'text-gray-200 fill-current'"
                                         viewBox="0 0 24 24"
                                         xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                    </svg>
                                </button>
                                <span v-if="rating" class="ml-3 text-xs font-black text-amber-600 bg-amber-50 px-2.5 py-0.5 rounded-full capitalize">
                                    {{ getRatingLabel(rating) }}
                                </span>
                            </div>
                            <p v-if="rateForm.errors.rating" class="text-red-500 text-xs mt-1">{{ rateForm.errors.rating }}</p>
                        </div>

                        <!-- Feedback Comments Textarea -->
                        <div class="space-y-1.5">
                            <label for="modal_review_comments" class="text-xs font-bold uppercase tracking-wider text-gray-400 block">
                                Feedback Comments
                            </label>
                            <textarea id="modal_review_comments"
                                      v-model="rateForm.review_comments"
                                      rows="4"
                                      class="w-full border-gray-250 rounded-xl shadow-sm focus:border-amber-500 focus:ring-amber-500 text-sm resize-none"
                                      placeholder="Tell us about your experience and how we did…"></textarea>
                            <p v-if="rateForm.errors.review_comments" class="text-red-500 text-xs mt-1">{{ rateForm.errors.review_comments }}</p>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="p-6 border-t border-gray-50 flex justify-end gap-3 bg-gray-50/50">
                        <button @click="closeModal"
                                class="px-4 py-2 text-xs font-bold text-gray-600 hover:text-gray-800 transition hover:bg-gray-100 rounded-lg">
                            Cancel
                        </button>
                        <button @click="submitRating"
                                :disabled="rateForm.processing || !rating"
                                class="bg-amber-500 text-white font-bold text-xs px-5 py-2.5 rounded-lg hover:bg-amber-600 active:scale-95 transition-all shadow-sm shadow-amber-200 disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ rateForm.processing ? 'Submitting…' : 'Submit Review' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/inertia-vue3';
import { ref, watch, onUnmounted } from 'vue';

defineProps({
    serviceRequests: Array
});

const showModal = ref(false);
const selectedRequest = ref(null);
const rating = ref(0);
const hoverRating = ref(0);

const rateForm = useForm({
    rating: 0,
    review_comments: '',
});

const updateBodyScroll = () => {
    const mainEl = document.querySelector('main');
    if (showModal.value) {
        document.body.classList.add('overflow-hidden');
        if (mainEl) {
            mainEl.classList.add('overflow-hidden');
            mainEl.style.overflow = 'hidden';
        }
    } else {
        document.body.classList.remove('overflow-hidden');
        if (mainEl) {
            mainEl.classList.remove('overflow-hidden');
            mainEl.style.overflow = '';
        }
    }
};

const openReviewModal = (req) => {
    selectedRequest.value = req;
    rating.value = 0;
    rateForm.rating = 0;
    rateForm.review_comments = '';
    showModal.value = true;
    updateBodyScroll();
};

const closeModal = () => {
    showModal.value = false;
    selectedRequest.value = null;
    updateBodyScroll();
};

onUnmounted(() => {
    document.body.classList.remove('overflow-hidden');
    const mainEl = document.querySelector('main');
    if (mainEl) {
        mainEl.classList.remove('overflow-hidden');
        mainEl.style.overflow = '';
    }
});

const setRating = (val) => {
    rating.value = val;
    rateForm.rating = val;
};

const getRatingLabel = (val) => {
    switch (val) {
        case 5: return 'excellent';
        case 4: return 'very good';
        case 3: return 'good';
        case 2: return 'fair';
        case 1: return 'poor';
        default: return '';
    }
};

const submitRating = () => {
    rateForm.post(route('service-requests.rate', selectedRequest.value.id), {
        onSuccess: () => {
            closeModal();
        }
    });
};
</script>
