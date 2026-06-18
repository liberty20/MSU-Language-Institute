<template>
    <AuthenticatedLayout>
        <template #header>
            Short Course Enrollments & Performance
        </template>

        <div class="space-y-8 pb-12">
            <!-- Header Operations Banner -->
            <div class="bg-gradient-to-r from-[#0a1f44] to-[#143264] text-white p-8 rounded-3xl shadow-lg border-b-4 border-[#f5c242] flex flex-col md:flex-row md:items-center justify-between gap-6 transition-all">
                <div class="space-y-2">
                    <h2 class="text-2xl font-extrabold tracking-tight">Academic & Enrollment Operations</h2>
                    <p class="text-blue-100 text-sm max-w-2xl font-light">Monitor student enrollments, instructor assignments, pass rate metrics, and manage payment verification and certification across all active short courses.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button v-if="['ict_administrator', 'admin_assistant'].some(r => $page.props.auth.roles.includes(r))" 
                            @click="openManualEnrollModal" 
                            class="px-5 py-2.5 bg-[#f5c242] hover:bg-yellow-500 text-[#0a1f44] rounded-xl transition font-extrabold text-sm flex items-center gap-2 shadow-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        Manual Enroll Student
                    </button>
                    <button @click="showFilters = !showFilters" class="px-5 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl transition font-semibold text-sm flex items-center gap-2 border border-white/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 8.293A1 1 0 013 7.586V4z"/></svg>
                        {{ showFilters ? 'Hide Filters' : 'Show Filters' }}
                    </button>
                </div>
            </div>

            <!-- Dashboard Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6">
                <!-- Card 1: Total Courses -->
                <div class="bg-white p-6 rounded-2xl border-l-4 border-indigo-500 border-y border-r border-gray-100 shadow-sm hover:shadow-md transition duration-300 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wide">Total Courses</div>
                        <div class="flex items-baseline gap-2 mt-2">
                            <span class="text-3xl font-black text-[#0a1f44]">{{ summaryCards.total_courses }}</span>
                            <span class="text-xs font-semibold text-gray-500">Unique</span>
                        </div>
                    </div>
                    <div class="p-3 bg-indigo-50 rounded-xl">
                        <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </div>
                </div>

                <!-- Card 2: Total Enrolled -->
                <div class="bg-white p-6 rounded-2xl border-l-4 border-[#0a1f44] border-y border-r border-gray-100 shadow-sm hover:shadow-md transition duration-300 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wide">Enrolled Students</div>
                        <div class="flex items-baseline gap-2 mt-2">
                            <span class="text-3xl font-black text-[#0a1f44]">{{ summaryCards.total_enrolled_students }}</span>
                            <span class="text-xs font-semibold text-gray-500">Total</span>
                        </div>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-xl">
                        <svg class="w-6 h-6 text-[#0a1f44]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                </div>

                <!-- Card 3: Assigned Instructors -->
                <div class="bg-white p-6 rounded-2xl border-l-4 border-amber-500 border-y border-r border-gray-100 shadow-sm hover:shadow-md transition duration-300 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wide">Instructors</div>
                        <div class="flex items-baseline gap-2 mt-2">
                            <span class="text-3xl font-black text-[#0a1f44]">{{ summaryCards.total_assigned_instructors }}</span>
                            <span class="text-xs font-semibold text-gray-500">Assigned</span>
                        </div>
                    </div>
                    <div class="p-3 bg-amber-50 rounded-xl">
                        <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                </div>

                <!-- Card 4: Overall Pass Rate -->
                <div class="bg-white p-6 rounded-2xl border-l-4 border-emerald-500 border-y border-r border-gray-100 shadow-sm hover:shadow-md transition duration-300 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wide">Overall Pass Rate</div>
                        <div class="flex items-baseline gap-2 mt-2">
                            <span class="text-3xl font-black text-[#0a1f44]" :class="{
                                'text-green-600': summaryCards.overall_pass_rate >= 75,
                                'text-amber-600': summaryCards.overall_pass_rate >= 50 && summaryCards.overall_pass_rate < 75,
                                'text-red-600': summaryCards.overall_pass_rate < 50
                            }">{{ summaryCards.overall_pass_rate }}%</span>
                        </div>
                    </div>
                    <div class="p-3 bg-emerald-50 rounded-xl">
                        <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2"/></svg>
                    </div>
                </div>

                <!-- Card 5: Courses In Progress -->
                <div class="bg-white p-6 rounded-2xl border-l-4 border-blue-500 border-y border-r border-gray-100 shadow-sm hover:shadow-md transition duration-300 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wide">In Progress</div>
                        <div class="flex items-baseline gap-2 mt-2">
                            <span class="text-3xl font-black text-[#0a1f44]">{{ summaryCards.courses_in_progress }}</span>
                            <span class="text-xs font-semibold text-gray-500">Ongoing</span>
                        </div>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-xl">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>

                <!-- Card 6: Completed Courses -->
                <div class="bg-white p-6 rounded-2xl border-l-4 border-teal-500 border-y border-r border-gray-100 shadow-sm hover:shadow-md transition duration-300 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wide">Completed</div>
                        <div class="flex items-baseline gap-2 mt-2">
                            <span class="text-3xl font-black text-[#0a1f44]">{{ summaryCards.completed_courses }}</span>
                            <span class="text-xs font-semibold text-gray-500">Batches</span>
                        </div>
                    </div>
                    <div class="p-3 bg-teal-50 rounded-xl">
                        <svg class="w-6 h-6 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>

            <!-- Filters & Search Panel -->
            <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 -translate-y-4" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-4">
                <div v-show="showFilters" class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-6">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <h4 class="text-sm font-bold text-[#0a1f44] uppercase tracking-wide">Filter Analytics Scope</h4>
                        <button @click="resetFilters" class="text-xs font-semibold text-red-500 hover:text-red-700 transition">Reset All Filters</button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-5">
                        <!-- Course Search -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Course Name / Code</label>
                            <input v-model="filterForm.course" @input="applyFilters" type="text" placeholder="e.g. ZSL-101" class="w-full text-xs rounded-xl border-gray-200 shadow-sm focus:border-brand-gold focus:ring-[#0a1f44] focus:ring-1" />
                        </div>

                        <!-- Instructor Search -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Instructor Name</label>
                            <input v-model="filterForm.instructor" @input="applyFilters" type="text" placeholder="Search instructor..." class="w-full text-xs rounded-xl border-gray-200 shadow-sm focus:border-brand-gold focus:ring-[#0a1f44] focus:ring-1" />
                        </div>

                        <!-- Unit Dropdown -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Unit / Department</label>
                            <select v-model="filterForm.unit" @change="applyFilters" class="w-full text-xs rounded-xl border-gray-200 shadow-sm focus:border-brand-gold focus:ring-[#0a1f44] focus:ring-1">
                                <option value="">All Units</option>
                                <option v-for="dept in departments" :key="dept.id" :value="dept.code">{{ dept.name }} ({{ dept.code }})</option>
                            </select>
                        </div>

                        <!-- Intake Name Search -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Intake Batch</label>
                            <input v-model="filterForm.intake" @input="applyFilters" type="text" placeholder="e.g. Winter 2026" class="w-full text-xs rounded-xl border-gray-200 shadow-sm focus:border-brand-gold focus:ring-[#0a1f44] focus:ring-1" />
                        </div>

                        <!-- Course Status -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Status</label>
                            <select v-model="filterForm.status" @change="applyFilters" class="w-full text-xs rounded-xl border-gray-200 shadow-sm focus:border-brand-gold focus:ring-[#0a1f44] focus:ring-1">
                                <option value="">All Statuses</option>
                                <option value="open">Open</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="completed">Completed</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>

                        <!-- Pass Rate Minimum -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Min Pass Rate (%)</label>
                            <input v-model="filterForm.min_pass_rate" @input="applyFilters" type="number" min="0" max="100" class="w-full text-xs rounded-xl border-gray-200 shadow-sm focus:border-brand-gold focus:ring-[#0a1f44] focus:ring-1" />
                        </div>

                        <!-- Pass Rate Maximum -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Max Pass Rate (%)</label>
                            <input v-model="filterForm.max_pass_rate" @input="applyFilters" type="number" min="0" max="100" class="w-full text-xs rounded-xl border-gray-200 shadow-sm focus:border-brand-gold focus:ring-[#0a1f44] focus:ring-1" />
                        </div>

                        <!-- Start Date Min -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Scheduled From</label>
                            <input v-model="filterForm.start_date" @change="applyFilters" type="date" class="w-full text-xs rounded-xl border-gray-200 shadow-sm focus:border-brand-gold focus:ring-[#0a1f44] focus:ring-1" />
                        </div>

                        <!-- End Date Max -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Scheduled To</label>
                            <input v-model="filterForm.end_date" @change="applyFilters" type="date" class="w-full text-xs rounded-xl border-gray-200 shadow-sm focus:border-brand-gold focus:ring-[#0a1f44] focus:ring-1" />
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Export Tools Toolbar -->
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="space-y-1">
                    <h3 class="text-sm font-bold text-[#0a1f44] uppercase tracking-wide">Management Export Console</h3>
                    <p class="text-xs text-gray-500">Download formatted operational spreadsheets, instructor lists, and pass rate summaries.</p>
                </div>
                <div class="flex flex-wrap items-center gap-4">
                    <!-- Report Selection -->
                    <div>
                        <select v-model="exportForm.report_type" class="text-xs rounded-xl border-gray-200 shadow-sm focus:ring-[#0a1f44] focus:ring-1">
                            <option value="enrollment">Enrollment Report</option>
                            <option value="instructor_allocation">Instructor Allocation Report</option>
                            <option value="course_performance">Course Performance Report</option>
                            <option value="pass_rate_analysis">Pass Rate Analysis Report</option>
                        </select>
                    </div>

                    <!-- Format Selection -->
                    <div>
                        <select v-model="exportForm.format" class="text-xs rounded-xl border-gray-200 shadow-sm focus:ring-[#0a1f44] focus:ring-1">
                            <option value="pdf">PDF Download</option>
                            <option value="csv">CSV Sheet</option>
                            <option value="excel">Excel Sheet</option>
                        </select>
                    </div>

                    <!-- Export Trigger -->
                    <button @click="triggerExport" class="px-5 py-2 bg-[#f5c242] hover:bg-[#e0b034] text-[#0a1f44] rounded-xl transition font-extrabold text-xs tracking-wider uppercase shadow-sm">
                        Export Report
                    </button>
                </div>
            </div>

            <!-- Course Intakes List (Expandable Cards) -->
            <div class="space-y-6">
                <div v-for="intake in intakes" :key="intake.id" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden transition hover:shadow-md">
                    <!-- Card Header / Summarized Details -->
                    <div @click="toggleIntake(intake.id)" class="p-6 bg-gray-50/50 hover:bg-gray-50/80 transition cursor-pointer flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-100">
                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-base font-extrabold text-[#0a1f44]">{{ intake.course_name }}</h3>
                                <span class="px-2.5 py-0.5 text-[10px] font-black text-gray-500 bg-gray-100 rounded-md border border-gray-200 tracking-wider">CODE: {{ intake.course_code }}</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
                                <span>Batch: <strong>{{ intake.intake_name }}</strong></span>
                                <span class="hidden md:inline text-gray-300">|</span>
                                <span>Timeline: <strong>{{ formatDate(intake.start_date) }}</strong> to <strong>{{ formatDate(intake.end_date) }}</strong></span>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 self-end md:self-center">
                            <!-- Status Badge -->
                            <span class="px-3 py-1 text-[10px] font-black tracking-wider uppercase rounded-full" :class="{
                                'bg-teal-50 text-teal-700 border border-teal-200': intake.course_status === 'Open',
                                'bg-blue-50 text-blue-700 border border-blue-200': intake.course_status === 'Ongoing',
                                'bg-green-50 text-green-700 border border-green-200': intake.course_status === 'Completed',
                                'bg-gray-50 text-gray-700 border border-gray-200': intake.course_status === 'Closed' || intake.course_status === 'Draft'
                            }">
                                {{ intake.course_status }}
                            </span>
                            
                            <!-- Dropdown Chevron Icon -->
                            <svg class="w-5 h-5 text-gray-400 transform transition-transform duration-200" :class="{ 'rotate-180': expandedIntakes[intake.id] }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>

                    <!-- Card Body Analytics Grid -->
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 border-b border-gray-100 bg-white">
                        <!-- Instructor Info Box -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Assigned Instructor</h4>
                            <div v-if="intake.instructor_info" class="space-y-1 bg-gray-50 p-4 rounded-xl border border-gray-150">
                                <div class="font-extrabold text-[#0a1f44] text-sm">{{ intake.instructor_info.name }}</div>
                                <div class="text-[11px] font-bold text-gray-500">{{ intake.instructor_info.role }}</div>
                                <div class="text-[10px] text-gray-400 mt-1 uppercase font-semibold">{{ intake.instructor_info.unit }}</div>
                                <div v-if="intake.instructor_info.email" class="text-[10px] text-gray-550 mt-1.5 flex items-center gap-1 border-t border-gray-200 pt-1.5">
                                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    <span class="truncate">{{ intake.instructor_info.email }}</span>
                                </div>
                            </div>
                            <div v-else class="text-xs text-gray-400 italic py-4">No instructor assigned.</div>
                        </div>

                        <!-- Enrollment Stats Box -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Student Enrollments</h4>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-gray-50 p-2.5 rounded-lg border border-gray-150 text-center">
                                    <div class="text-[10px] font-bold text-gray-400 uppercase">Enrolled</div>
                                    <div class="text-lg font-black text-slate-800">{{ intake.total_enrolled }}</div>
                                </div>
                                <div class="bg-teal-50/30 p-2.5 rounded-lg border border-teal-100 text-center">
                                    <div class="text-[10px] font-bold text-teal-600 uppercase">Active</div>
                                    <div class="text-lg font-black text-teal-800">{{ intake.active_students }}</div>
                                </div>
                                <div class="bg-green-50/30 p-2.5 rounded-lg border border-green-100 text-center">
                                    <div class="text-[10px] font-bold text-green-600 uppercase">Completed</div>
                                    <div class="text-lg font-black text-green-800">{{ intake.completed_students }}</div>
                                </div>
                                <div class="bg-red-50/30 p-2.5 rounded-lg border border-red-100 text-center">
                                    <div class="text-[10px] font-bold text-red-500 uppercase">Withdrawn</div>
                                    <div class="text-lg font-black text-red-800">{{ intake.withdrawn_students }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Pass Rate Analytics Box -->
                        <div class="space-y-3 md:col-span-2">
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Pass Rate Analytics</h4>
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-150 flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div class="space-y-1.5 flex-1">
                                    <!-- Progress Bar -->
                                    <div class="flex items-center justify-between text-xs font-bold mb-1">
                                        <span class="text-gray-500 uppercase">Academic Pass Rate</span>
                                        <span class="text-sm font-black text-[#0a1f44]">{{ intake.pass_rate }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 h-3 rounded-full overflow-hidden shadow-inner">
                                        <div class="h-full rounded-full transition-all duration-500" :class="{
                                            'bg-gradient-to-r from-emerald-400 to-green-600 shadow-[0_0_8px_rgba(16,185,129,0.35)]': intake.performance_rating === 'Excellent',
                                            'bg-gradient-to-r from-blue-400 to-indigo-600 shadow-[0_0_8px_rgba(59,130,246,0.35)]': intake.performance_rating === 'Good',
                                            'bg-gradient-to-r from-amber-400 to-orange-500 shadow-[0_0_8px_rgba(245,158,11,0.35)]': intake.performance_rating === 'Fair',
                                            'bg-gradient-to-r from-red-500 to-rose-600 shadow-[0_0_8px_rgba(239,68,68,0.45)]': intake.performance_rating === 'Needs Attention'
                                        }" :style="{ width: intake.pass_rate + '%' }"></div>
                                    </div>
                                    <!-- Rating Indicator -->
                                    <div class="flex items-center gap-2 mt-2">
                                        <span class="inline-block w-2.5 h-2.5 rounded-full animate-pulse" :class="{
                                            'bg-emerald-500': intake.performance_rating === 'Excellent',
                                            'bg-blue-500': intake.performance_rating === 'Good',
                                            'bg-amber-500': intake.performance_rating === 'Fair',
                                            'bg-red-550': intake.performance_rating === 'Needs Attention'
                                        }"></span>
                                        <span class="text-[10px] font-extrabold uppercase px-2.5 py-0.5 rounded-md tracking-wider animate-scaleUp" :class="{
                                            'bg-emerald-55 text-emerald-800 border border-emerald-200': intake.performance_rating === 'Excellent',
                                            'bg-blue-55 text-blue-800 border border-blue-200': intake.performance_rating === 'Good',
                                            'bg-amber-55 text-amber-800 border border-amber-200': intake.performance_rating === 'Fair',
                                            'bg-rose-55 text-rose-800 border border-rose-200': intake.performance_rating === 'Needs Attention'
                                        }">
                                            {{ intake.performance_rating }}
                                        </span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-2.5 text-center min-w-[200px]">
                                    <div class="p-1.5 bg-white rounded-lg border border-gray-150 shadow-sm">
                                        <div class="text-[9px] font-bold text-gray-400 uppercase">Assessed</div>
                                        <div class="text-sm font-black text-[#0a1f44]">{{ intake.students_assessed }}</div>
                                    </div>
                                    <div class="p-1.5 bg-white rounded-lg border border-gray-150 shadow-sm">
                                        <div class="text-[9px] font-bold text-emerald-600 uppercase">Passed</div>
                                        <div class="text-sm font-black text-emerald-700">{{ intake.students_passed }}</div>
                                    </div>
                                    <div class="p-1.5 bg-white rounded-lg border border-gray-150 shadow-sm">
                                        <div class="text-[9px] font-bold text-rose-500 uppercase">Failed</div>
                                        <div class="text-sm font-black text-rose-700">{{ intake.students_failed }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nested Expandable Student List -->
                    <transition enter-active-class="transition duration-150 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-100 ease-in" leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
                        <div v-show="expandedIntakes[intake.id]" class="bg-slate-50/50 p-6 border-t border-gray-100">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                                <h4 class="text-xs font-black text-[#0a1f44] uppercase tracking-wider flex items-center gap-2">
                                    <svg class="w-4 h-4 text-[#f5c242]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                    Enrolled Student Registry ({{ intake.students.length }} Students)
                                </h4>
                                <div class="flex items-center gap-2" v-if="intake.students.length > 0">
                                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">Export Class List:</span>
                                    <button @mousedown="exportClassList(intake.id, 'csv')" class="px-3 py-1 bg-white hover:bg-gray-150 text-[#0a1f44] border border-gray-200 rounded-lg text-[10px] font-bold uppercase transition">CSV</button>
                                    <button @mousedown="exportClassList(intake.id, 'excel')" class="px-3 py-1 bg-white hover:bg-gray-150 text-[#0a1f44] border border-gray-200 rounded-lg text-[10px] font-bold uppercase transition">Excel</button>
                                    <button @mousedown="exportClassList(intake.id, 'pdf')" class="px-3 py-1 bg-white hover:bg-gray-150 text-[#0a1f44] border border-gray-200 rounded-lg text-[10px] font-bold uppercase transition">PDF</button>
                                </div>
                            </div>

                            <div class="bg-white rounded-xl border border-gray-150 overflow-hidden shadow-sm animate-scaleUp">
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left border-collapse text-xs">
                                        <thead>
                                            <tr class="bg-[#0a1f44] text-white font-extrabold uppercase tracking-wider text-[10px] border-b border-[#0a1f44]">
                                                <th class="px-6 py-4">Student Details</th>
                                                <th class="px-6 py-4">Registration Number</th>
                                                <th class="px-6 py-4">Enrollment Date</th>
                                                <th class="px-6 py-4 text-center">Avg Mark</th>
                                                <th class="px-6 py-4 text-center">Academic Status</th>
                                                <th class="px-6 py-4">Enrollment Status</th>
                                                <th class="px-6 py-4">Payment Verification</th>
                                                <th class="px-6 py-4 text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <tr v-for="student in intake.students" :key="student.id" class="hover:bg-gray-50/80 transition">
                                                <!-- Student details -->
                                                <td class="px-6 py-4">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-8 h-8 rounded-full bg-[#0a1f44]/5 text-[#0a1f44] font-black text-xs flex items-center justify-center border border-[#0a1f44]/15 uppercase">
                                                            {{ student.name.charAt(0) }}
                                                        </div>
                                                        <div>
                                                            <div class="font-extrabold text-[#0a1f44] text-sm">{{ student.name }}</div>
                                                            <div class="text-[10px] text-gray-450 font-semibold mt-0.5">{{ student.email }}</div>
                                                        </div>
                                                    </div>
                                                </td>

                                                <!-- Registration Number -->
                                                <td class="px-6 py-4">
                                                    <span class="px-2.5 py-1 bg-gray-50 rounded-md border border-gray-200 font-mono text-[11px] font-bold text-gray-650 shadow-sm">
                                                        {{ student.reg_number }}
                                                    </span>
                                                </td>

                                                <!-- Enrollment Date -->
                                                <td class="px-6 py-4 text-gray-600 font-semibold">
                                                    <div class="flex items-center gap-1.5">
                                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                        <span>{{ formatDate(student.enrollment_date) }}</span>
                                                    </div>
                                                </td>

                                                <!-- Average Mark -->
                                                <td class="px-6 py-4 text-center">
                                                    <span v-if="student.average_mark !== null" class="inline-flex items-center justify-center font-black text-sm px-2.5 py-0.5 rounded shadow-sm" :class="{
                                                        'text-emerald-700 bg-emerald-50 border border-emerald-200': student.average_mark >= 50,
                                                        'text-rose-700 bg-rose-50 border border-rose-200': student.average_mark < 50
                                                    }">
                                                        {{ student.average_mark }}%
                                                    </span>
                                                    <span v-else class="text-gray-400 italic font-semibold">N/A</span>
                                                </td>

                                                <!-- Pass/Fail Result -->
                                                <td class="px-6 py-4 text-center">
                                                    <span v-if="student.pass_fail_status === 'Pass'" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded border border-emerald-200 bg-emerald-50 text-emerald-700 font-black text-[10px] uppercase shadow-sm">
                                                        <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                        {{ student.pass_fail_status }}
                                                    </span>
                                                    <span v-else-if="student.pass_fail_status === 'Fail'" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded border border-rose-200 bg-rose-50 text-rose-700 font-black text-[10px] uppercase shadow-sm">
                                                        <svg class="w-3 h-3 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                                        {{ student.pass_fail_status }}
                                                    </span>
                                                    <span v-else class="text-gray-400 font-semibold">N/A</span>
                                                </td>

                                                <!-- Enrollment Status -->
                                                <td class="px-6 py-4">
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider border shadow-sm" :class="{
                                                        'bg-emerald-50 text-emerald-800 border-emerald-200': student.status === 'active',
                                                        'bg-blue-50 text-blue-800 border-blue-200': student.status === 'completed',
                                                        'bg-amber-50 text-amber-800 border-amber-200': student.status === 'pending',
                                                        'bg-rose-50 text-rose-850 border-rose-200': student.status === 'dropped'
                                                    }">
                                                        <span class="w-1.5 h-1.5 rounded-full" :class="{
                                                            'bg-emerald-600': student.status === 'active',
                                                            'bg-blue-600': student.status === 'completed',
                                                            'bg-amber-500': student.status === 'pending',
                                                            'bg-rose-600': student.status === 'dropped'
                                                        }"></span>
                                                        {{ student.status }}
                                                    </span>
                                                </td>

                                                <!-- Payment Status & Proof -->
                                                <td class="px-6 py-4">
                                                    <div class="flex flex-col gap-1 items-start">
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider border shadow-sm" :class="{
                                                            'bg-emerald-55 text-emerald-800 border-emerald-200': student.payment_status === 'verified',
                                                            'bg-amber-55 text-amber-850 border-amber-200': student.payment_status === 'pending',
                                                            'bg-rose-55 text-rose-800 border-rose-200': student.payment_status === 'failed'
                                                        }">
                                                            <span class="w-1.5 h-1.5 rounded-full" :class="{
                                                                'bg-emerald-600': student.payment_status === 'verified',
                                                                'bg-amber-500': student.payment_status === 'pending',
                                                                'bg-rose-600': student.payment_status === 'failed'
                                                            }"></span>
                                                            {{ student.payment_status }}
                                                        </span>
                                                        <a v-if="student.payment_proof_path" :href="'/storage/' + student.payment_proof_path" target="_blank" class="text-[10px] font-bold text-brand-gold-dark hover:text-brand-blue flex items-center gap-1 transition mt-0.5" title="View Receipt">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                            <span>Proof Receipt</span>
                                                        </a>
                                                    </div>
                                                </td>

                                                <!-- Actions -->
                                                <td class="px-6 py-4 text-right">
                                                    <div class="flex items-center justify-end gap-2">
                                                        <button v-if="student.payment_status === 'pending'" @click="openVerifyModal(student, intake)" class="bg-[#0a1f44] hover:bg-[#143264] text-white text-[10px] font-black tracking-wider uppercase px-3.5 py-1.5 rounded-lg transition shadow-sm border border-[#0a1f44]/20 hover:scale-[1.03]">
                                                            Verify Payment
                                                        </button>
                                                        <button v-if="student.status === 'active'" @click="issueCertificate(student)" class="bg-[#f5c242] hover:bg-yellow-400 text-[#0a1f44] text-[10px] font-black tracking-wider uppercase px-3.5 py-1.5 rounded-lg transition shadow-sm border border-yellow-500/20 hover:scale-[1.03]">
                                                            Issue Cert
                                                        </button>
                                                        <span v-if="student.status === 'completed'" class="text-xs text-brand-gold-dark font-extrabold flex items-center justify-end gap-1.5">
                                                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                            Certified
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr v-if="intake.students.length === 0">
                                                <td colspan="8" class="px-5 py-8 text-center text-gray-500 font-semibold italic">
                                                    No student enrollments found matching the criteria.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </transition>
                </div>

                <div v-if="intakes.length === 0" class="py-16 text-center bg-white rounded-3xl border border-gray-150 p-8 shadow-sm">
                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <h3 class="text-base font-extrabold text-gray-700 uppercase">No Course Data Located</h3>
                    <p class="text-gray-500 text-xs mt-1">Try adjusting the filter configurations or searching for another course intake.</p>
                </div>
            </div>
        </div>

        <!-- Verify Payment Modal -->
        <div v-if="verifyModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="verifyModalOpen = false">
            <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden animate-scaleUp">
                <div class="bg-[#0a1f44] text-white px-6 py-4 flex justify-between items-center border-b-4 border-[#f5c242]">
                    <h3 class="text-base font-extrabold tracking-wide uppercase">Verify Payment Receipt</h3>
                    <button @click="verifyModalOpen = false" class="text-gray-300 hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="submitVerify" class="p-6 space-y-4">
                    <div>
                        <p class="text-xs text-gray-650 leading-relaxed mb-4">
                            Review the payment copy uploaded by the client. Enter the verified amount paid by <strong class="text-brand-blue">{{ selectedEnrollment?.user.name }}</strong> for <strong class="text-brand-blue">{{ selectedEnrollment?.intake.course.title }}</strong> to activate their portal enrollment.
                        </p>
                        <div class="bg-slate-50 p-4 rounded-xl border border-gray-150 flex items-center justify-between mb-4">
                            <div>
                                <span class="text-[9px] text-gray-400 font-black uppercase tracking-wider">Tuition Fee</span>
                                <p class="text-sm font-extrabold text-gray-700">{{ selectedEnrollment?.intake.course.currency }} {{ Number(selectedEnrollment?.intake.course.price).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</p>
                            </div>
                            <a v-if="selectedEnrollment?.payment_proof_path" :href="'/storage/' + selectedEnrollment.payment_proof_path" target="_blank" class="text-xs font-black text-brand-gold-dark hover:text-brand-blue flex items-center gap-1.5 transition uppercase tracking-wider">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                Open Receipt
                            </a>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-600 uppercase tracking-wide mb-1.5">Verified Amount Paid ({{ selectedEnrollment?.intake.course.currency }})</label>
                            <input v-model="verifyForm.amount_paid" type="number" step="0.01" min="0" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-1 focus:ring-[#0a1f44] text-xs" required />
                        </div>
                    </div>
                    <div class="pt-4 border-t border-gray-150 flex justify-end gap-3">
                        <button type="button" @click="verifyModalOpen = false" class="px-5 py-2.5 rounded-full border border-gray-300 font-semibold text-gray-750 hover:bg-gray-50 transition text-xs">Cancel</button>
                        <button type="submit" class="px-5 py-2.5 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold transition shadow-sm text-xs uppercase tracking-wider">Approve & Activate</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Manual Enroll Student Modal -->
        <div v-if="manualEnrollModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="manualEnrollModalOpen = false">
            <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden animate-scaleUp">
                <div class="bg-[#0a1f44] text-white px-6 py-4 flex justify-between items-center border-b-4 border-[#f5c242]">
                    <h3 class="text-base font-extrabold tracking-wide uppercase">Manual Enroll Student</h3>
                    <button @click="manualEnrollModalOpen = false" class="text-gray-300 hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="submitManualEnroll" class="p-6 space-y-4">
                    <!-- Student Selection -->
                    <div>
                        <label class="block text-[10px] font-bold text-gray-650 uppercase tracking-wide mb-1.5">Select Student</label>
                        <select v-model="enrollForm.user_id" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-[#f5c242] focus:ring-1 focus:ring-[#0a1f44] text-xs" required>
                            <option value="">-- Choose Student --</option>
                            <option v-for="student in studentUsers" :key="student.id" :value="student.id">
                                {{ student.name }} ({{ student.email }})
                            </option>
                        </select>
                    </div>

                    <!-- Course Intake Selection -->
                    <div>
                        <label class="block text-[10px] font-bold text-gray-650 uppercase tracking-wide mb-1.5">Select Course Intake</label>
                        <select v-model="enrollForm.course_intake_id" @change="onIntakeChange" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-[#f5c242] focus:ring-1 focus:ring-[#0a1f44] text-xs" required>
                            <option value="">-- Choose Intake --</option>
                            <option v-for="intake in activeIntakes" :key="intake.id" :value="intake.id">
                                {{ intake.course?.title }} - {{ intake.name }} ({{ intake.course?.course_code }})
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Payment Status -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-650 uppercase tracking-wide mb-1.5">Payment Status</label>
                            <select v-model="enrollForm.payment_status" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-[#f5c242] focus:ring-1 focus:ring-[#0a1f44] text-xs" required>
                                <option value="pending">Pending Verification</option>
                                <option value="verified">Verified (Active)</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>

                        <!-- Amount Paid -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-650 uppercase tracking-wide mb-1.5">Amount Paid</label>
                            <input v-model="enrollForm.amount_paid" type="number" step="0.01" min="0" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-[#f5c242] focus:ring-1 focus:ring-[#0a1f44] text-xs" required />
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-150 flex justify-end gap-3">
                        <button type="button" @click="manualEnrollModalOpen = false" class="px-5 py-2.5 rounded-full border border-gray-300 font-semibold text-gray-750 hover:bg-gray-50 transition text-xs">Cancel</button>
                        <button type="submit" class="px-5 py-2.5 rounded-full bg-[#0a1f44] hover:bg-[#143264] text-white font-extrabold transition shadow-sm text-xs uppercase tracking-wider">Enroll Student</button>
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

const props = defineProps({
    intakes: Array,
    summaryCards: Object,
    filters: Object,
    departments: Array,
    studentUsers: Array,
    activeIntakes: Array,
});

// Expandable course section states
const expandedIntakes = ref({});

const toggleIntake = (id) => {
    expandedIntakes.value[id] = !expandedIntakes.value[id];
};

// Filter states
const filterForm = reactive({
    course: props.filters.course || '',
    instructor: props.filters.instructor || '',
    unit: props.filters.unit || '',
    intake: props.filters.intake || '',
    status: props.filters.status || '',
    min_pass_rate: props.filters.min_pass_rate || '',
    max_pass_rate: props.filters.max_pass_rate || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

// Advanced filter collapse status
const showFilters = ref(false);

// Export form states
const exportForm = reactive({
    report_type: 'enrollment',
    format: 'pdf',
});

// Submit filters query parameters
const applyFilters = () => {
    Inertia.get(route('course-enrollments.index'), filterForm, {
        preserveState: true,
        replace: true,
    });
};

// Reset search fields
const resetFilters = () => {
    Object.keys(filterForm).forEach(key => {
        filterForm[key] = '';
    });
    applyFilters();
};

// Dynamic download triggers
const triggerExport = () => {
    const queryParams = {
        ...filterForm,
        report_type: exportForm.report_type,
        format: exportForm.format,
    };
    const queryString = new URLSearchParams(queryParams).toString();
    window.open(route('course-enrollments.export') + '?' + queryString, '_blank');
};

// Verifying payments & Certificate issuance logic (Preserved features)
const verifyModalOpen = ref(false);
const selectedEnrollment = ref(null);
const verifyForm = reactive({
    amount_paid: 0,
});

const openVerifyModal = (student, intake) => {
    selectedEnrollment.value = {
        id: student.enrollment_id,
        user: { name: student.name },
        intake: {
            course: {
                title: intake.course_name,
                currency: student.currency || 'USD',
                price: student.course_price || 0,
            }
        },
        payment_proof_path: student.payment_proof_path,
    };
    verifyForm.amount_paid = student.course_price || 0;
    verifyModalOpen.value = true;
};

const submitVerify = () => {
    Inertia.post(route('course-enrollments.verify', selectedEnrollment.value.id), verifyForm, {
        onSuccess: () => {
            verifyModalOpen.value = false;
            Inertia.reload({ only: ['intakes', 'summaryCards'] });
        }
    });
};

const issueCertificate = (student) => {
    if (confirm('Are you sure you want to issue a course completion certificate for ' + student.name + '? This will mark their enrollment status as completed.')) {
        Inertia.post(route('course-enrollments.certificate', student.enrollment_id), {}, {
            onSuccess: () => {
                Inertia.reload({ only: ['intakes', 'summaryCards'] });
            }
        });
    }
};

const exportClassList = (intakeId, format) => {
    const url = route('course-enrollments.export-students', { intakeId: intakeId }) + `?format=${format}`;
    window.open(url, '_blank');
};

const formatDate = (dateString) => {
    if (!dateString || dateString === 'N/A') return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
};

// Manual enrollment reactive states and methods
const manualEnrollModalOpen = ref(false);
const enrollForm = reactive({
    user_id: '',
    course_intake_id: '',
    payment_status: 'verified',
    amount_paid: 0,
});

const openManualEnrollModal = () => {
    enrollForm.user_id = '';
    enrollForm.course_intake_id = '';
    enrollForm.payment_status = 'verified';
    enrollForm.amount_paid = 0;
    manualEnrollModalOpen.value = true;
};

const onIntakeChange = () => {
    const intake = props.activeIntakes.find(i => i.id === enrollForm.course_intake_id);
    if (intake && intake.course) {
        enrollForm.amount_paid = Number(intake.course.price) || 0;
    }
};

const submitManualEnroll = () => {
    Inertia.post(route('course-enrollments.manual'), enrollForm, {
        onSuccess: () => {
            manualEnrollModalOpen.value = false;
            Inertia.reload({ only: ['intakes', 'summaryCards'] });
        }
    });
};
</script>

<style scoped>
@keyframes scaleUp {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
.animate-scaleUp {
    animation: scaleUp 0.15s ease-out forwards;
}
</style>
