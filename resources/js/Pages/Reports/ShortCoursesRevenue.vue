<template>
    <Head title="Short Courses Revenue Analytics" />

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
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Short Courses Revenue</h1>
                        <p class="text-xs text-slate-500 mt-0.5 font-medium">Drill-down reporting and student intake financial analytics</p>
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
                        <div class="h-4 w-[1px] bg-slate-200"></div>
                        <button @click="triggerExport('csv')" 
                                class="px-4 py-2 rounded-xl text-xs font-black text-slate-600 hover:bg-blue-50 hover:text-blue-650 transition-all duration-300 flex items-center gap-2 transform active:scale-95">
                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Export CSV
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
                    <span class="text-xs font-black text-slate-800">Recalibrating Revenue Datasets...</span>
                </div>
            </div>

            <!-- Filters Dashboard Panel -->
            <div class="neo-glass-card rounded-3xl p-6 border border-slate-200/80 shadow-sm relative overflow-hidden group">
                <div class="absolute top-0 left-0 w-full h-[3px] bg-gradient-to-r from-amber-400 via-indigo-600 to-blue-600"></div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6 border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                            <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Revenue Filter Engine</h3>
                            <p class="text-[10px] text-slate-500 font-medium">Fine-tune enrollment stats and course financial distributions</p>
                        </div>
                    </div>
                    
                    <!-- Date Presets Panel -->
                    <div class="flex flex-wrap items-center gap-1.5 bg-slate-100/80 p-1 rounded-2xl border border-slate-200">
                        <button v-for="preset in presets" :key="preset.id"
                                @click="selectPreset(preset.id)"
                                :class="[
                                    'px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all duration-200',
                                    filters.preset === preset.id
                                        ? 'bg-white text-indigo-700 shadow-sm'
                                        : 'text-slate-500 hover:text-slate-800 hover:bg-white/50'
                                ]">
                            {{ preset.label }}
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-5">
                    <!-- Date Start -->
                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider">Start Date</label>
                        <input type="date" v-model="filters.date_start" @change="applyFiltersPresetClear"
                               class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 shadow-sm" />
                    </div>

                    <!-- Date End -->
                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider">End Date</label>
                        <input type="date" v-model="filters.date_end" @change="applyFiltersPresetClear"
                               class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 shadow-sm" />
                    </div>

                    <!-- Course Selector -->
                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider">Course</label>
                        <select v-model="filters.course_id" @change="applyFilters"
                                class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 shadow-sm">
                            <option value="">All Courses</option>
                            <option v-for="course in filterOptions.courses" :key="course.id" :value="course.id">{{ course.title }} ({{ course.code }})</option>
                        </select>
                    </div>

                    <!-- Student Selector -->
                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider">Student</label>
                        <select v-model="filters.student_id" @change="applyFilters"
                                class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 shadow-sm">
                            <option value="">All Students</option>
                            <option v-for="student in filterOptions.students" :key="student.id" :value="student.id">{{ student.name }}</option>
                        </select>
                    </div>

                    <!-- Payment Status -->
                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider">Payment Status</label>
                        <select v-model="filters.payment_status" @change="applyFilters"
                                class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 shadow-sm">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="verified">Verified</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>

                    <!-- Payment Method -->
                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider">Payment Method</label>
                        <select v-model="filters.payment_method" @change="applyFilters"
                                class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 shadow-sm">
                            <option value="">All Methods</option>
                            <option v-for="method in filterOptions.payment_methods" :key="method" :value="method">{{ method }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mt-6 pt-5 border-t border-slate-100">
                    <div class="relative w-full sm:w-80">
                        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" v-model="filters.search" @input="debounceSearch" placeholder="Search by student or certificate code..."
                               class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-slate-800 focus:ring-2 focus:ring-blue-500 shadow-xs" />
                    </div>

                    <button @click="resetFilters" 
                            class="text-xs font-black text-red-650 hover:text-red-500 transition duration-300 flex items-center gap-1.5 transform active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Reset Filter Options
                    </button>
                </div>
            </div>

            <!-- KPI Cards Summary Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5">
                <!-- Card 1: Total Revenue -->
                <div class="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm relative overflow-hidden group">
                    <div class="absolute top-0 left-0 w-full h-[3px] bg-emerald-500"></div>
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Revenue</span>
                            <span class="block text-2xl font-black text-slate-800 mt-1 font-mono">USD {{ formatNumber(kpis.total_revenue) }}</span>
                        </div>
                        <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 text-[9px] text-slate-500 font-bold">
                        Verified student tuition fees
                    </div>
                </div>

                <!-- Card 2: Transactions -->
                <div class="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm relative overflow-hidden group">
                    <div class="absolute top-0 left-0 w-full h-[3px] bg-indigo-500"></div>
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Enrollments</span>
                            <span class="block text-2xl font-black text-slate-800 mt-1">{{ kpis.total_transactions }}</span>
                        </div>
                        <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222M12 20v-7.5l4-2.222"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 text-[9px] text-slate-500 font-bold">
                        Submitted enrollments list
                    </div>
                </div>

                <!-- Card 3: Avg per Tx -->
                <div class="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm relative overflow-hidden group">
                    <div class="absolute top-0 left-0 w-full h-[3px] bg-blue-500"></div>
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Avg per Enrollment</span>
                            <span class="block text-2xl font-black text-slate-800 mt-1 font-mono">USD {{ formatNumber(kpis.average_revenue) }}</span>
                        </div>
                        <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 text-[9px] text-slate-500 font-bold">
                        Verified student tuition mean
                    </div>
                </div>

                <!-- Card 4: Growth Rate -->
                <div class="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm relative overflow-hidden group">
                    <div class="absolute top-0 left-0 w-full h-[3px] bg-teal-500"></div>
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Revenue Growth</span>
                            <span class="block text-2xl font-black mt-1" :class="kpis.revenue_growth >= 0 ? 'text-teal-600' : 'text-rose-600'">
                                {{ kpis.revenue_growth >= 0 ? '+' : '' }}{{ kpis.revenue_growth.toFixed(1) }}%
                            </span>
                        </div>
                        <div class="p-2.5 rounded-xl" :class="kpis.revenue_growth >= 0 ? 'bg-teal-50 text-teal-600' : 'bg-rose-50 text-rose-650'">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 text-[9px] text-slate-500 font-bold">
                        Compared to previous period
                    </div>
                </div>

                <!-- Card 5: Outstanding Balance -->
                <div class="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm relative overflow-hidden group">
                    <div class="absolute top-0 left-0 w-full h-[3px] bg-rose-500"></div>
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Outstanding Tuition</span>
                            <span class="block text-2xl font-black text-slate-800 mt-1 font-mono">USD {{ formatNumber(kpis.outstanding_payments) }}</span>
                        </div>
                        <div class="p-2.5 bg-rose-50 text-rose-600 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 text-[9px] text-slate-500 font-bold">
                        Unpaid student tuition balance
                    </div>
                </div>
            </div>

            <!-- Visualization Charts Grid -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Line Chart: Trend -->
                <div class="neo-glass-card rounded-3xl p-6 border border-slate-200/80 shadow-sm xl:col-span-2">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-4">Revenue Collection Trend</h3>
                    <div id="trend-chart" class="w-full min-h-[300px]"></div>
                </div>

                <!-- Donut Chart: Payment Method -->
                <div class="neo-glass-card rounded-3xl p-6 border border-slate-200/80 shadow-sm">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-4">Payment Method Distribution</h3>
                    <div id="method-chart" class="w-full min-h-[300px] flex items-center justify-center"></div>
                </div>

                <!-- Bar Chart: Monthly Revenue -->
                <div class="neo-glass-card rounded-3xl p-6 border border-slate-200/80 shadow-sm xl:col-span-2">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-4">Monthly Revenue Comparison</h3>
                    <div id="month-chart" class="w-full min-h-[300px]"></div>
                </div>

                <!-- Donut Chart: Course Category -->
                <div class="neo-glass-card rounded-3xl p-6 border border-slate-200/80 shadow-sm">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-4">Revenue by Course Category</h3>
                    <div id="category-chart" class="w-full min-h-[300px] flex items-center justify-center"></div>
                </div>

                <!-- Horizontal Bar Chart: Top Performing Courses -->
                <div class="neo-glass-card rounded-3xl p-6 border border-slate-200/80 shadow-sm xl:col-span-2">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-4">Top Performing Courses by Revenue</h3>
                    <div id="top-courses-chart" class="w-full min-h-[300px]"></div>
                </div>

                <!-- Donut Chart: Revenue Status -->
                <div class="neo-glass-card rounded-3xl p-6 border border-slate-200/80 shadow-sm">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-4">Revenue by Payment Status</h3>
                    <div id="status-chart" class="w-full min-h-[300px] flex items-center justify-center"></div>
                </div>
            </div>

            <!-- Ledger Table Panel -->
            <div class="neo-glass-card rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-850 uppercase tracking-wider">Tuition Transaction Ledger</h3>
                        <p class="text-[10px] text-slate-500 font-semibold mt-0.5">Real-time student course enrollment payments ledger</p>
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
                                <th class="px-6 py-4">Student Name</th>
                                <th class="px-6 py-4">Course</th>
                                <th class="px-6 py-4">Payment Method</th>
                                <th class="px-6 py-4 cursor-pointer hover:bg-slate-100" @click="sortBy('amount_paid')">
                                    Amount Paid
                                    <span v-if="filters.sort_by === 'amount_paid'">{{ filters.sort_desc === 'true' ? '▼' : '▲' }}</span>
                                </th>
                                <th class="px-6 py-4">Outstanding Balance</th>
                                <th class="px-6 py-4 cursor-pointer hover:bg-slate-100" @click="sortBy('payment_status')">
                                    Status
                                    <span v-if="filters.sort_by === 'payment_status'">{{ filters.sort_desc === 'true' ? '▼' : '▲' }}</span>
                                </th>
                                <th class="px-6 py-4 cursor-pointer hover:bg-slate-100" @click="sortBy('created_at')">
                                    Date Enrolled
                                    <span v-if="filters.sort_by === 'created_at'">{{ filters.sort_desc === 'true' ? '▼' : '▲' }}</span>
                                </th>
                                <th class="px-6 py-4">Recorded By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            <tr v-for="enrollment in enrollments.data" :key="enrollment.id" class="hover:bg-slate-50/50 transition duration-150">
                                <td class="px-6 py-4.5 font-extrabold text-slate-800">
                                    ENR-{{ enrollment.id }}
                                </td>
                                <td class="px-6 py-4.5 font-bold text-slate-700">
                                    {{ enrollment.user ? enrollment.user.name : 'N/A' }}
                                </td>
                                <td class="px-6 py-4.5 text-slate-500 font-semibold">
                                    {{ enrollment.intake && enrollment.intake.course ? enrollment.intake.course.title : 'N/A' }}
                                </td>
                                <td class="px-6 py-4.5 text-slate-500 font-semibold">
                                    {{ enrollment.payment_proof_path ? 'Bank Proof Upload' : 'Manual Admin Entry' }}
                                </td>
                                <td class="px-6 py-4.5 font-bold text-emerald-650 font-mono">
                                    USD {{ formatNumber(enrollment.amount_paid) }}
                                </td>
                                <td class="px-6 py-4.5 font-bold text-rose-650 font-mono">
                                    USD {{ formatNumber(getOutstanding(enrollment)) }}
                                </td>
                                <td class="px-6 py-4.5">
                                    <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider border shadow-xs"
                                          :class="getStatusBadgeClass(enrollment.payment_status)">
                                        {{ enrollment.payment_status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4.5 text-slate-500 font-semibold">
                                    {{ formatDate(enrollment.created_at) }}
                                </td>
                                <td class="px-6 py-4.5 text-slate-500 font-semibold">
                                    {{ enrollment.payment_status === 'verified' ? 'Admin Assistant' : 'Pending Verification' }}
                                </td>
                            </tr>
                            <tr v-if="!enrollments.data || enrollments.data.length === 0">
                                <td colspan="9" class="px-6 py-16 text-center text-slate-400 text-xs font-bold bg-slate-50/10">
                                    No verified enrollment transaction records match filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginated Footer -->
                <div v-if="enrollments.links && enrollments.links.length > 3" class="p-6 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500 font-semibold">
                        Showing {{ enrollments.from }} to {{ enrollments.to }} of {{ enrollments.total }} transactions
                    </span>
                    <div class="flex items-center gap-1.5">
                        <button v-for="link in enrollments.links" :key="link.label"
                                @click="visitLink(link.url)"
                                :disabled="!link.url || link.active"
                                v-html="link.label"
                                :class="[
                                    'px-3 py-1.5 rounded-xl text-xs font-black transition duration-200',
                                    link.active
                                        ? 'bg-[#0a1f44] text-white'
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
import { ref, reactive, onMounted, watch, nextTick } from 'vue';

const props = defineProps({
    enrollments: Object,
    kpis: Object,
    charts: Object,
    filters: Object,
    filterOptions: Object
});

const isLoading = ref(false);
const chartsLoaded = ref(false);

const presets = [
    { id: 'today', label: 'Today' },
    { id: 'this_week', label: 'This Week' },
    { id: 'this_month', label: 'This Month' },
    { id: 'this_quarter', label: 'This Quarter' },
    { id: 'this_year', label: 'This Year' },
    { id: 'fy_2026', label: 'FY 2026' }
];

const filters = reactive({
    preset: props.filters.preset || 'this_month',
    date_start: props.filters.date_start || '',
    date_end: props.filters.date_end || '',
    course_id: props.filters.course_id || '',
    student_id: props.filters.student_id || '',
    payment_status: props.filters.payment_status || '',
    payment_method: props.filters.payment_method || '',
    search: props.filters.search || '',
    sort_by: props.filters.sort_by || 'created_at',
    sort_desc: props.filters.sort_desc || 'true'
});

// Chart instances
let trendChart = null;
let methodChart = null;
let monthChart = null;
let categoryChart = null;
let topCoursesChart = null;
let statusChart = null;

const formatNumber = (num) => {
    return Number(num || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getOutstanding = (e) => {
    if (!e.intake || !e.intake.course) return 0;
    return Math.max(0, e.intake.course.price - e.amount_paid);
};

const getStatusBadgeClass = (status) => {
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

const applyFilters = () => {
    isLoading.value = true;
    Inertia.get(route('reports.short-courses-revenue'), filters, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            isLoading.value = false;
        }
    });
};

const applyFiltersPresetClear = () => {
    filters.preset = '';
    applyFilters();
};

const selectPreset = (presetId) => {
    filters.preset = presetId;
    filters.date_start = '';
    filters.date_end = '';
    applyFilters();
};

const resetFilters = () => {
    filters.preset = 'this_month';
    filters.date_start = '';
    filters.date_end = '';
    filters.course_id = '';
    filters.student_id = '';
    filters.payment_status = '';
    filters.payment_method = '';
    filters.search = '';
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
    const url = new URL(route('reports.short-courses-revenue.export'), window.location.origin);
    url.searchParams.append('format', formatType);
    
    Object.entries(filters).forEach(([key, val]) => {
        if (val) url.searchParams.append(key, val);
    });
    
    window.open(url.toString(), '_blank');
};

const loadApexChartsScript = () => {
    return new Promise((resolve, reject) => {
        if (window.ApexCharts) {
            resolve();
            return;
        }
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/apexcharts';
        script.async = true;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Failed to load ApexCharts script'));
        document.head.appendChild(script);
    });
};

const renderCharts = () => {
    if (!window.ApexCharts) return;

    // 1. Trend Line Chart
    const trendOptions = {
        chart: { type: 'area', height: 300, toolbar: { show: false }, fontFamily: 'Outfit, Inter, sans-serif' },
        colors: ['#f59e0b'],
        stroke: { width: 3, curve: 'smooth' },
        series: [{ name: 'Tuition Income', data: props.charts.trend.map(d => d.total) }],
        xaxis: { categories: props.charts.trend.map(d => d.date), labels: { style: { fontSize: '9px', fontWeight: 600 } } },
        yaxis: { labels: { formatter: (val) => '$' + Number(val).toLocaleString(), style: { fontSize: '9px', fontWeight: 600 } } },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.05, stops: [0, 90, 100] } },
        grid: { borderColor: '#f1f5f9', strokeDashArray: 4 }
    };
    if (trendChart) trendChart.destroy();
    trendChart = new window.ApexCharts(document.querySelector('#trend-chart'), trendOptions);
    trendChart.render();

    // 2. Payment Method Pie/Donut Chart
    const methodOptions = {
        chart: { type: 'donut', height: 300, fontFamily: 'Outfit, Inter, sans-serif' },
        colors: ['#3b82f6', '#10b981'],
        labels: props.charts.by_method.map(d => d.method || 'Unknown'),
        series: props.charts.by_method.map(d => d.total),
        plotOptions: { pie: { donut: { size: '70%', labels: { show: true, total: { show: true, label: 'Method sum', formatter: () => '$' + props.charts.by_method.reduce((a, b) => a + b.total, 0).toLocaleString(undefined, { maximumFractionDigits: 0 }) } } } } },
        legend: { position: 'bottom', fontSize: '10px', fontWeight: 600 },
        dataLabels: { enabled: false }
    };
    if (methodChart) methodChart.destroy();
    methodChart = new window.ApexCharts(document.querySelector('#method-chart'), methodOptions);
    methodChart.render();

    // 3. Monthly Revenue Bar Chart
    const monthOptions = {
        chart: { type: 'bar', height: 300, toolbar: { show: false }, fontFamily: 'Outfit, Inter, sans-serif' },
        colors: ['#6366f1'],
        series: [{ name: 'Monthly sum', data: props.charts.by_month.map(d => d.total) }],
        xaxis: { categories: props.charts.by_month.map(d => d.month), labels: { style: { fontSize: '9px', fontWeight: 600 } } },
        yaxis: { labels: { formatter: (val) => '$' + Number(val).toLocaleString(), style: { fontSize: '9px', fontWeight: 600 } } },
        grid: { borderColor: '#f1f5f9', strokeDashArray: 4 }
    };
    if (monthChart) monthChart.destroy();
    monthChart = new window.ApexCharts(document.querySelector('#month-chart'), monthOptions);
    monthChart.render();

    // 4. Course Category Donut Chart
    const categoryOptions = {
        chart: { type: 'donut', height: 300, fontFamily: 'Outfit, Inter, sans-serif' },
        colors: ['#ec4899', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6'],
        labels: props.charts.by_service.map(d => d.category),
        series: props.charts.by_service.map(d => d.total),
        plotOptions: { pie: { donut: { size: '70%', labels: { show: true, total: { show: true, label: 'Categories', formatter: () => '$' + props.charts.by_service.reduce((a, b) => a + b.total, 0).toLocaleString(undefined, { maximumFractionDigits: 0 }) } } } } },
        legend: { position: 'bottom', fontSize: '10px', fontWeight: 600 },
        dataLabels: { enabled: false }
    };
    if (categoryChart) categoryChart.destroy();
    categoryChart = new window.ApexCharts(document.querySelector('#category-chart'), categoryOptions);
    categoryChart.render();

    // 5. Top Courses Horizontal Bar Chart
    const topOptions = {
        chart: { type: 'bar', height: 300, toolbar: { show: false }, fontFamily: 'Outfit, Inter, sans-serif' },
        plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '50%' } },
        colors: ['#0f172a'],
        series: [{ name: 'Revenue', data: props.charts.top_services.map(d => d.total) }],
        xaxis: { categories: props.charts.top_services.map(d => d.title), labels: { formatter: (val) => '$' + Number(val).toLocaleString(), style: { fontSize: '9px', fontWeight: 600 } } },
        yaxis: { labels: { style: { fontSize: '9px', fontWeight: 600 } } },
        grid: { borderColor: '#f1f5f9', strokeDashArray: 4 }
    };
    if (topCoursesChart) topCoursesChart.destroy();
    topCoursesChart = new window.ApexCharts(document.querySelector('#top-courses-chart'), topOptions);
    topCoursesChart.render();

    // 6. Status Pie Chart
    const statusOptions = {
        chart: { type: 'donut', height: 300, fontFamily: 'Outfit, Inter, sans-serif' },
        colors: ['#10b981', '#f59e0b', '#ef4444'],
        labels: props.charts.by_status.map(d => d.status),
        series: props.charts.by_status.map(d => d.total),
        plotOptions: { pie: { donut: { size: '70%', labels: { show: true } } } },
        legend: { position: 'bottom', fontSize: '10px', fontWeight: 600 },
        dataLabels: { enabled: false }
    };
    if (statusChart) statusChart.destroy();
    statusChart = new window.ApexCharts(document.querySelector('#status-chart'), statusOptions);
    statusChart.render();

    chartsLoaded.value = true;
};

onMounted(() => {
    loadApexChartsScript()
        .then(() => {
            renderCharts();
        })
        .catch(err => {
            console.error('ApexCharts load error:', err);
        });
});

watch(() => props.charts, () => {
    if (window.ApexCharts) {
        nextTick(() => {
            renderCharts();
        });
    }
}, { deep: true });
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap');

:deep(main) {
    background: radial-gradient(circle at 10% 20%, rgba(245, 158, 11, 0.02) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(59, 130, 246, 0.02) 0%, transparent 40%),
                #f8fafc !important;
}

:deep(main), :deep(header), .neo-glass-card, button, input, select, textarea {
    font-family: 'Outfit', sans-serif !important;
}

.neo-glass-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(0, 0, 0, 0.08);
    box-shadow: 0 4px 20px 0 rgba(0, 0, 0, 0.02);
}
</style>
