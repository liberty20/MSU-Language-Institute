<template>
    <AuthenticatedLayout>
        <template #header>
            My Short Courses
        </template>

        <div class="space-y-8">
            <!-- Header Banner -->
            <div class="bg-gradient-to-r from-[#0a1f44] to-[#0c2859] p-6 rounded-2xl border border-gray-150 shadow-md flex flex-col md:flex-row md:justify-between md:items-center gap-6 text-white">
                <div class="flex items-center gap-4 flex-grow min-w-0">
                    <div class="w-16 h-16 rounded-full bg-white/10 flex items-center justify-center shadow flex-shrink-0 overflow-hidden">
                        <img v-if="$page.props.auth.user.avatar" :src="'/storage/' + $page.props.auth.user.avatar" class="w-full h-full object-cover" alt="User Profile Picture" />
                        <svg v-else class="w-10 h-10 text-brand-gold" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                    </div>
                    <div class="space-y-1 min-w-0">
                        <span class="inline-block py-0.5 px-3 rounded-full bg-white/10 text-brand-gold text-xs font-bold tracking-widest uppercase mb-1">
                            Student Learning Portal
                        </span>
                        <h3 class="text-xl font-bold truncate">{{ greeting }}, {{ $page.props.auth.user.name }}</h3>
                        <p class="text-sm text-gray-300">View your active short course registrations, class schedules, assignments progress, and latest continuous assessment feedback.</p>
                    </div>
                </div>
                <a href="/courses-catalog" class="bg-brand-gold hover:bg-yellow-400 text-[#0a1f44] font-bold px-6 py-2.5 rounded-full transition shadow text-sm self-start md:self-center flex-shrink-0">
                    Browse Course Catalog &rarr;
                </a>
            </div>



            <!-- Enrollments Cards Grid -->
            <div class="space-y-4">
                <h4 class="text-base font-black text-[#0a1f44] tracking-tight border-b border-gray-150 pb-2">My Enrolled Course Sessions</h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-for="enrollment in enrollments" :key="enrollment.id" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col hover:shadow-md transition">
                        <div class="p-6 flex-grow space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="px-2.5 py-1 text-[0.65rem] font-bold bg-amber-50 text-amber-700 rounded-full uppercase tracking-wider">
                                    {{ enrollment.intake.course.category }}
                                </span>
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full uppercase tracking-wider"
                                    :class="{
                                        'bg-green-50 text-green-700': enrollment.enrollment_status === 'active',
                                        'bg-blue-50 text-blue-700': enrollment.enrollment_status === 'completed',
                                        'bg-amber-50 text-amber-700': enrollment.enrollment_status === 'pending',
                                        'bg-red-50 text-red-700': enrollment.enrollment_status === 'dropped'
                                    }">
                                    Registration: {{ enrollment.enrollment_status }}
                                </span>
                            </div>
                            
                            <div>
                                <h3 class="text-lg font-bold text-[#0a1f44] leading-snug">{{ enrollment.intake.course.title }}</h3>
                                <p class="text-xs text-gray-400 font-medium mt-1">Code: {{ enrollment.intake.course.code }} | {{ enrollment.intake.course.duration_weeks }} Weeks</p>
                            </div>

                            <!-- Schedule details -->
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-150 space-y-2 text-xs text-gray-650 font-medium">
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Intake Batch:</span>
                                    <span class="text-gray-700 font-semibold">{{ enrollment.intake.name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Class Schedule:</span>
                                    <span class="text-gray-700">{{ formatDate(enrollment.intake.start_date) }} to {{ formatDate(enrollment.intake.end_date) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Assigned Instructor:</span>
                                    <span class="text-gray-700 font-bold text-brand-gold-dark">{{ enrollment.intake.instructor ? enrollment.intake.instructor.name : 'TBA' }}</span>
                                </div>
                            </div>

                            <!-- Certificate Section -->
                            <div v-if="enrollment.certificate_code" class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-center justify-between text-xs gap-3">
                                <div class="flex-grow">
                                    <p class="font-bold text-emerald-800 uppercase tracking-wide">
                                        {{ enrollment.enrollment_status === 'completed' ? 'Certificate Collected!' : 'Certificate Ready for Collection!' }}
                                    </p>
                                </div>
                                <div class="flex gap-2 items-center">
                                    <button 
                                        v-if="enrollment.enrollment_status !== 'completed'"
                                        @click="viewCertificate(enrollment)" 
                                        class="bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold px-3 py-1.5 rounded-full transition shadow text-[0.7rem] uppercase"
                                    >
                                        View Certificate
                                    </button>
                                    <span v-else class="px-3 py-1.5 rounded-full bg-emerald-100 text-emerald-800 font-extrabold uppercase text-[0.65rem] border border-emerald-200/50 flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        Certificate Issued Physically
                                    </span>
                                    <button 
                                        v-if="enrollment.enrollment_status === 'completed'" 
                                        @click="openTestimonialModal(enrollment)" 
                                        class="bg-[#f5c242] hover:bg-yellow-400 text-[#0a1f44] font-black px-3 py-1.5 rounded-full transition shadow text-[0.7rem] uppercase"
                                    >
                                        Share Testimony
                                    </button>
                                </div>
                            </div>

                            <!-- Share testimony if completed but no certificate yet -->
                            <div v-if="enrollment.enrollment_status === 'completed' && !enrollment.certificate_code" class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-center justify-between text-xs gap-3">
                                <div class="flex-grow">
                                    <p class="font-bold text-blue-800 uppercase tracking-wide">Course Completed!</p>
                                    <p class="text-gray-600 font-medium mt-0.5">We would love to hear your feedback.</p>
                                </div>
                                <button @click="openTestimonialModal(enrollment)" class="bg-[#f5c242] hover:bg-yellow-400 text-[#0a1f44] font-black px-3 py-1.5 rounded-full transition shadow text-[0.7rem] uppercase">
                                    Share Testimony
                                </button>
                            </div>
                        </div>

                        <!-- Payment Footer Block -->
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center text-xs">
                            <div>
                                <span class="text-gray-400 font-semibold uppercase block">Payment Status</span>
                                <span class="font-bold uppercase tracking-wider mt-0.5 block"
                                    :class="{
                                        'text-green-600': enrollment.payment_status === 'verified',
                                        'text-amber-600': enrollment.payment_status === 'pending',
                                        'text-red-600': enrollment.payment_status === 'failed'
                                    }">
                                    {{ enrollment.payment_status }}
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-gray-400 font-semibold uppercase block">Amount Paid</span>
                                <span class="font-bold text-gray-700 text-sm mt-0.5 block">
                                    {{ enrollment.intake.course.currency }} {{ Number(enrollment.amount_paid).toLocaleString(undefined, {minimumFractionDigits: 2}) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-if="enrollments.length === 0" class="col-span-2 py-16 text-center bg-white rounded-2xl border border-gray-150 p-8 shadow-sm">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        <h4 class="font-bold text-brand-blue text-base">You are not enrolled in any courses yet</h4>
                        <p class="text-xs text-gray-400 mt-1 mb-6">Browse our short courses catalog to register and enroll in swahili, braille, and sign language programs.</p>
                        <a href="/courses-catalog" class="inline-block bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold px-6 py-2.5 rounded-full transition shadow text-sm">
                            Go to Catalog &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Certificate View Modal -->
        <div v-if="certModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="certModalOpen = false">
            <div class="bg-white rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden p-8 space-y-6 text-center border-8 border-brand-gold relative">
                <!-- Decorative Border -->
                <div class="absolute inset-2 border-2 border-brand-blue pointer-events-none"></div>

                <!-- Certificate Title -->
                <div class="space-y-2 py-4">
                    <img src="/msu-logo-2.png" alt="MSU Logo" class="h-20 mx-auto" />
                    <h2 class="text-2xl font-black text-brand-blue tracking-wide">Midlands State University</h2>
                    <h3 class="text-xs font-bold text-brand-gold uppercase tracking-widest">National Language Institute</h3>
                </div>

                <!-- Certificate Body -->
                <div class="space-y-4 py-4">
                    <p class="text-xs text-gray-400 uppercase tracking-widest font-semibold">This is to certify that</p>
                    <h4 class="text-2xl font-serif font-bold text-gray-800 border-b border-gray-200 pb-2 max-w-md mx-auto">{{ selectedCertEnrollment?.user.name }}</h4>
                    <p class="text-sm text-gray-500 italic px-8">has successfully completed the prescribed curriculum and training program in</p>
                    <h3 class="text-xl font-bold text-brand-blue leading-snug">{{ selectedCertEnrollment?.intake.course.title }}</h3>
                    <p class="text-xs text-gray-500 font-semibold mt-1">Duration: {{ selectedCertEnrollment?.intake.course.duration_weeks }} Weeks ({{ selectedCertEnrollment?.intake.name }})</p>
                </div>

                <!-- Certificate Signatures -->
                <div class="grid grid-cols-2 gap-8 pt-8 text-center text-xs border-t border-gray-150">
                    <div>
                        <div class="font-serif italic text-gray-800 text-sm">Executive Director</div>
                        <div class="text-[0.65rem] text-gray-400 mt-0.5 border-t border-gray-200 pt-1.5 w-32 mx-auto">MSULI Management</div>
                    </div>
                    <div>
                        <div class="font-mono text-xs text-brand-gold-dark font-bold">{{ selectedCertEnrollment?.certificate_code }}</div>
                        <div class="text-[0.65rem] text-gray-400 mt-0.5 border-t border-gray-200 pt-1.5 w-32 mx-auto">Certificate Verification</div>
                    </div>
                </div>
                
                <div class="flex justify-end pt-4">
                    <button @click="certModalOpen = false" class="bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold px-6 py-2 rounded-full transition shadow text-xs">
                        Close Certificate
                    </button>
                </div>
            </div>
        </div>

        <!-- Testimony Submission Modal -->
        <div v-if="testimonyModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeTestimonyModal">
            <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200 text-xs">
                <div class="bg-[#0a1f44] text-white px-8 py-5 flex justify-between items-center border-b border-brand-gold/25">
                    <div>
                        <span class="px-2.5 py-0.5 text-[0.6rem] bg-brand-gold/15 text-brand-gold border border-brand-gold/30 rounded font-black uppercase tracking-widest">Share Your Experience</span>
                        <h3 class="text-lg font-black mt-1">Submit Testimony</h3>
                        <p class="text-[10px] text-gray-300 mt-0.5 uppercase font-bold tracking-wider">Course: {{ selectedTestimonyEnrollment?.intake.course.title }}</p>
                    </div>
                    <button @click="closeTestimonyModal" class="text-gray-300 hover:text-white transition p-1 rounded-full hover:bg-white/5">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form @submit.prevent="submitTestimony" class="p-8 space-y-4">
                    <div>
                        <label class="block text-[10px] font-black text-gray-700 uppercase tracking-wide mb-1.5">Your Testimony (max 1000 characters)</label>
                        <textarea 
                            v-model="testimonyText" 
                            rows="5" 
                            required 
                            maxlength="1000"
                            class="w-full text-xs rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-xs" 
                            placeholder="Describe your learning experience, what you achieved, and how the National Language Institute course helped advance your career..."
                        ></textarea>
                    </div>

                    <div class="pt-4 border-t border-gray-150 flex justify-end gap-3">
                        <button type="button" @click="closeTestimonyModal" class="px-5 py-2.5 rounded-full border border-gray-300 font-bold text-gray-700 hover:bg-gray-50 transition text-[10px] uppercase tracking-wider">Cancel</button>
                        <button type="submit" :disabled="submittingTestimony" class="px-6 py-2.5 rounded-full bg-[#0a1f44] hover:bg-[#0c2859] text-[#f5c242] font-black transition shadow text-[10px] uppercase tracking-wider disabled:opacity-50">
                            {{ submittingTestimony ? 'Submitting...' : 'Submit Testimony' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Inertia } from '@inertiajs/inertia';
import { ref, onMounted, onUnmounted, computed } from 'vue';

defineProps({
    enrollments: Array,
});

const currentHour = ref(new Date().getHours());
let timer = null;

onMounted(() => {
    timer = setInterval(() => {
        currentHour.value = new Date().getHours();
    }, 60000);
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});

const greeting = computed(() => {
    const hr = currentHour.value;
    if (hr < 12) {
        return 'Good Morning';
    } else if (hr < 17) {
        return 'Good Afternoon';
    } else {
        return 'Good Evening';
    }
});

const certModalOpen = ref(false);
const selectedCertEnrollment = ref(null);

const testimonyModalOpen = ref(false);
const selectedTestimonyEnrollment = ref(null);
const testimonyText = ref('');
const submittingTestimony = ref(false);

const openTestimonialModal = (enrollment) => {
    selectedTestimonyEnrollment.value = enrollment;
    testimonyModalOpen.value = true;
};

const closeTestimonyModal = () => {
    testimonyModalOpen.value = false;
    selectedTestimonyEnrollment.value = null;
    testimonyText.value = '';
    submittingTestimony.value = false;
};

const submitTestimony = () => {
    if (!testimonyText.value.trim()) return;
    submittingTestimony.value = true;

    Inertia.post(route('student.testimonials.store'), {
        enrollment_id: selectedTestimonyEnrollment.value.id,
        text: testimonyText.value,
    }, {
        onSuccess: () => {
            closeTestimonyModal();
        },
        onError: () => {
            submittingTestimony.value = false;
        }
    });
};

const viewCertificate = (enrollment) => {
    if (enrollment.enrollment_status === 'completed') {
        alert("This certificate has been collected physically. Digital access is no longer available.");
        return;
    }
    selectedCertEnrollment.value = enrollment;
    certModalOpen.value = true;
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
};
</script>
