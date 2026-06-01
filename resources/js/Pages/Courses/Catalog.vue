<template>
    <div class="min-h-screen bg-gray-50 font-sans text-gray-900 flex flex-col">
        <!-- Navigation -->
        <nav class="bg-[#0a1f44] py-6 shadow-lg">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <img src="/msu-logo-2.png" alt="MSU Logo" class="h-12 w-auto bg-white p-1 rounded shadow-sm" />
                    <div>
                        <h1 class="text-lg font-black text-white tracking-wide">MSUNLI Courses</h1>
                        <p class="text-[0.6rem] text-[#f5c242] uppercase tracking-widest font-bold">Midlands State University</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <a href="/" class="text-sm font-semibold text-gray-200 hover:text-[#f5c242] transition mr-4">Home</a>
                    <a href="/login" class="bg-[#f5c242] hover:bg-yellow-400 text-[#0a1f44] font-bold px-6 py-2.5 rounded-full transition shadow-lg text-sm">
                        Portal Login
                    </a>
                </div>
            </div>
        </nav>

        <!-- Top-level feedback banners for redirect feedback -->
        <div class="max-w-7xl mx-auto px-6 lg:px-8 w-full mt-6 space-y-4" v-if="page?.props?.value?.flash?.success || page?.props?.value?.flash?.error">
            <div v-if="page.props.value.flash.success" class="bg-green-100 border-l-4 border-green-500 text-green-800 p-4 rounded-xl shadow-sm flex items-center justify-between transition-all" role="alert">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2.5 flex-shrink-0 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <div>
                        <span class="font-bold text-xs uppercase tracking-wider block">Submission Success</span>
                        <p class="text-xs font-semibold mt-0.5">{{ page.props.value.flash.success }}</p>
                    </div>
                </div>
                <button @click="page.props.value.flash.success = null" class="text-green-800 hover:text-green-950 font-black text-lg p-1">&times;</button>
            </div>
            
            <div v-if="page.props.value.flash.error" class="bg-red-100 border-l-4 border-red-500 text-red-800 p-4 rounded-xl shadow-sm flex items-center justify-between transition-all" role="alert">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2.5 flex-shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                    <div>
                        <span class="font-bold text-xs uppercase tracking-wider block">Processing Error</span>
                        <p class="text-xs font-semibold mt-0.5">{{ page.props.value.flash.error }}</p>
                    </div>
                </div>
                <button @click="page.props.value.flash.error = null" class="text-red-800 hover:text-red-950 font-black text-lg p-1">&times;</button>
            </div>
        </div>

        <!-- Hero Header -->
        <header class="bg-gradient-to-r from-[#0a1f44] to-[#0c2859] py-16 text-center relative overflow-hidden">
            <div class="absolute inset-0 opacity-10 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-yellow-300 via-transparent to-transparent"></div>
            <div class="max-w-4xl mx-auto px-6 relative z-10 space-y-4">
                <span class="inline-block py-1 px-3.5 rounded-full bg-white/10 text-[#f5c242] text-xs font-bold tracking-widest uppercase mb-2">
                    MSULI Short Courses Platform
                </span>
                <h2 class="text-3xl md:text-5xl font-black text-white leading-tight">Empower Yourself with Language Skills</h2>
                <p class="text-base text-gray-300 max-w-xl mx-auto leading-relaxed">
                    Join our professional short courses in Sign Language, Braille, Swahili, and foreign languages designed for corporate stakeholders, students, and community inclusion.
                </p>
            </div>
        </header>

        <!-- Main Body -->
        <main class="flex-grow max-w-7xl w-full mx-auto px-6 lg:px-8 py-12 grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Sidebar Filters -->
            <aside class="space-y-6">
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-6">
                    <h3 class="font-bold text-brand-blue border-b border-gray-100 pb-3 uppercase text-xs tracking-wider">Filter Programs</h3>
                    
                    <!-- Search Input -->
                    <div>
                        <label class="block text-[0.65rem] font-bold text-gray-500 uppercase tracking-wide mb-1.5">Keyword Search</label>
                        <input v-model="searchQuery" type="text" class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="e.g. Swahili, Sign..." />
                    </div>

                    <!-- Category Selector -->
                    <div>
                        <label class="block text-[0.65rem] font-bold text-gray-500 uppercase tracking-wide mb-1.5">Category</label>
                        <select v-model="selectedCategory" class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm">
                            <option value="all">All Categories</option>
                            <option v-for="cat in uniqueCategories" :key="cat" :value="cat">{{ cat }}</option>
                        </select>
                    </div>

                    <!-- Unit Selector -->
                    <div>
                        <label class="block text-[0.65rem] font-bold text-gray-500 uppercase tracking-wide mb-1.5">Functional Unit</label>
                        <select v-model="selectedUnit" class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm">
                            <option value="all">All Units</option>
                            <option value="LTRDU">LTRDU (Language Technology)</option>
                            <option value="LCSU">LCSU (Consultancy & Services)</option>
                            <option value="SNSU">SNSU (Special Needs)</option>
                            <option value="ILASU">ILASU (International)</option>
                            <option value="AOS">AOS (Admin & Operations Support)</option>
                        </select>
                    </div>
                </div>
            </aside>

            <!-- Courses Grid -->
            <section class="lg:col-span-3 space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-for="course in filteredCourses" :key="course.id" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col hover:shadow-md transition">
                        <div class="p-6 flex-grow space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="px-2.5 py-1 text-[0.65rem] font-black bg-amber-50 text-amber-700 rounded-full uppercase tracking-wider">
                                    {{ course.category }}
                                </span>
                                <span class="text-xs font-bold text-[#f5c242] uppercase tracking-wider">
                                    {{ course.department ? course.department.code : 'General MSULI' }}
                                </span>
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-brand-blue leading-snug">{{ course.title }}</h3>
                                <p class="text-xs text-gray-400 font-medium mt-1">Code: {{ course.code }} | Duration: {{ course.duration_weeks }} Weeks</p>
                            </div>
                            <p class="text-sm text-gray-600 leading-relaxed line-clamp-3">{{ course.description }}</p>

                            <!-- Tuition Block -->
                            <div class="pt-4 border-t border-gray-100 flex justify-between items-center">
                                <div>
                                    <span class="text-[0.65rem] text-gray-400 font-bold uppercase tracking-wider block">Course Tuition Fee</span>
                                    <span class="text-lg font-extrabold text-brand-blue">{{ course.currency }} {{ Number(course.price).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Enrollment Card Footer -->
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col gap-3">
                            <div class="text-xs text-gray-500 font-medium">
                                <span class="font-bold text-brand-blue">Active Sessions:</span>
                                <div class="mt-2 space-y-2">
                                    <div v-for="intake in getCourseIntakes(course.id)" :key="intake.id" class="flex justify-between items-center bg-white p-3 rounded-xl border border-gray-150 shadow-sm">
                                        <div>
                                            <p class="font-bold text-gray-700 text-xs">{{ intake.name }}</p>
                                            <p class="text-[0.65rem] text-gray-400 mt-0.5">Starts: {{ formatDate(intake.start_date) }}</p>
                                        </div>
                                        <button @click="enrollNow(intake)" class="bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold text-[0.7rem] px-3.5 py-1.5 rounded-full transition shadow">
                                            Register & Enroll &rarr;
                                        </button>
                                    </div>
                                    <div v-if="getCourseIntakes(course.id).length === 0" class="text-gray-400 italic text-[0.7rem] py-1">
                                        No active enrollment intakes available at this moment.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="filteredCourses.length === 0" class="col-span-2 py-16 text-center bg-white rounded-2xl border border-gray-150 p-8 shadow-sm">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <h4 class="font-bold text-brand-blue text-base">No Matching Courses Found</h4>
                        <p class="text-xs text-gray-400 mt-1">Try relaxing your search terms or changing your unit/category filters.</p>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="bg-[#0a1f44] text-white py-8 border-t border-brand-blue-light mt-16 text-center text-xs text-gray-400">
            &copy; 2026 Midlands State University. All rights reserved.
        </footer>

        <!-- Registration Intake Modal -->
        <div v-if="enrollModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm overflow-y-auto" @click.self="closeEnrollModal">
            <div class="bg-white rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden my-8">
                <div class="bg-[#0a1f44] text-white px-8 py-5 flex justify-between items-center border-b border-[#f5c242]/20">
                    <div>
                        <h3 class="text-xl font-bold tracking-tight">Short Course Registration Application</h3>
                        <p class="text-xs text-[#f5c242] mt-1">MSU National Language Institute Portal</p>
                    </div>
                    <button @click="closeEnrollModal" class="text-gray-300 hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="submitEnrollment" class="p-8 space-y-6 max-h-[75vh] overflow-y-auto">
                    <div class="bg-gray-50 border border-gray-150 p-4 rounded-xl space-y-1">
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Applying For</p>
                        <p class="text-base font-black text-brand-blue">{{ selectedIntake?.course.title }}</p>
                        <p class="text-xs text-brand-gold-dark font-semibold">Intake Session: {{ selectedIntake?.name }} | Tuition: {{ selectedIntake?.course.currency }} {{ Number(selectedIntake?.course.price).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</p>
                    </div>

                    <!-- Flash messages from validation errors or successes -->
                    <div v-if="flashError" class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl text-xs font-semibold">
                        {{ flashError }}
                    </div>
                    <div v-if="flashSuccess" class="bg-green-50 border border-green-200 text-green-700 p-4 rounded-xl text-xs font-semibold">
                        {{ flashSuccess }}
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Full Name -->
                        <div class="col-span-1 md:col-span-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Full Name *</label>
                            <input v-model="form.full_name" type="text" required class="w-full text-sm rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="e.g. John Doe" />
                        </div>

                        <!-- Email Address -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Email Address *</label>
                            <input v-model="form.email" type="email" required class="w-full text-sm rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="e.g. john.doe@example.com" />
                        </div>

                        <!-- Phone Number -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Phone Number *</label>
                            <input v-model="form.phone" type="text" required class="w-full text-sm rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="e.g. +263 77 123 4567" />
                        </div>

                        <!-- National ID Number -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">National ID Number *</label>
                            <input v-model="form.national_id_number" type="text" required class="w-full text-sm rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="e.g. 12-345678X90" />
                        </div>

                        <!-- National ID Copy (File Upload) -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Upload National ID Copy *</label>
                            <input type="file" @change="handleIdUpload" required class="w-full text-xs text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#0a1f44]/10 file:text-brand-blue hover:file:bg-[#0a1f44]/20" />
                            <span class="text-[10px] text-gray-400 block mt-1">Supported formats: PDF, JPEG, PNG (Max 10MB)</span>
                        </div>

                        <!-- Physical Address -->
                        <div class="col-span-1 md:col-span-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Physical Address *</label>
                            <textarea v-model="form.physical_address" rows="2" required class="w-full text-sm rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="Enter your full home address..."></textarea>
                        </div>

                        <!-- Proof of Payment Upload -->
                        <div class="col-span-1 md:col-span-2 bg-amber-50/50 border border-amber-200/50 rounded-xl p-5 space-y-4">
                            <div>
                                <h4 class="text-xs font-bold text-brand-gold-dark uppercase tracking-wider">Tuition Fees Bank Deposit Details</h4>
                                <p class="text-xs text-gray-600 mt-1 leading-relaxed">
                                    To finalize enrollment, please pay into our official MSULI bank account and upload your transaction receipt confirmation.
                                </p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Upload Payment Proof (JPEG, PNG, PDF) *</label>
                                <input type="file" @change="handlePaymentUpload" required class="w-full text-xs text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#0a1f44]/10 file:text-brand-blue hover:file:bg-[#0a1f44]/20" />
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-150 flex justify-end gap-3 sticky bottom-0 bg-white">
                        <button type="button" @click="closeEnrollModal" class="px-5 py-2.5 rounded-full border border-gray-300 font-semibold text-gray-700 hover:bg-gray-50 transition text-sm">Cancel</button>
                        <button type="submit" :disabled="submitting" class="px-6 py-2.5 rounded-full bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold transition shadow text-sm disabled:opacity-50 flex items-center gap-2">
                            <span v-if="submitting">Submitting Application...</span>
                            <span v-else>Submit Application</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import { usePage } from '@inertiajs/inertia-vue3';

const page = usePage();

const props = defineProps({
    courses: Array,
    intakes: Array,
});

const searchQuery = ref('');
const selectedCategory = ref('all');
const selectedUnit = ref('all');

const uniqueCategories = computed(() => {
    return [...new Set(props.courses.map(c => c.category))];
});

const filteredCourses = computed(() => {
    return props.courses.filter(course => {
        const matchesSearch = course.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            course.code.toLowerCase().includes(searchQuery.value.toLowerCase());
        
        const matchesCategory = selectedCategory.value === 'all' || course.category === selectedCategory.value;
        
        const matchesUnit = selectedUnit.value === 'all' || (course.department && course.department.code === selectedUnit.value);
        
        return matchesSearch && matchesCategory && matchesUnit;
    });
});

const getCourseIntakes = (courseId) => {
    return props.intakes.filter(i => i.course_id === courseId);
};

const enrollModalOpen = ref(false);
const selectedIntake = ref(null);

const form = reactive({
    full_name: '',
    national_id_number: '',
    email: '',
    phone: '',
    physical_address: '',
});

const nationalIdCopyFile = ref(null);
const paymentProofFile = ref(null);
const submitting = ref(false);
const flashError = ref('');
const flashSuccess = ref('');

const enrollNow = (intake) => {
    selectedIntake.value = intake;
    enrollModalOpen.value = true;
};

const closeEnrollModal = () => {
    enrollModalOpen.value = false;
    selectedIntake.value = null;
    paymentProofFile.value = null;
    nationalIdCopyFile.value = null;
    form.full_name = '';
    form.national_id_number = '';
    form.email = '';
    form.phone = '';
    form.physical_address = '';
    flashError.value = '';
    flashSuccess.value = '';
};

const handleIdUpload = (e) => {
    const file = e.target.files[0];
    if (file && file.size > 10 * 1024 * 1024) {
        flashError.value = 'Validation Error: National ID copy file size exceeds the maximum 10MB limit.';
        e.target.value = ''; // Reset input
        nationalIdCopyFile.value = null;
        return;
    }
    nationalIdCopyFile.value = file;
    flashError.value = '';
};

const handlePaymentUpload = (e) => {
    const file = e.target.files[0];
    if (file && file.size > 10 * 1024 * 1024) {
        flashError.value = 'Validation Error: Proof of Payment file size exceeds the maximum 10MB limit.';
        e.target.value = ''; // Reset input
        paymentProofFile.value = null;
        return;
    }
    paymentProofFile.value = file;
    flashError.value = '';
};

const submitEnrollment = () => {
    if (!nationalIdCopyFile.value) {
        flashError.value = 'Please upload a copy of your National ID.';
        return;
    }
    if (!paymentProofFile.value) {
        flashError.value = 'Please upload your proof of payment.';
        return;
    }

    submitting.value = true;
    flashError.value = '';
    flashSuccess.value = '';

    const formData = new FormData();
    formData.append('course_intake_id', selectedIntake.value.id);
    formData.append('full_name', form.full_name);
    formData.append('national_id_number', form.national_id_number);
    formData.append('national_id_copy', nationalIdCopyFile.value);
    formData.append('email', form.email);
    formData.append('phone', form.phone);
    formData.append('physical_address', form.physical_address);
    formData.append('payment_proof', paymentProofFile.value);

    Inertia.post(route('courses.apply'), formData, {
        onSuccess: (page) => {
            submitting.value = false;
            // Success alerts will be shown in the session flash, 
            // but we'll show success here and close the modal after a delay
            flashSuccess.value = 'Application submitted successfully. Your enrollment application has been received and is pending verification.';
            setTimeout(() => {
                closeEnrollModal();
            }, 3500);
        },
        onError: (err) => {
            submitting.value = false;
            if (Object.keys(err).length > 0) {
                // Combine error keys
                flashError.value = Object.values(err).join(' ');
            } else {
                flashError.value = 'An error occurred during submission. Please check all fields.';
            }
        }
    });
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
};
</script>
