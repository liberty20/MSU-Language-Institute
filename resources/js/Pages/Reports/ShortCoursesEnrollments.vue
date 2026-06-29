<template>
    <Head title="Short Courses Enrollments Analytics" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 w-full animate-fade-in-up">
                <div class="flex items-center gap-3">
                    <Link :href="route('reports.index')" 
                          class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Short Courses Enrollments</h1>
                        <p class="text-xs text-slate-500 mt-0.5 font-medium">Drill-down student registry and real-time intake tracking</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="flex items-center bg-white/80 backdrop-blur-md p-1 rounded-2xl border border-slate-200 shadow-sm">
                        <button @click="triggerExport('pdf')" 
                                class="px-4 py-2 rounded-xl text-xs font-black text-slate-600 hover:bg-red-50 hover:text-red-650 transition-all duration-300 flex items-center gap-2 transform active:scale-95">
                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Export PDF
                        </button>
                        <div class="h-4 w-[1px] bg-slate-200"></div>
                        <button @click="triggerExport('excel')" 
                                class="px-4 py-2 rounded-xl text-xs font-black text-slate-600 hover:bg-green-50 hover:text-green-650 transition-all duration-300 flex items-center gap-2 transform active:scale-95">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Export Excel
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <ReportsBgWrapper class="px-6 py-6">
            <div class="w-full space-y-6">
                <!-- Loading Indicator Overlay -->
                <div v-if="isLoading" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/10 backdrop-blur-xs">
                    <div class="bg-white p-6 rounded-3xl border border-slate-250/50 shadow-2xl flex items-center gap-4">
                        <svg class="animate-spin h-6 w-6 text-indigo-650" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-xs font-black text-slate-800">Recalibrating Enrollment Datasets...</span>
                    </div>
                </div>

                <!-- KPI Cards Summary Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5">
                    <!-- Card 1: Total Enrolled -->
                    <div @click="setStatus('all')"
                         :class="[
                             'bg-white border rounded-3xl p-5 shadow-sm relative overflow-hidden group cursor-pointer hover:scale-[1.02] transition-all duration-300',
                             filters.status === 'all' ? 'ring-2 ring-blue-550 border-transparent shadow-md' : 'border-slate-200'
                         ]">
                        <div class="absolute top-0 left-0 w-full h-[3px] bg-blue-500"></div>
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Enrolled</span>
                                <span class="block text-2xl font-black text-slate-800 mt-1">{{ kpis.total }}</span>
                            </div>
                            <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 text-[9px] text-slate-500 font-bold">
                            All students registrations
                        </div>
                    </div>

                    <!-- Card 2: Active Students -->
                    <div @click="setStatus('active')"
                         :class="[
                             'bg-white border rounded-3xl p-5 shadow-sm relative overflow-hidden group cursor-pointer hover:scale-[1.02] transition-all duration-300',
                             filters.status === 'active' ? 'ring-2 ring-emerald-550 border-transparent shadow-md' : 'border-slate-200'
                         ]">
                        <div class="absolute top-0 left-0 w-full h-[3px] bg-emerald-500"></div>
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Active Students</span>
                                <span class="block text-2xl font-black text-slate-800 mt-1">{{ kpis.active }}</span>
                            </div>
                            <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 text-[9px] text-slate-500 font-bold">
                            Currently learning state
                        </div>
                    </div>

                    <!-- Card 3: Pending Review -->
                    <div @click="setStatus('pending')"
                         :class="[
                             'bg-white border rounded-3xl p-5 shadow-sm relative overflow-hidden group cursor-pointer hover:scale-[1.02] transition-all duration-300',
                             filters.status === 'pending' ? 'ring-2 ring-amber-550 border-transparent shadow-md' : 'border-slate-200'
                         ]">
                        <div class="absolute top-0 left-0 w-full h-[3px] bg-amber-500"></div>
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Pending Review</span>
                                <span class="block text-2xl font-black text-slate-800 mt-1">{{ kpis.pending }}</span>
                            </div>
                            <div class="p-2.5 bg-amber-50 text-amber-600 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 text-[9px] text-slate-500 font-bold">
                            Awaiting payment verification
                        </div>
                    </div>

                    <!-- Card 4: Completed -->
                    <div @click="setStatus('completed')"
                         :class="[
                             'bg-white border rounded-3xl p-5 shadow-sm relative overflow-hidden group cursor-pointer hover:scale-[1.02] transition-all duration-300',
                             filters.status === 'completed' ? 'ring-2 ring-indigo-550 border-transparent shadow-md' : 'border-slate-200'
                         ]">
                        <div class="absolute top-0 left-0 w-full h-[3px] bg-indigo-500"></div>
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Completed</span>
                                <span class="block text-2xl font-black text-slate-800 mt-1">{{ kpis.completed }}</span>
                            </div>
                            <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 text-[9px] text-slate-500 font-bold">
                            Graduated and certified
                        </div>
                    </div>

                    <!-- Card 5: Dropped / Withdrawn -->
                    <div @click="setStatus('dropped')"
                         :class="[
                             'bg-white border rounded-3xl p-5 shadow-sm relative overflow-hidden group cursor-pointer hover:scale-[1.02] transition-all duration-300',
                             filters.status === 'dropped' ? 'ring-2 ring-rose-550 border-transparent shadow-md' : 'border-slate-200'
                         ]">
                        <div class="absolute top-0 left-0 w-full h-[3px] bg-rose-500"></div>
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Dropped</span>
                                <span class="block text-2xl font-black text-slate-800 mt-1">{{ kpis.dropped }}</span>
                            </div>
                            <div class="p-2.5 bg-rose-50 text-rose-600 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 text-[9px] text-slate-500 font-bold">
                            Withdrawn registrations
                        </div>
                    </div>
                </div>

                <!-- Filters Panel -->
                <div class="neo-glass-card rounded-3xl p-6 border border-slate-200/80 shadow-sm relative overflow-hidden group bg-white/90">
                    <div class="absolute top-0 left-0 w-full h-[3px] bg-gradient-to-r from-blue-450 via-indigo-600 to-emerald-600"></div>

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6 border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Enrollments Filter Engine</h3>
                                <p class="text-[10px] text-slate-500 font-medium">Filter, search, and paginate through all short course registrations</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                        <!-- Student Name Search -->
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider">Search Student</label>
                            <div class="relative">
                                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <input type="text" v-model="filters.search" @input="debounceSearch" placeholder="Search by student name..."
                                       class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-slate-800 focus:ring-2 focus:ring-blue-500 shadow-xs" />
                            </div>
                        </div>

                        <!-- Course Filter -->
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider">Course</label>
                            <select v-model="filters.course_id" @change="applyFilters"
                                    class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 shadow-sm">
                                <option value="">All Courses</option>
                                <option v-for="course in filterOptions.courses" :key="course.id" :value="course.id">
                                    {{ course.title }} ({{ course.code }})
                                </option>
                            </select>
                        </div>

                        <!-- Instructor Filter -->
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider">Instructor</label>
                            <select v-model="filters.instructor_id" @change="applyFilters"
                                    class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 shadow-sm">
                                <option value="">All Instructors</option>
                                <option v-for="inst in filterOptions.instructors" :key="inst.id" :value="inst.id">
                                    {{ inst.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider">Registration Status</label>
                            <select v-model="filters.status" @change="applyFilters"
                                    class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 shadow-sm">
                                <option value="all">All Registrations</option>
                                <option value="pending">Pending Review</option>
                                <option value="active">Active Students</option>
                                <option value="completed">Completed / Certified</option>
                                <option value="dropped">Dropped / Withdrawn</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end items-center mt-6 pt-5 border-t border-slate-100">
                        <button @click="resetFilters" 
                                class="text-xs font-black text-red-650 hover:text-red-500 transition duration-300 flex items-center gap-1.5 transform active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Reset Filter Options
                        </button>
                    </div>
                </div>

                <!-- Ledger Table Panel -->
                <div class="neo-glass-card rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden bg-white">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-850 uppercase tracking-wider">Student Registry Ledger</h3>
                            <p class="text-[10px] text-slate-500 font-semibold mt-0.5">Real-time student short course registration details</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50 border-b border-slate-100 text-[9px] uppercase tracking-wider text-slate-500 font-black">
                                    <th class="px-6 py-4 cursor-pointer hover:bg-slate-100" @click="sortBy('id')">
                                        Enrollment Ref
                                        <span v-if="filters.sort_by === 'id'">{{ filters.sort_desc === 'true' ? '▼' : '▲' }}</span>
                                    </th>
                                    <th class="px-6 py-4 cursor-pointer hover:bg-slate-100" @click="sortBy('student')">
                                        Student Name
                                        <span v-if="filters.sort_by === 'student'">{{ filters.sort_desc === 'true' ? '▼' : '▲' }}</span>
                                    </th>
                                    <th class="px-6 py-4 cursor-pointer hover:bg-slate-100" @click="sortBy('course')">
                                        Course Details
                                        <span v-if="filters.sort_by === 'course'">{{ filters.sort_desc === 'true' ? '▼' : '▲' }}</span>
                                    </th>
                                    <th class="px-6 py-4">Instructor</th>
                                    <th class="px-6 py-4">MSUNLI Unit</th>
                                    <th class="px-6 py-4">Tuition Paid</th>
                                    <th class="px-6 py-4">Payment</th>
                                    <th class="px-6 py-4 cursor-pointer hover:bg-slate-100" @click="sortBy('enrollment_status')">
                                        Status
                                        <span v-if="filters.sort_by === 'enrollment_status'">{{ filters.sort_desc === 'true' ? '▼' : '▲' }}</span>
                                    </th>
                                    <th class="px-6 py-4 cursor-pointer hover:bg-slate-100" @click="sortBy('created_at')">
                                        Date Enrolled
                                        <span v-if="filters.sort_by === 'created_at'">{{ filters.sort_desc === 'true' ? '▼' : '▲' }}</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs">
                                <tr v-for="enrollment in enrollments.data" :key="enrollment.id" class="hover:bg-slate-50/50 transition duration-150">
                                    <td class="px-6 py-4.5 font-extrabold text-slate-800">
                                        ENR-{{ enrollment.id }}
                                    </td>
                                    <td class="px-6 py-4.5">
                                        <div class="font-bold text-slate-700">{{ enrollment.user ? enrollment.user.name : 'N/A' }}</div>
                                        <div class="text-[10px] text-slate-400 font-medium">{{ enrollment.user ? enrollment.user.email : '' }}</div>
                                    </td>
                                    <td class="px-6 py-4.5">
                                        <div class="font-bold text-slate-800">{{ enrollment.intake && enrollment.intake.course ? enrollment.intake.course.title : 'N/A' }}</div>
                                        <div class="text-[10px] text-indigo-600 font-bold mt-0.5">Batch: {{ enrollment.intake ? enrollment.intake.name : 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4.5 text-slate-500 font-semibold">
                                        {{ enrollment.intake && enrollment.intake.instructor ? enrollment.intake.instructor.name : 'Unassigned' }}
                                    </td>
                                    <td class="px-6 py-4.5 text-slate-400 font-black uppercase text-[9px] tracking-wider">
                                        {{ enrollment.intake && enrollment.intake.course && enrollment.intake.course.department ? enrollment.intake.course.department.code : 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4.5 font-bold text-emerald-650 font-mono">
                                        USD {{ formatNumber(enrollment.amount_paid) }}
                                    </td>
                                    <td class="px-6 py-4.5">
                                        <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider border shadow-xs"
                                              :class="getPaymentBadgeClass(enrollment.payment_status)">
                                            {{ enrollment.payment_status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4.5">
                                        <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider border shadow-xs"
                                              :class="getEnrollmentBadgeClass(enrollment.enrollment_status)">
                                            {{ enrollment.enrollment_status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4.5 text-slate-550 font-semibold">
                                        {{ formatDate(enrollment.created_at) }}
                                    </td>
                                </tr>
                                <tr v-if="!enrollments.data || enrollments.data.length === 0">
                                    <td colspan="9" class="px-6 py-16 text-center text-slate-400 text-xs font-bold bg-slate-50/10">
                                        No student enrollment records match active filters.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginated Footer -->
                    <div v-if="enrollments.links && enrollments.links.length > 3" class="p-6 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-xs text-slate-500 font-semibold">
                            Showing {{ enrollments.from }} to {{ enrollments.to }} of {{ enrollments.total }} students
                        </span>
                        <div class="flex items-center gap-1.5">
                            <button v-for="link in enrollments.links" :key="link.label"
                                    @click="visitLink(link.url)"
                                    :disabled="!link.url || link.active"
                                    v-html="link.label"
                                    :class="[
                                        'px-3 py-1.5 rounded-xl text-xs font-black transition duration-200',
                                        link.active
                                            ? 'bg-[#0a1f44] text-white shadow-sm'
                                            : !link.url
                                                ? 'text-slate-350 cursor-not-allowed'
                                                : 'text-slate-650 hover:bg-slate-100 hover:text-black'
                                    ]"></button>
                        </div>
                    </div>
                </div>
            </div>
        </ReportsBgWrapper>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ReportsBgWrapper from '@/Components/ReportsBgWrapper.vue';
import { Head, Link } from '@inertiajs/inertia-vue3';
import { Inertia } from '@inertiajs/inertia';
import { ref, reactive } from 'vue';

const props = defineProps({
    enrollments: Object,
    kpis: Object,
    filters: Object,
    filterOptions: Object
});

const isLoading = ref(false);

const filters = reactive({
    course_id: props.filters.course_id || '',
    instructor_id: props.filters.instructor_id || '',
    search: props.filters.search || '',
    status: props.filters.status || 'all',
    sort_by: props.filters.sort_by || 'created_at',
    sort_desc: props.filters.sort_desc || 'true'
});

const formatNumber = (num) => {
    return Number(num || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const getPaymentBadgeClass = (status) => {
    switch (status) {
        case 'verified':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'pending':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'failed':
            return 'bg-rose-50 text-rose-700 border-rose-200';
        default:
            return 'bg-slate-50 text-slate-700 border-slate-200';
    }
};

const getEnrollmentBadgeClass = (status) => {
    switch (status) {
        case 'active':
            return 'bg-teal-50 text-teal-700 border-teal-200';
        case 'completed':
            return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'pending':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'dropped':
            return 'bg-rose-50 text-rose-700 border-rose-200';
        default:
            return 'bg-slate-50 text-slate-700 border-slate-200';
    }
};

const applyFilters = () => {
    isLoading.value = true;
    Inertia.get(route('reports.short-courses-enrollments'), filters, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            isLoading.value = false;
        }
    });
};

const setStatus = (statusVal) => {
    filters.status = statusVal;
    applyFilters();
};

const resetFilters = () => {
    filters.course_id = '';
    filters.instructor_id = '';
    filters.search = '';
    filters.status = 'all';
    applyFilters();
};

let searchDebounceTimeout = null;
const debounceSearch = () => {
    clearTimeout(searchDebounceTimeout);
    searchDebounceTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
};

const sortBy = (field) => {
    if (filters.sort_by === field) {
        filters.sort_desc = filters.sort_desc === 'true' ? 'false' : 'true';
    } else {
        filters.sort_by = field;
        filters.sort_desc = 'true';
    }
    applyFilters();
};

const visitLink = (url) => {
    if (!url) return;
    isLoading.value = true;
    Inertia.get(url, {}, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isLoading.value = false;
        }
    });
};

const triggerExport = (formatType) => {
    const url = new URL(route('reports.short-courses-enrollments.export'), window.location.origin);
    url.searchParams.append('format', formatType);
    
    Object.entries(filters).forEach(([key, val]) => {
        if (val) url.searchParams.append(key, val);
    });
    
    window.open(url.toString(), '_blank');
};
</script>
