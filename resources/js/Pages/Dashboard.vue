<template>
    <AuthenticatedLayout>
        <template #header>
            Dashboard
        </template>

        <div class="space-y-8">
            <!-- Unit / Department Banner -->
            <div v-if="stats.department_code" class="bg-gradient-to-r from-[#0a1f44] to-[#0c2859] text-white p-6 rounded-2xl border border-gray-150 shadow-md flex items-center justify-between">
                <div class="space-y-1">
                    <span class="inline-block py-0.5 px-3 rounded-full bg-white/10 text-brand-gold text-xs font-bold tracking-widest uppercase mb-1">
                        Assigned Functional Unit
                    </span>
                    <h3 class="text-xl font-bold">{{ stats.department_name }} ({{ stats.department_code }})</h3>
                    <p class="text-sm text-gray-300">This dashboard is configured to show service requests and operations scoped specifically to your unit.</p>
                </div>
                <div class="w-16 h-16 rounded-full bg-white/10 flex items-center justify-center font-black text-2xl text-brand-gold shadow flex-shrink-0">
                    {{ stats.department_code.slice(0, 2) }}
                </div>
            </div>

            <!-- Stats Row -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Clients (Hidden for clients) -->
                <div v-if="!$page.props.auth.roles.includes('client')" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center gap-4 transition hover:shadow-md">
                    <div class="w-14 h-14 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Total Clients</p>
                        <h3 class="text-2xl font-bold text-brand-blue">{{ stats.total_clients }}</h3>
                    </div>
                </div>

                <!-- Active Requests (Shown for everyone) -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center gap-4 transition hover:shadow-md">
                    <div class="w-14 h-14 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Active Requests</p>
                        <h3 class="text-2xl font-bold text-brand-blue">{{ stats.active_requests }}</h3>
                    </div>
                </div>

                <!-- Pending Tasks / Requests (Shown for everyone) -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center gap-4 transition hover:shadow-md">
                    <div class="w-14 h-14 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">
                            {{ $page.props.auth.roles.includes('client') ? 'Pending Requests' : 'Pending Tasks' }}
                        </p>
                        <h3 class="text-2xl font-bold text-brand-blue">{{ stats.pending_tasks }}</h3>
                    </div>
                </div>

                <!-- Assigned Tasks (Shown for all administrative and staff users) -->
                <div v-if="!$page.props.auth.roles.includes('client')" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center gap-4 transition hover:shadow-md">
                    <div class="w-14 h-14 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Assigned Tasks</p>
                        <h3 class="text-2xl font-bold text-brand-blue">{{ stats.assigned_tasks }}</h3>
                    </div>
                </div>

                <!-- In Progress Requests (Only shown for clients) -->
                <div v-if="$page.props.auth.roles.includes('client')" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center gap-4 transition hover:shadow-md">
                    <div class="w-14 h-14 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">In Progress</p>
                        <h3 class="text-2xl font-bold text-brand-blue">{{ stats.in_progress_requests }}</h3>
                    </div>
                </div>

                <!-- Approved Value (Only shown for clients) -->
                <div v-if="$page.props.auth.roles.includes('client')" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-150 flex items-center gap-4 transition hover:shadow-md">
                    <div class="w-14 h-14 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Approved Value</p>
                        <div v-if="typeof stats.total_revenue === 'object' && stats.total_revenue !== null" class="space-y-0.5">
                            <h3 v-for="(amount, curr) in stats.total_revenue" :key="curr" class="font-bold text-brand-blue leading-tight" style="font-size: 10px;">
                                {{ curr }} {{ Number(amount).toLocaleString(undefined, {minimumFractionDigits: 2}) }}
                            </h3>
                            <h3 v-if="Object.keys(stats.total_revenue).length === 0" class="font-bold text-brand-blue leading-tight" style="font-size: 10px;">
                                USD 0.00
                            </h3>
                        </div>
                        <h3 v-else class="text-2xl font-bold text-brand-blue">
                            ${{ Number(stats.total_revenue).toLocaleString(undefined, {minimumFractionDigits: 2}) }}
                        </h3>
                    </div>
                </div>
            </div>

            <!-- Instructor Assigned Courses Section -->
            <div v-if="stats.assigned_courses_list" class="space-y-6">
                <div class="border-b border-gray-150 pb-4">
                    <h3 class="text-xl font-bold text-[#0a1f44]">My Assigned Courses</h3>
                    <p class="text-sm text-gray-500">Overview of the short course programs allocated to you by the Executive Directorate.</p>
                </div>

                <!-- Instructor Course Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-150 flex items-center gap-4 transition hover:shadow-md">
                        <div class="w-14 h-14 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Total Courses Assigned</p>
                            <h3 class="text-2xl font-bold text-[#0a1f44]">{{ stats.total_courses_assigned }}</h3>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-150 flex items-center gap-4 transition hover:shadow-md">
                        <div class="w-14 h-14 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Total Students Enrolled</p>
                            <h3 class="text-2xl font-bold text-[#0a1f44]">{{ stats.total_students_assigned }}</h3>
                        </div>
                    </div>
                </div>

                <!-- Assigned Courses Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div v-for="course in stats.assigned_courses_list" :key="course.id" class="bg-white p-6 rounded-2xl border border-gray-150 shadow-sm space-y-4 hover:shadow-md transition">
                        <div class="flex justify-between items-center border-b border-gray-50 pb-3">
                            <span class="px-2.5 py-0.5 text-xs font-bold bg-[#0a1f44]/10 text-[#0a1f44] rounded-full">
                                {{ course.course_code }}
                            </span>
                            <span class="text-xs text-gray-400 font-bold uppercase tracking-wider">{{ course.status }}</span>
                        </div>
                        <h4 class="text-base font-black text-gray-800 leading-snug line-clamp-2" :title="course.course_name">{{ course.course_name }}</h4>
                        <div class="pt-3 border-t border-gray-50 flex justify-between items-center text-sm font-bold text-gray-600">
                            <span>Students Assigned:</span>
                            <span class="text-blue-600 font-extrabold text-lg">{{ course.enrolled_students }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Requests Table (Only shown for non-clients) -->
            <div v-if="!$page.props.auth.roles.includes('client')" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="text-lg font-bold text-brand-blue">Recent Service Requests</h3>
                    <Link :href="route('service-requests.index')" class="text-sm text-brand-gold-dark hover:text-brand-gold font-semibold transition">
                        View All &rarr;
                    </Link>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500">
                                <th class="px-6 py-4 font-semibold">Reference</th>
                                <th class="px-6 py-4 font-semibold">Client</th>
                                <th class="px-6 py-4 font-semibold">Service</th>
                                <th class="px-6 py-4 font-semibold">Priority</th>
                                <th class="px-6 py-4 font-semibold">Status</th>
                                <th class="px-6 py-4 font-semibold">Date</th>
                                <th class="px-6 py-4 font-semibold text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="req in recentRequests" :key="req.id" class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 font-medium text-brand-blue">{{ req.reference }}</td>
                                <td class="px-6 py-4 text-gray-700">{{ req.client }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ req.service }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full uppercase tracking-wide"
                                        :class="{
                                            'bg-red-100 text-red-700': req.priority === 'urgent',
                                            'bg-orange-100 text-orange-700': req.priority === 'high',
                                            'bg-yellow-100 text-yellow-700': req.priority === 'medium',
                                            'bg-gray-100 text-gray-700': req.priority === 'low'
                                        }">
                                        {{ req.priority }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full uppercase tracking-wide"
                                        :class="{
                                            'bg-blue-100 text-blue-700': req.status === 'pending' || req.status === 'in_progress',
                                            'bg-green-100 text-green-700': req.status === 'completed' || req.status === 'approved',
                                            'bg-purple-100 text-purple-700': req.status === 'review' || req.status === 'quoted',
                                            'bg-red-100 text-red-700': req.status === 'cancelled'
                                        }">
                                        {{ req.status.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-500 text-sm">{{ req.date }}</td>
                                <td class="px-6 py-4 text-right">
                                    <Link :href="route('service-requests.show', req.id)" class="text-brand-gold-dark hover:text-brand-blue font-semibold text-sm transition">
                                        View
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="recentRequests.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    No recent service requests found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Activities Audit Feed (Only shown for authorized roles) -->
            <div v-if="['ict_administrator', 'executive_director', 'deputy_director', 'admin_assistant'].some(r => $page.props.auth.roles.includes(r))" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col h-[28rem]">
                <div class="border-b border-gray-50 pb-3 flex justify-between items-center mb-4">
                    <h4 class="text-sm font-black text-brand-blue uppercase tracking-wider">Recent Activity Audit Trail</h4>
                    <Link :href="route('admin.audit-trail')" class="text-xs font-black text-brand-gold-dark hover:underline uppercase tracking-wider">
                        View Full Audit Trail &rarr;
                    </Link>
                </div>
                <div class="flex-grow overflow-y-auto space-y-3 pr-1">
                    <div v-for="log in recentActivities" :key="log.id" class="p-3.5 hover:bg-gray-50 transition border border-gray-50 rounded-xl flex gap-3 relative">
                        <div class="flex-1 min-w-0 space-y-1">
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] font-bold text-brand-blue uppercase tracking-wide">{{ log.action }}</span>
                                <span class="text-[8px] text-gray-400 font-semibold">{{ log.created_at }}</span>
                            </div>
                            <p class="text-xs text-gray-650 leading-snug line-clamp-2" :title="log.description">{{ log.description }}</p>
                        </div>
                    </div>
                    <div v-if="recentActivities.length === 0" class="flex flex-col items-center justify-center h-48 text-center text-xs text-gray-400 italic">
                        No system activities logged yet.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/inertia-vue3';

defineProps({
    stats: Object,
    recentRequests: Array,
    recentActivities: Array
});
</script>
