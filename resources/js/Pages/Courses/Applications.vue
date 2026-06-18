<template>
    <Head title="Course Applications Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <span class="font-extrabold text-[#0a1f44]">Course Registration Applications</span>
                <span class="text-xs text-gray-500 font-medium">Short Courses Portal</span>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Header banner with premium aesthetics -->
            <div class="bg-gradient-to-r from-[#0a1f44] to-[#0d2859] p-8 rounded-2xl border border-[#f5c242]/10 shadow-lg text-white relative overflow-hidden">
                <div class="absolute right-0 top-0 translate-x-10 -translate-y-10 opacity-10 bg-yellow-400 w-40 h-40 rounded-full blur-3xl"></div>
                <div class="relative z-10 space-y-2">
                    <span class="text-xs text-[#f5c242] font-black uppercase tracking-widest bg-white/10 px-3 py-1 rounded-full">Review Dashboard</span>
                    <h3 class="text-2xl font-black leading-tight">Short Course Verification & Approvals</h3>
                    <p class="text-sm text-gray-300 max-w-xl">
                        Verify student registration fields, review National ID copies and Proof of Payments, and step through the multi-role structured validation pipeline.
                    </p>
                </div>
            </div>

            <!-- Tabs and Filters Grid -->
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
                <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
                    <!-- Status Filter Tabs -->
                    <div class="flex flex-wrap gap-1.5 bg-gray-50 p-1.5 rounded-xl border border-gray-150">
                        <button v-for="tab in tabs" :key="tab.value"
                                @click="filterByStatus(tab.value)"
                                class="px-4 py-2 text-xs font-bold rounded-lg transition-all"
                                :class="currentStatus === tab.value 
                                    ? 'bg-[#0a1f44] text-[#f5c242] shadow-sm' 
                                    : 'text-gray-600 hover:bg-gray-100 hover:text-brand-blue'">
                            {{ tab.label }}
                        </button>
                    </div>

                    <!-- Dropdown Filters for Course & Instructor -->
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Course:</label>
                            <select v-model="currentCourse" @change="filterByCourse"
                                    class="border-gray-200 rounded-lg text-xs font-semibold focus:border-[#0a1f44] focus:ring-[#0a1f44] py-1.5 min-w-[150px]">
                                <option value="">All Courses</option>
                                <option v-for="c in courses" :key="c.id" :value="c.id">
                                    {{ c.code }} — {{ c.title }}
                                </option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2">
                            <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Instructor:</label>
                            <select v-model="currentInstructor" @change="filterByInstructor"
                                    class="border-gray-200 rounded-lg text-xs font-semibold focus:border-[#0a1f44] focus:ring-[#0a1f44] py-1.5 min-w-[150px]">
                                <option value="">All Instructors</option>
                                <option v-for="inst in instructors" :key="inst.id" :value="inst.id">
                                    {{ inst.name }}
                                </option>
                            </select>
                        </div>

                        <button @click="resetFilters" v-if="currentCourse || currentInstructor || currentStatus !== 'all'"
                                class="text-xs font-bold text-red-500 hover:text-red-750 flex items-center gap-1 transition">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- Applications Table -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-4 font-semibold">Applicant Name</th>
                                <th class="px-6 py-4 font-semibold">Intake Session</th>
                                <th class="px-6 py-4 font-semibold">National ID</th>
                                <th class="px-6 py-4 font-semibold">Date Submitted</th>
                                <th class="px-6 py-4 font-semibold">Workflow Status</th>
                                <th class="px-6 py-4 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <tr v-for="app in applications.data" :key="app.id" class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-brand-blue">{{ app.full_name }}</div>
                                    <div class="text-xs text-gray-400 mt-0.5">{{ app.email }} | {{ app.phone }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-gray-700">{{ app.intake?.course?.title }}</div>
                                    <div class="text-xs text-gray-400 mt-0.5">Batch: {{ app.intake?.name }}</div>
                                </td>
                                <td class="px-6 py-4 text-xs font-semibold text-gray-600">
                                    {{ app.national_id_number }}
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500">
                                    {{ formatDate(app.created_at) }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 text-xs font-black rounded-full uppercase tracking-wider block w-fit shadow-sm"
                                        :class="statusBadgeClass(app.status)">
                                        {{ statusLabel(app.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <Link :href="route('course-applications.show', app.id)" 
                                          class="bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold text-xs px-4 py-2 rounded-full transition shadow inline-flex items-center gap-1">
                                        <span>View & Review</span>
                                        <span>&rarr;</span>
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!applications.data || applications.data.length === 0">
                                <td colspan="6" class="px-6 py-16 text-center text-gray-400 italic">
                                    No applications found matching the selected status.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination footer -->
                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between" v-if="applications.links && applications.links.length > 3">
                    <span class="text-sm text-gray-500">Showing {{ applications.from || 0 }} to {{ applications.to || 0 }} of {{ applications.total || 0 }}</span>
                    <div class="flex gap-1">
                        <Link v-for="(link, k) in applications.links" :key="k"
                              :href="link.url || '#'"
                              class="px-3 py-1 rounded border text-sm transition"
                              :class="link.active ? 'bg-[#0a1f44] border-[#0a1f44] text-white' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
                              v-html="link.label">
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/inertia-vue3';
import { ref } from 'vue';
import { Inertia } from '@inertiajs/inertia';

const props = defineProps({
    applications: Object,
    courses: Array,
    instructors: Array,
    filters: Object,
});

const currentStatus = ref(props.filters.status || 'all');
const currentCourse = ref(props.filters.course_id || '');
const currentInstructor = ref(props.filters.instructor_id || '');

const tabs = [
    { value: 'all', label: 'All Applications' },
    { value: 'pending', label: 'Pending Verification' },
    { value: 'verified', label: 'Verified' },
    { value: 'recommended', label: 'Recommended' },
    { value: 'enrolled', label: 'Approved & Enrolled' },
    { value: 'rejected', label: 'Rejected' },
];

const applyFilters = () => {
    const query = {};
    if (currentStatus.value !== 'all') {
        query.status = currentStatus.value;
    }
    if (currentCourse.value) {
        query.course_id = currentCourse.value;
    }
    if (currentInstructor.value) {
        query.instructor_id = currentInstructor.value;
    }
    Inertia.get(route('course-applications.index'), query, {
        preserveState: true,
        replace: true,
    });
};

const filterByStatus = (statusVal) => {
    currentStatus.value = statusVal;
    applyFilters();
};

const filterByCourse = () => {
    applyFilters();
};

const filterByInstructor = () => {
    applyFilters();
};

const resetFilters = () => {
    currentStatus.value = 'all';
    currentCourse.value = '';
    currentInstructor.value = '';
    applyFilters();
};

const statusBadgeClass = (status) => {
    switch (status) {
        case 'approved':
        case 'enrolled':
            return 'bg-green-50 text-green-700 border border-green-200';
        case 'verified':
            return 'bg-amber-50 text-amber-700 border border-amber-200';
        case 'recommended':
            return 'bg-purple-50 text-purple-700 border border-purple-200';
        case 'rejected':
            return 'bg-red-50 text-red-700 border border-red-200';
        case 'pending':
        default:
            return 'bg-blue-50 text-blue-700 border border-blue-200';
    }
};

const statusLabel = (status) => {
    switch (status) {
        case 'approved':
        case 'enrolled':
            return 'Approved & Enrolled';
        case 'verified':
            return 'Verified (Admin)';
        case 'recommended':
            return 'Recommended (DD)';
        case 'rejected':
            return 'Rejected';
        case 'pending':
        default:
            return 'Pending Verification';
    }
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>
