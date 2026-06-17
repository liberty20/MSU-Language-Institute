<template>
    <Head title="System Reports & Performance Analytics" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 w-full animate-fade-in-up">
                <div>
                    <div class="flex items-center gap-3">
                        <div>
                            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Reports & Performance Analytics</h1>
                            <p class="text-xs text-slate-500 mt-0.5 font-medium">Real-time institutional performance insights & operational metric monitoring</p>
                        </div>
                    </div>
                </div>
                
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Quick Export Actions with Glowing Micro-hover -->
                    <div class="flex items-center bg-white/80 backdrop-blur-md p-1 rounded-2xl border border-slate-200 shadow-sm">
                        <button @click="triggerQuickExport('pdf')" 
                                class="px-4 py-2 rounded-xl text-xs font-black text-slate-600 hover:bg-red-50 hover:text-red-650 transition-all duration-300 flex items-center gap-2 transform active:scale-95">
                            <svg class="w-4 h-4 text-red-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Export PDF
                        </button>
                        <div class="h-4 w-[1px] bg-slate-200"></div>
                        <button @click="triggerQuickExport('excel')" 
                                class="px-4 py-2 rounded-xl text-xs font-black text-slate-600 hover:bg-green-50 hover:text-green-650 transition-all duration-300 flex items-center gap-2 transform active:scale-95">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Export Excel
                        </button>
                    </div>

                    <!-- Modern Clean Neutral Button -->
                    <button @click="openGenerateModal = true" 
                            class="relative bg-white hover:bg-slate-50 text-slate-800 border border-slate-200 px-4 py-2.5 rounded-xl font-black text-xs transition-all duration-300 shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95 flex items-center gap-1.5 overflow-hidden group">
                        <span class="absolute inset-0 w-full h-full bg-gradient-to-r from-white/0 via-white/10 to-white/0 -translate-x-full group-hover:animate-shimmer"></span>
                        <svg class="w-3.5 h-3.5 stroke-[2.5] text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        Compile Report
                    </button>
                </div>
            </div>
        </template>

        <div class="w-full space-y-6 pb-16 animate-fade-in-up">
            <!-- Premium Navigation Tabs for Analytics Scopes -->
            <div class="flex border-b border-slate-200 gap-4 mb-6">
                <button @click="selectScope('client_services')"
                        :class="[
                            'px-5 py-3 text-xs uppercase tracking-wider font-black transition-all duration-300 border-b-2 -mb-[2px] flex items-center gap-2',
                            activeScope === 'client_services' || !activeScope
                                ? 'border-[#0a1f44] text-[#0a1f44]'
                                : 'border-transparent text-slate-500 hover:text-slate-800'
                        ]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                    Language Services & Quotations
                </button>
                <button @click="selectScope('student_enrollment')"
                        :class="[
                            'px-5 py-3 text-xs uppercase tracking-wider font-black transition-all duration-300 border-b-2 -mb-[2px] flex items-center gap-2',
                            activeScope === 'student_enrollment'
                                ? 'border-[#0a1f44] text-[#0a1f44]'
                                : 'border-transparent text-slate-500 hover:text-slate-800'
                        ]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222M12 20v-7.5l4-2.222"/></svg>
                    Short Courses Enrollments
                </button>
            </div>

            <!-- Language Services Scope Dashboard Content -->
            <div v-if="activeScope === 'client_services' || !activeScope" class="space-y-6">
                <!-- Executive KPI Deck (7 Cards) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-7 gap-5">
                <!-- Card 1: Total Intake Requests -->
                <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-3xl p-5 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
                    <div class="absolute top-0 left-0 w-full h-[3px] bg-blue-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Intake</span>
                            <span class="block text-2xl font-black text-slate-800 mt-1">{{ animatedTotals.total_requests }}</span>
                        </div>
                        <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-500 font-bold">
                        <span>Intake Volume</span>
                        <span class="text-blue-650 font-black">All requests</span>
                    </div>
                </div>

                <!-- Card 2: Active Work Queue -->
                <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-3xl p-5 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
                    <div class="absolute top-0 left-0 w-full h-[3px] bg-indigo-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Active Queue</span>
                            <span class="block text-2xl font-black text-slate-800 mt-1">{{ animatedTotals.active_assignments }}</span>
                        </div>
                        <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-500 font-bold">
                        <span>Under Development</span>
                        <span class="text-indigo-650 font-black">Active tasks</span>
                    </div>
                </div>

                <!-- Card 3: Completed Output -->
                <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-3xl p-5 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
                    <div class="absolute top-0 left-0 w-full h-[3px] bg-emerald-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Completed</span>
                            <span class="block text-2xl font-black text-slate-800 mt-1">{{ animatedTotals.completed_services }}</span>
                        </div>
                        <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-500 font-bold">
                        <span>Closed Services</span>
                        <span class="text-emerald-650 font-black">Delivered</span>
                    </div>
                </div>

                <!-- Card 4: Avg Turnaround Speed -->
                <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-3xl p-5 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
                    <div class="absolute top-0 left-0 w-full h-[3px] bg-amber-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Turnaround</span>
                            <span class="block text-2xl font-black text-slate-800 mt-1">{{ animatedTotals.avg_turnaround }}d</span>
                        </div>
                        <div class="p-2.5 bg-amber-50 text-amber-600 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-500 font-bold">
                        <span>Calendar Baseline</span>
                        <span class="text-amber-650 font-black">Avg duration</span>
                    </div>
                </div>

                <!-- Card 5: Client Satisfaction -->
                <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-3xl p-5 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
                    <div class="absolute top-0 left-0 w-full h-[3px] bg-teal-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Satisfaction</span>
                            <span class="block text-2xl font-black text-slate-800 mt-1">{{ animatedTotals.client_satisfaction }}%</span>
                        </div>
                        <div class="p-2.5 bg-teal-50 text-teal-600 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-500 font-bold">
                        <span>Post-Service Review</span>
                        <span class="text-teal-650 font-black">Rating score</span>
                    </div>
                </div>

                <!-- Card 6: Operational Quality -->
                <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-3xl p-5 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
                    <div class="absolute top-0 left-0 w-full h-[3px] bg-sky-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Quality KPI</span>
                            <span class="block text-2xl font-black text-slate-800 mt-1">{{ animatedTotals.kpi_performance }}%</span>
                        </div>
                        <div class="p-2.5 bg-sky-50 text-sky-650 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-500 font-bold">
                        <span>Compliance Rate</span>
                        <span class="text-sky-700 font-black">Audit score</span>
                    </div>
                </div>

                <!-- Card 7: Total Revenue Generated -->
                <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-3xl p-5 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
                    <div class="absolute top-0 left-0 w-full h-[3px] bg-emerald-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-emerald-500/5 rounded-full blur-xl group-hover:bg-emerald-500/10 transition-all duration-500"></div>
                    <div class="flex justify-between items-start relative z-10">
                        <div>
                            <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Revenue Generated</span>
                            <div class="flex flex-col gap-0.5 mt-1">
                                <span v-for="(amount, curr) in animatedRevenue" :key="curr" class="block text-sm font-black font-mono leading-none"
                                      :class="{
                                          'text-emerald-600': curr === 'USD',
                                          'text-blue-600': curr === 'ZAR',
                                          'text-[#d4af37]': curr === 'ZWL',
                                          'text-slate-800': !['USD', 'ZAR', 'ZWL'].includes(curr)
                                      }">
                                    {{ curr }} {{ Number(amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                </span>
                                <span v-if="Object.keys(animatedRevenue).length === 0" class="block text-sm font-black text-emerald-600 font-mono leading-none">
                                    USD 0.00
                                </span>
                            </div>
                        </div>
                        <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl border border-emerald-100/50">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-500 font-bold relative z-10">
                        <span>Verified Inflow</span>
                        <span class="text-emerald-650 font-black">Live Stats</span>
                    </div>
                </div>
            </div>
                <!-- 1. Frosted Glassmorphic Filter Card -->
                <div class="neo-glass-card rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-200/80 transition-all duration-500 relative overflow-hidden group">
                    <div class="absolute top-0 left-0 w-full h-[3px] bg-gradient-to-r from-slate-400 via-slate-600 to-blue-600 opacity-85"></div>
                    
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6 border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Operational Filter Engine</h3>
                                <p class="text-[10px] text-slate-500 font-medium">Dynamically recalibrate analytical aggregates & charts</p>
                            </div>
                        </div>
                        
                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border border-slate-200 bg-slate-100 text-slate-700">
                            {{ isManagement ? 'Administrative Scope' : 'Restricted Staff Scope' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-5">
                        <!-- Date Range Start -->
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Start Date
                            </label>
                            <input type="date" v-model="filters.date_start" @change="applyFilters" 
                                   class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 hover:bg-slate-50/50 shadow-sm" />
                        </div>

                        <!-- Date Range End -->
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                End Date
                            </label>
                            <input type="date" v-model="filters.date_end" @change="applyFilters" 
                                   class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 hover:bg-slate-50/50 shadow-sm" />
                        </div>

                        <!-- Department -->
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                Department
                            </label>
                            <select v-model="filters.department_id" @change="applyFilters" :disabled="!isManagement" 
                                    class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 hover:bg-slate-50/50 shadow-sm disabled:opacity-30 disabled:cursor-not-allowed">
                                <option value="">All Departments</option>
                                <option v-for="dept in filterOptions.departments" :key="dept.id" :value="dept.id">{{ dept.name }} ({{ dept.code }})</option>
                            </select>
                        </div>

                        <!-- Service Category -->
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                Category
                            </label>
                            <select v-model="filters.service_category" @change="applyFilters" 
                                    class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 hover:bg-slate-50/50 shadow-sm">
                                <option value="">All Categories</option>
                                <option v-for="cat in filterOptions.categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                            </select>
                        </div>

                        <!-- Staff Member -->
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                Assigned Staff
                            </label>
                            <select v-model="filters.assigned_to" @change="applyFilters" :disabled="!isManagement" 
                                    class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 hover:bg-slate-50/50 shadow-sm disabled:opacity-30 disabled:cursor-not-allowed">
                                <option value="">All Staff</option>
                                <option v-for="s in filterOptions.staff" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>

                        <!-- Request Status -->
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Request Status
                            </label>
                            <select v-model="filters.task_status" @change="applyFilters" 
                                    class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 hover:bg-slate-50/50 shadow-sm">
                                <option value="">All Statuses</option>
                                <option value="completed">Completed</option>
                                <option value="in_progress">In Progress</option>
                                <option value="pending">Pending</option>
                                <option value="overdue">Overdue</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 mt-6 pt-5 border-t border-slate-100">
                        <span class="text-[10px] text-slate-500 font-semibold flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-blue-500 animate-ping"></span>
                            All analytical metrics update automatically based on operational queue coordinates.
                        </span>
                        <button @click="resetAllFilters" 
                                class="text-xs font-black text-red-650 hover:text-red-500 transition duration-300 flex items-center gap-1.5 group transform active:scale-95">
                            <svg class="w-4 h-4 transition-transform group-hover:rotate-180 duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Reset Engine Filters
                        </button>
                    </div>
                </div>

                <!-- 2. Two Core Analysis Charts - Pie & Line -->
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                    <!-- A. Service Requests Status Pie Chart -->
                    <div class="neo-glass-card rounded-3xl p-6 shadow-md border border-slate-200/80 hover:shadow-xl transition-all duration-300 flex flex-col justify-between min-h-[420px] group">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-base font-black text-slate-850 tracking-tight">Service Requests Allocation</h3>
                                <p class="text-[10px] text-slate-500 font-semibold mt-0.5">Real-time distribution of request operational state</p>
                            </div>
                            <span class="p-2 bg-blue-550/10 rounded-xl text-blue-600 border border-blue-100">
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                            </span>
                        </div>
 
                        <!-- Chart Container -->
                        <div class="flex-grow flex items-center justify-center relative min-h-[220px]">
                            <div id="pie-chart" class="w-full"></div>
                            <div v-if="!chartsLoaded" class="absolute inset-0 flex items-center justify-center bg-white/80 backdrop-blur-xs rounded-xl">
                                <span class="text-xs font-black text-blue-600 flex items-center gap-2">
                                    <svg class="animate-spin h-5 w-5 text-blue-650" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Orchestrating Matrix...
                                </span>
                            </div>
                        </div>
 
                        <div class="text-[10px] text-slate-500 font-bold text-center border-t border-slate-100 pt-4 flex justify-between items-center px-2">
                            <span>Total Requests Counted:</span>
                            <span class="font-black text-blue-650 px-2.5 py-0.5 bg-blue-50 border border-blue-100 rounded-full">{{ totalRequestsCount }} active</span>
                        </div>
                    </div>
 
                    <!-- C. Line Chart - Influx Trend & Velocity -->
                    <div class="neo-glass-card rounded-3xl p-6 shadow-md border border-slate-200/80 hover:shadow-xl transition-all duration-300 flex flex-col justify-between min-h-[420px] group">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-base font-black text-slate-850 tracking-tight">Influx Trend & Velocity</h3>
                                <p class="text-[10px] text-slate-500 font-semibold mt-0.5">Comparative intake vs absolute completion rates monthly</p>
                            </div>
                            <span class="p-2 bg-indigo-50 text-indigo-600 border border-indigo-100">
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </span>
                        </div>
 
                        <!-- Chart Container -->
                        <div class="flex-grow flex items-center justify-center relative min-h-[220px]">
                            <div id="line-chart" class="w-full"></div>
                            <div v-if="!chartsLoaded" class="absolute inset-0 flex items-center justify-center bg-white/80 backdrop-blur-xs rounded-xl">
                                <span class="text-xs font-black text-indigo-600 flex items-center gap-2">
                                    <svg class="animate-spin h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Projecting trend timeline...
                                </span>
                            </div>
                        </div>
 
                        <div class="border-t border-slate-100 pt-4 text-[10px] text-slate-500 font-bold text-center flex items-center justify-center gap-1">
                            <svg class="w-3.5 h-3.5 text-indigo-500 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Calculated monthly totals for the past six active billing cycles.
                        </div>
                    </div>
                </div>

                <!-- 2.5 Filtered Service Requests Registry Table -->
                <div class="neo-glass-card rounded-3xl shadow-md border border-slate-200/80 hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col justify-between min-h-[400px]">
                    <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="p-1.5 bg-blue-50 text-blue-600 rounded-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                                </span>
                                <h3 class="text-base font-black text-slate-850 tracking-tight">Service Requests Registry</h3>
                            </div>
                            <p class="text-[10px] text-slate-550 font-semibold mt-0.5">Detailed operational workflow tracking log matched to active filters</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span v-if="filters.task_status" class="text-[9px] font-black uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200 px-2.5 py-1 rounded-full flex items-center gap-1.5 animate-pulse">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                Filter: {{ getStatusLabel({ status: filters.task_status }) }}
                                <button @click="filters.task_status = ''; applyFilters();" class="hover:text-blue-900 font-extrabold ml-1 font-mono">×</button>
                            </span>
                            <span class="text-xs font-black text-blue-650 bg-blue-50 border border-blue-100 px-3 py-1 rounded-xl shadow-xs">
                                Total Filtered: {{ props.serviceRequests?.total || 0 }}
                            </span>
                        </div>
                    </div>

                    <div class="overflow-x-auto flex-1 custom-scrollbar">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50 border-b border-slate-100 text-[9px] uppercase tracking-wider text-slate-500 font-black">
                                    <th class="px-6 py-4">Reference</th>
                                    <th class="px-6 py-4">Service Category</th>
                                    <th class="px-6 py-4">Title</th>
                                    <th class="px-6 py-4">Client</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4">Deadline</th>
                                    <th class="px-6 py-4">Assigned Staff</th>
                                    <th class="px-6 py-4 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs">
                                <tr v-for="req in props.serviceRequests?.data" :key="req.id" class="hover:bg-slate-50/50 transition-all duration-150">
                                    <td class="px-6 py-4.5 font-black text-slate-800">{{ req.reference_number }}</td>
                                    <td class="px-6 py-4.5 text-slate-550 font-semibold capitalize">{{ req.service_category.replace(/_/g, ' ') }}</td>
                                    <td class="px-6 py-4.5 font-extrabold text-slate-800 max-w-[200px] truncate">{{ req.title }}</td>
                                    <td class="px-6 py-4.5 text-slate-650 font-bold">{{ req.client?.organization || req.client?.contact_person || 'N/A' }}</td>
                                    <td class="px-6 py-4.5">
                                        <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider border shadow-xs"
                                              :class="getStatusBadgeClass(req)">
                                            {{ getStatusLabel(req) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4.5">
                                        <span v-if="req.deadline" class="font-semibold" :class="getCategorizedStatus(req) === 'overdue' ? 'text-red-650 font-black' : 'text-slate-500'">
                                            {{ formatDate(req.deadline) }}
                                        </span>
                                        <span v-else class="text-slate-400 font-medium italic">No Deadline</span>
                                    </td>
                                    <td class="px-6 py-4.5">
                                        <div class="flex items-center gap-2" v-if="req.assigned_to || req.assignedTo">
                                            <div class="w-5 h-5 rounded-full bg-slate-900 text-white font-black text-[9px] flex items-center justify-center">
                                                {{ (req.assigned_to?.name || req.assignedTo?.name || 'U').charAt(0).toUpperCase() }}
                                            </div>
                                            <span class="font-extrabold text-slate-700 text-[11px]">
                                                {{ req.assigned_to?.name || req.assignedTo?.name }}
                                            </span>
                                        </div>
                                        <span v-else class="text-slate-400 font-medium italic">Unassigned</span>
                                    </td>
                                    <td class="px-6 py-4.5 text-right">
                                        <Link :href="route('service-requests.show', req.id)" 
                                              class="inline-flex items-center gap-1.5 text-blue-650 hover:text-blue-500 font-black text-xs transition duration-200 transform hover:scale-105">
                                            View Details
                                            <svg class="w-3 h-3 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="!props.serviceRequests?.data || props.serviceRequests?.data.length === 0">
                                    <td colspan="8" class="px-6 py-16 text-center text-slate-400 text-xs font-bold bg-slate-50/10">
                                        No service requests found matching the active analytical scope.<br/>
                                        <span class="text-[10px] text-slate-555 font-medium mt-1 block">Try clearing status slice filters or resetting filters.</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Footer -->
                    <div class="px-6 py-4 bg-slate-50/50 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <span class="text-[10px] text-slate-500 font-extrabold uppercase tracking-wider">
                            Showing {{ props.serviceRequests?.from || 0 }} to {{ props.serviceRequests?.to || 0 }} of {{ props.serviceRequests?.total || 0 }} service requests
                        </span>
                        <div class="flex items-center gap-1.5" v-if="props.serviceRequests?.links && props.serviceRequests?.links.length > 3">
                            <Link v-for="(link, idx) in props.serviceRequests?.links" :key="idx" 
                                  :href="link.url || '#'" 
                                  preserve-state 
                                  preserve-scroll
                                  class="px-2.5 py-1.5 rounded-xl border text-[10px] font-black transition duration-200" 
                                  :class="link.active 
                                      ? 'bg-slate-900 text-white border-slate-900 shadow-sm' 
                                      : !link.url 
                                          ? 'text-slate-350 border-slate-100 cursor-not-allowed bg-transparent' 
                                          : 'bg-white text-slate-650 border-slate-200 hover:bg-slate-50 hover:text-black hover:border-slate-300'"
                                  v-html="link.label">
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Student Enrollments Scope Dashboard Content -->
            <div v-else-if="activeScope === 'student_enrollment'" class="space-y-6 animate-fade-in-up">
                <!-- Short Course Enrollment KPI Deck -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5">
                    <!-- Card 1: Total Enrolled -->
                    <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-3xl p-5 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
                        <div class="absolute top-0 left-0 w-full h-[3px] bg-blue-500"></div>
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Enrolled</span>
                                <span class="block text-2xl font-black text-slate-800 mt-1">{{ props.studentEnrollmentStats.total }}</span>
                            </div>
                            <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-500 font-bold">
                            <span>Database Ledger</span>
                            <span class="text-blue-650 font-black">All registrations</span>
                        </div>
                    </div>

                    <!-- Card 2: Active Enrolled -->
                    <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-3xl p-5 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
                        <div class="absolute top-0 left-0 w-full h-[3px] bg-emerald-500"></div>
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Active Students</span>
                                <span class="block text-2xl font-black text-slate-800 mt-1">{{ props.studentEnrollmentStats.active }}</span>
                            </div>
                            <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-500 font-bold">
                            <span>Currently Learning</span>
                            <span class="text-emerald-650 font-black">Active state</span>
                        </div>
                    </div>

                    <!-- Card 3: Pending Payment/Verification -->
                    <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-3xl p-5 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
                        <div class="absolute top-0 left-0 w-full h-[3px] bg-amber-500"></div>
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Pending</span>
                                <span class="block text-2xl font-black text-slate-800 mt-1">{{ props.studentEnrollmentStats.pending }}</span>
                            </div>
                            <div class="p-2.5 bg-amber-50 text-amber-600 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-500 font-bold">
                            <span>Requires Verification</span>
                            <span class="text-amber-650 font-black">Pending review</span>
                        </div>
                    </div>

                    <!-- Card 4: Completed -->
                    <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-3xl p-5 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
                        <div class="absolute top-0 left-0 w-full h-[3px] bg-[#d4af37]"></div>
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Completed</span>
                                <span class="block text-2xl font-black text-slate-800 mt-1">{{ props.studentEnrollmentStats.completed }}</span>
                            </div>
                            <div class="p-2.5 bg-yellow-50 text-[#d4af37] rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-500 font-bold">
                            <span>Alumni Graduates</span>
                            <span class="text-[#d4af37] font-black">Issued Certificates</span>
                        </div>
                    </div>

                    <!-- Card 5: Dropped -->
                    <div class="bg-white/90 backdrop-blur-md border border-slate-200 rounded-3xl p-5 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
                        <div class="absolute top-0 left-0 w-full h-[3px] bg-red-500"></div>
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Dropped</span>
                                <span class="block text-2xl font-black text-slate-800 mt-1">{{ props.studentEnrollmentStats.dropped }}</span>
                            </div>
                            <div class="p-2.5 bg-red-50 text-red-650 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 21h6v-1a6 6 0 00-9-5.197M15 21h6v-1a6 6 0 00-9-5.197"/></svg>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-500 font-bold">
                            <span>Deactivated / Suspended</span>
                            <span class="text-red-650 font-black">Inactive</span>
                        </div>
                    </div>
                </div>

                <!-- Two Core Analysis Charts - Pie & Line for Student Enrollments -->
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                    <!-- A. Student Enrollments Status Pie Chart -->
                    <div class="neo-glass-card rounded-3xl p-6 shadow-md border border-slate-200/80 hover:shadow-xl transition-all duration-300 flex flex-col justify-between min-h-[420px] group">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-base font-black text-slate-850 tracking-tight">Enrollment Status Distribution</h3>
                                <p class="text-[10px] text-slate-550 font-semibold mt-0.5">Real-time status breakdown of course enrollments</p>
                            </div>
                            <span class="p-2 bg-blue-550/10 rounded-xl text-blue-600 border border-blue-100">
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                            </span>
                        </div>
 
                        <!-- Chart Container -->
                        <div class="flex-grow flex items-center justify-center relative min-h-[220px]">
                            <div id="enrollment-pie-chart" class="w-full"></div>
                            <div v-if="!chartsLoaded" class="absolute inset-0 flex items-center justify-center bg-white/80 backdrop-blur-xs rounded-xl">
                                <span class="text-xs font-black text-blue-600 flex items-center gap-2">
                                    <svg class="animate-spin h-5 w-5 text-blue-650" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Orchestrating Matrix...
                                </span>
                            </div>
                        </div>
 
                        <div class="text-[10px] text-slate-500 font-bold text-center border-t border-slate-100 pt-4 flex justify-between items-center px-2">
                            <span>Total Students Enrolled:</span>
                            <span class="font-black text-blue-650 px-2.5 py-0.5 bg-blue-50 border border-blue-100 rounded-full">{{ props.studentEnrollmentStats.total }} registered</span>
                        </div>
                    </div>
 
                    <!-- C. Line Chart - Enrollment Trend & Velocity -->
                    <div class="neo-glass-card rounded-3xl p-6 shadow-md border border-slate-200/80 hover:shadow-xl transition-all duration-300 flex flex-col justify-between min-h-[420px] group">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-base font-black text-slate-850 tracking-tight">Enrollment Influx Trend</h3>
                                <p class="text-[10px] text-slate-550 font-semibold mt-0.5">Monthly student enrollment trajectory</p>
                            </div>
                            <span class="p-2 bg-indigo-50 text-indigo-600 border border-indigo-100">
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </span>
                        </div>
 
                        <!-- Chart Container -->
                        <div class="flex-grow flex items-center justify-center relative min-h-[220px]">
                            <div id="enrollment-line-chart" class="w-full"></div>
                            <div v-if="!chartsLoaded" class="absolute inset-0 flex items-center justify-center bg-white/80 backdrop-blur-xs rounded-xl">
                                <span class="text-xs font-black text-indigo-600 flex items-center gap-2">
                                    <svg class="animate-spin h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Projecting trend timeline...
                                </span>
                            </div>
                        </div>
 
                        <div class="border-t border-slate-100 pt-4 text-[10px] text-slate-500 font-bold text-center flex items-center justify-center gap-1">
                            <svg class="w-3.5 h-3.5 text-indigo-500 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Calculated monthly totals for the past six active enrollment periods.
                        </div>
                    </div>
                </div>

                <!-- Short Course Enrollment Filters Card -->
                <div class="neo-glass-card rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-slate-200/80 transition-all duration-500 relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-[3px] bg-[#0a1f44] opacity-85"></div>
                    
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6 border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-800 uppercase tracking-wider">Short Courses Analytics Filter</h3>
                                <p class="text-[10px] text-slate-550 font-medium">Dynamically filter community student enrollment database parameters</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5">
                        <!-- Select Course -->
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                Select Course
                            </label>
                            <select v-model="filters.student_course_id" @change="applyFilters" 
                                    class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 hover:bg-slate-50/50 shadow-sm">
                                <option value="">All Courses</option>
                                <option v-for="course in filterOptions.courses" :key="course.id" :value="course.id">
                                    [{{ course.code }}] {{ course.title }}
                                </option>
                            </select>
                        </div>

                        <!-- Select Unit -->
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                MSUNLI Unit
                            </label>
                            <select v-model="filters.student_unit_id" @change="applyFilters" 
                                    class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 hover:bg-slate-50/50 shadow-sm">
                                <option value="">All Units</option>
                                <option v-for="unit in filterOptions.departments" :key="unit.id" :value="unit.id">
                                    {{ unit.code }} - {{ unit.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Enrollment Status -->
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Status
                            </label>
                            <select v-model="filters.student_enrollment_status" @change="applyFilters" 
                                    class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 hover:bg-slate-50/50 shadow-sm">
                                <option value="">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="pending">Pending Review</option>
                                <option value="completed">Completed</option>
                                <option value="dropped">Dropped</option>
                            </select>
                        </div>

                        <!-- Start Date -->
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Start Date
                            </label>
                            <input type="date" v-model="filters.date_start" @change="applyFilters" 
                                   class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 hover:bg-slate-50/50 shadow-sm" />
                        </div>

                        <!-- End Date -->
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                End Date
                            </label>
                            <input type="date" v-model="filters.date_end" @change="applyFilters" 
                                   class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 hover:bg-slate-50/50 shadow-sm" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-4 pt-4 border-t border-slate-100">
                        <button @click="resetAllFilters" class="px-5 py-2.5 text-xs font-black text-red-650 hover:bg-red-50 rounded-xl transition duration-300 transform active:scale-95 flex items-center gap-1.5">
                            Reset Filters
                        </button>
                    </div>
                </div>

                <!-- Enrolled Students Table -->
                <div class="neo-glass-card rounded-3xl shadow-md border border-slate-200/80 hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col justify-between min-h-[400px]">
                    <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="p-1.5 bg-[#0a1f44] text-[#f5c242] rounded-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222M12 20v-7.5l4-2.222"/></svg>
                                </span>
                                <h3 class="text-base font-black text-slate-850 tracking-tight">Short Course Student Enrollments Register</h3>
                            </div>
                            <p class="text-[10px] text-slate-550 font-semibold mt-0.5 font-medium">Real-time dynamic student enrollment logs and tuition payment tracking</p>
                        </div>
                        <span class="text-xs font-black text-blue-650 bg-blue-50 border border-blue-100 px-3 py-1 rounded-xl shadow-xs">
                            Total Filtered: {{ props.enrolledStudents?.total || 0 }}
                        </span>
                    </div>

                    <div class="overflow-x-auto flex-1 custom-scrollbar">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50 border-b border-slate-100 text-[9px] uppercase tracking-wider text-slate-500 font-black">
                                    <th class="px-6 py-4">Student</th>
                                    <th class="px-6 py-4">Course Details</th>
                                    <th class="px-6 py-4">MSUNLI Unit</th>
                                    <th class="px-6 py-4 text-right">Tuition Paid</th>
                                    <th class="px-6 py-4">Payment</th>
                                    <th class="px-6 py-4">Enrollment Status</th>
                                    <th class="px-6 py-4">Enrolled Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs">
                                <tr v-for="se in props.enrolledStudents?.data" :key="se.id" class="hover:bg-slate-50/50 transition duration-150">
                                    <td class="px-6 py-4">
                                        <div class="font-black text-slate-850 text-sm">{{ se.user?.name || 'N/A' }}</div>
                                        <div class="text-[10px] text-slate-400 font-bold">{{ se.user?.email || 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-extrabold text-slate-800">{{ se.intake?.course?.title || 'N/A' }}</div>
                                        <div class="text-[10px] text-slate-550 font-semibold">Intake: {{ se.intake?.name || 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4 font-black text-[#0a1f44]">
                                        {{ se.intake?.course?.department?.code || 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-mono font-bold text-slate-800 font-mono font-bold">
                                        ${{ Number(se.amount_paid || 0).toFixed(2) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider border shadow-xs"
                                              :class="se.payment_status === 'verified' 
                                                  ? 'bg-emerald-50 text-emerald-700 border-emerald-200' 
                                                  : 'bg-amber-50 text-amber-700 border-amber-200'">
                                            {{ se.payment_status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider border shadow-xs"
                                              :class="se.enrollment_status === 'active' 
                                                  ? 'bg-emerald-50 text-emerald-700 border-emerald-200' 
                                                  : se.enrollment_status === 'pending'
                                                      ? 'bg-amber-50 text-amber-700 border-amber-200'
                                                      : se.enrollment_status === 'completed'
                                                          ? 'bg-blue-50 text-blue-700 border-blue-200'
                                                          : 'bg-red-50 text-red-700 border-red-200'">
                                            {{ se.enrollment_status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-500 font-bold">
                                        {{ formatDate(se.created_at) }}
                                    </td>
                                </tr>
                                <tr v-if="!props.enrolledStudents?.data || props.enrolledStudents?.data.length === 0">
                                    <td colspan="7" class="px-6 py-16 text-center text-slate-400 text-xs font-bold bg-slate-50/10">
                                        No student enrollments found matching the active analytical filters.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Footer -->
                    <div class="px-6 py-4 bg-slate-50/50 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <span class="text-[10px] text-slate-500 font-extrabold uppercase tracking-wider">
                            Showing {{ props.enrolledStudents?.from || 0 }} to {{ props.enrolledStudents?.to || 0 }} of {{ props.enrolledStudents?.total || 0 }} students
                        </span>
                        <div class="flex items-center gap-1.5" v-if="props.enrolledStudents?.links && props.enrolledStudents?.links.length > 3">
                            <Link v-for="(link, idx) in props.enrolledStudents?.links" :key="idx" 
                                  :href="link.url || '#'" 
                                  preserve-state 
                                  preserve-scroll
                                  class="px-2.5 py-1.5 rounded-xl border text-[10px] font-black transition duration-200" 
                                  :class="link.active 
                                      ? 'bg-slate-900 text-white border-slate-900 shadow-sm' 
                                      : !link.url 
                                          ? 'text-slate-350 border-slate-100 cursor-not-allowed bg-transparent' 
                                          : 'bg-white text-slate-650 border-slate-200 hover:bg-slate-50 hover:text-black hover:border-slate-300'"
                                  v-html="link.label">
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

                <!-- 3. Staff Productivity & Reports History -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" style="display:none;">
                    <!-- Staff Productivity (Podium style Leaderboard) -->
                    <div class="neo-glass-card rounded-3xl p-6 shadow-md border border-slate-200/80 hover:shadow-xl transition-all duration-300 lg:col-span-1 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-center mb-6">
                                <div>
                                    <h3 class="text-base font-black text-slate-850 tracking-tight">Staff Productivity Log</h3>
                                    <p class="text-[10px] text-slate-500 font-semibold mt-0.5">Top performing language specialists & coordinators</p>
                                </div>
                                <span class="bg-indigo-50 text-indigo-600 text-[9px] font-black uppercase px-2.5 py-1 rounded-full border border-indigo-100">Leaderboard</span>
                            </div>

                            <!-- Leaderboard List -->
                            <div class="space-y-3 max-h-[300px] overflow-y-auto pr-1 custom-scrollbar">
                                <div v-for="(expert, idx) in staffProductivity" :key="expert.id" 
                                     class="flex flex-col p-3.5 rounded-2xl border border-slate-100 bg-slate-50/30 hover:bg-slate-50 transition-all duration-300">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <!-- Rank Badge with Podium metallic colors -->
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center font-black text-xs shadow-xs border"
                                                 :class="idx === 0 ? 'bg-gradient-to-br from-amber-50 to-yellow-100/50 text-amber-700 border-amber-300' :
                                                         idx === 1 ? 'bg-gradient-to-br from-slate-50 to-slate-100/50 text-slate-700 border-slate-300' :
                                                         idx === 2 ? 'bg-gradient-to-br from-orange-50 to-orange-100/50 text-orange-700 border-orange-300' :
                                                         'bg-slate-100 text-slate-500 border-slate-200'">
                                                {{ idx + 1 }}
                                            </div>
                                            <div>
                                                <h4 class="text-xs font-black text-slate-800 flex items-center gap-1">
                                                    {{ expert.name }}
                                                    <svg v-if="idx === 0" class="w-3.5 h-3.5 text-amber-500 animate-bounce" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                </h4>
                                                <span class="text-[9px] text-slate-500 font-extrabold uppercase tracking-wide">{{ expert.department }}</span>
                                            </div>
                                        </div>

                                        <div class="text-right">
                                            <span class="text-xs font-black text-indigo-600 block">{{ expert.score }} pts</span>
                                            <span class="text-[9px] text-slate-500 font-bold">{{ expert.tasks_completed }} tasks &bull; {{ expert.requests_completed }} SR</span>
                                        </div>
                                    </div>
                                    <!-- Progress Bar Indicator -->
                                    <div class="w-full bg-slate-100 h-1.5 rounded-full mt-2.5 overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-1000 bg-gradient-to-r"
                                             :class="idx === 0 ? 'from-amber-400 to-yellow-500' : 'from-indigo-500 to-blue-500'"
                                             :style="{ width: `${Math.min(100, (expert.score / (staffProductivity[0]?.score || 1)) * 100)}%` }">
                                        </div>
                                    </div>
                                </div>

                                <div v-if="staffProductivity.length === 0" class="text-center py-12 text-slate-500 text-xs font-bold">
                                    No productivity coordinates archived for active filters.
                                </div>
                            </div>
                        </div>

                        <div class="text-[9px] text-slate-550 font-bold border-t border-slate-100 pt-3 text-center">
                            Calculated by mapping Tasks Completed (10pts) & Requests Handled (25pts).
                        </div>
                    </div>

                    <!-- Generated Reports Archive Table with Searchable Search Engines -->
                    <div class="neo-glass-card rounded-3xl shadow-md border border-slate-200/80 hover:shadow-xl transition-all duration-300 lg:col-span-2 overflow-hidden flex flex-col justify-between min-h-[400px]">
                        <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <h3 class="text-base font-black text-slate-850 tracking-tight">Generated Reports Archive</h3>
                                <p class="text-[10px] text-slate-550 font-semibold mt-0.5">Persistent PDF and Excel operational audit registries</p>
                            </div>
                            <span class="text-xs font-black text-blue-650 bg-blue-50 border border-blue-100 px-3 py-1 rounded-xl shadow-xs">Total Saved: {{ reports.total || 0 }}</span>
                        </div>

                        <!-- Modern Search & Category Filter panel inside table row -->
                        <div class="px-6 py-3.5 bg-slate-50/20 border-b border-slate-100 flex flex-col sm:flex-row items-center gap-3">
                            <div class="relative w-full sm:flex-1">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </span>
                                <input v-model="filters.search" @keyup.enter="applyFilters" type="text" placeholder="Search archive by report title..." 
                                       class="block w-full pl-9 pr-3 py-2 text-xs bg-white border border-slate-200 rounded-xl text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition shadow-xs" />
                            </div>
                            
                            <div class="w-full sm:w-48">
                                <select v-model="filters.archive_type" @change="applyFilters" 
                                        class="w-full text-xs bg-white border border-slate-200 rounded-xl px-3 py-2 text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition shadow-xs">
                                    <option value="">Filter by Type</option>
                                    <option value="client_services">Client Service Reports</option>
                                    <option value="quotations">Quotation Reports</option>
                                    <option value="workflow_progress">Workflow Progress</option>
                                    <option value="staff_workload">Staff Workload</option>
                                    <option value="kpi_performance">KPI Performance</option>
                                    <option value="administrative">Administrative Reports</option>
                                    <option value="student_enrollment">Student Enrollment Reports</option>
                                </select>
                            </div>

                            <button @click="applyFilters" 
                                    class="w-full sm:w-auto px-5 py-2 bg-slate-900 text-white rounded-xl text-xs font-black hover:bg-slate-800 transition shadow-sm transform active:scale-95">
                                Search
                            </button>
                        </div>

                        <div class="overflow-x-auto flex-1 custom-scrollbar">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50/50 border-b border-slate-100 text-[9px] uppercase tracking-wider text-slate-500 font-black">
                                        <th class="px-6 py-4">Report Title</th>
                                        <th class="px-6 py-4">Type</th>
                                        <th class="px-6 py-4">Format</th>
                                        <th class="px-6 py-4">Date Generated</th>
                                        <th class="px-6 py-4 text-right">Download</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-xs">
                                    <tr v-for="report in reports.data" :key="report.id" class="hover:bg-slate-50/50 transition-all duration-150">
                                        <td class="px-6 py-4.5 font-extrabold text-slate-800 max-w-[200px] truncate">{{ report.title }}</td>
                                        <td class="px-6 py-4.5 text-slate-500 font-semibold capitalize">{{ report.report_type.replace('_', ' ') }}</td>
                                        <td class="px-6 py-4.5">
                                            <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider shadow-xs border"
                                                  :class="report.format === 'pdf' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'">
                                                {{ report.format }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4.5 text-slate-500 font-semibold">{{ formatDate(report.created_at) }}</td>
                                        <td class="px-6 py-4.5 text-right">
                                            <a :href="report.file_path" 
                                               class="inline-flex items-center gap-1.5 text-blue-650 hover:text-blue-500 font-black text-xs transition duration-200 transform hover:scale-105" 
                                               download>
                                                <svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                Deliver
                                            </a>
                                        </td>
                                    </tr>
                                    <tr v-if="!reports.data || reports.data.length === 0">
                                        <td colspan="5" class="px-6 py-16 text-center text-slate-400 text-xs font-bold bg-slate-50/10">
                                            No custom report logs are persistently generated yet.<br/>
                                            <span class="text-[10px] text-slate-500 font-semibold mt-1 block">Click the "+ Compile Report" button at the top to build.</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        <!-- 5. Report Generation Glassmorphic Modal -->
        <div v-if="openGenerateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Modal Backdrop Blur -->
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-md transition-opacity duration-300" @click="openGenerateModal = false"></div>
            
            <div class="bg-white border border-slate-200/80 rounded-[2rem] shadow-2xl w-full max-w-lg z-10 overflow-hidden transform transition-all duration-300 scale-100 relative animate-fade-in-up text-slate-800">
                <div class="absolute top-0 left-0 w-full h-[4px] bg-gradient-to-r from-slate-400 via-slate-650 to-blue-600"></div>
                
                <div class="bg-slate-50/80 px-8 py-6 flex justify-between items-center relative border-b border-slate-100">
                    <div>
                        <h3 class="text-lg font-black tracking-tight flex items-center gap-2 text-slate-850">
                            <img src="/msu-logo-2.png" alt="MSU" class="h-6 w-auto bg-white p-0.5 rounded shadow-sm" />
                            Compile Operations Report
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5 font-medium">Capture dynamic performance coordinates into a saved document</p>
                    </div>
                    <button @click="openGenerateModal = false" class="text-slate-400 hover:text-slate-650 transition duration-200 p-1.5 bg-white shadow-sm border border-slate-200 rounded-full">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submitGenerateReport" class="p-8 space-y-6">
                     <div class="space-y-1.5">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider">Report Custom Title</label>
                        <input type="text" v-model="form.title" placeholder="e.g. Q2 Performance Overview" required 
                               class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-2xl px-4 py-3.5 text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 hover:bg-slate-50/50 shadow-xs" />
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider">Report Aggregation Type</label>
                        <select v-model="form.report_type" 
                                class="w-full text-xs font-semibold bg-white border border-slate-200 rounded-2xl px-4 py-3.5 text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 hover:bg-slate-50/50 shadow-xs">
                            <option value="client_services">Client Service Report</option>
                            <option value="quotations">Quotation Report</option>
                            <option value="workflow_progress">Workflow Progress Report</option>
                            <option value="staff_workload">Staff Workload Report</option>
                            <option value="kpi_performance">KPI Performance Report</option>
                            <option value="administrative">Administrative Report</option>
                            <option value="student_enrollment">Student Enrollment Report</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider">Target Export Format</label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="flex items-center gap-3 p-4 rounded-2xl border border-slate-200/80 bg-slate-50/35 cursor-pointer hover:bg-slate-100/50 hover:border-indigo-500/40 transition-all duration-300 shadow-xs relative overflow-hidden group animate-fade-in-up"
                                   :class="form.format === 'pdf' ? 'border-indigo-500 bg-indigo-50/40 ring-2 ring-indigo-500/10' : ''">
                                <input type="radio" v-model="form.format" value="pdf" class="text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer bg-white border-slate-300" />
                                <span class="text-xs font-black text-slate-800">PDF Document</span>
                            </label>
                            
                            <label class="flex items-center gap-3 p-4 rounded-2xl border border-slate-200/80 bg-slate-50/35 cursor-pointer hover:bg-slate-100/50 hover:border-blue-500/40 transition-all duration-300 shadow-xs relative overflow-hidden group animate-fade-in-up"
                                   :class="form.format === 'excel' ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-500/20' : ''">
                                <input type="radio" v-model="form.format" value="excel" class="text-blue-600 focus:ring-blue-500 w-4 h-4 cursor-pointer bg-white border-slate-300" />
                                <span class="text-xs font-black text-slate-800">Excel Spread</span>
                            </label>
                        </div>
                    </div>

                    <div class="bg-slate-50 border-l-4 border-slate-600 p-4 rounded-r-2xl text-[10px] text-slate-800 font-bold flex gap-2">
                        <svg class="w-4.5 h-4.5 text-slate-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                        <div>
                            <strong>Active Filters Bound:</strong> All active filter variables set in the dashboard panel will be mirrored in the generated report.
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-6 border-t border-slate-100">
                        <button type="button" @click="openGenerateModal = false" class="px-5 py-2.5 rounded-xl text-xs font-black text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition duration-200">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="bg-slate-900 text-white px-6 py-3 rounded-2xl text-xs font-black hover:bg-slate-800 transition-all duration-300 shadow-md flex items-center gap-1.5 transform active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                            Compile & Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/inertia-vue3';
import { Inertia } from '@inertiajs/inertia';
import { ref, reactive, computed, onMounted, watch, nextTick } from 'vue';

const props = defineProps({
    byCategory: Object,
    categoryTurnaround: Object,
    byStatus: Object,
    monthly: Array,
    serviceRequests: Object,
    totals: Object,
    staffProductivity: Array,
    reports: Object,
    filterOptions: Object,
    currentFilters: Object,
    isManagement: Boolean,
    studentEnrollmentStats: Object,
    enrolledStudents: Object,
    enrollmentMonthly: Array
});

const getCategorizedStatus = (req) => {
    if (!req) return 'pending';
    const now = new Date().toISOString().split('T')[0];
    const status = req.status;
    const deadline = req.deadline;
    
    if (status === 'completed') return 'completed';
    
    if (status !== 'completed' && status !== 'cancelled' && deadline && deadline < now) {
        return 'overdue';
    }
    
    if (['in_progress', 'review'].includes(status)) {
        return 'in_progress';
    }
    
    if (['pending', 'quoted', 'approved'].includes(status)) {
        return 'pending';
    }
    
    return status;
};

const getStatusBadgeClass = (req) => {
    const cat = getCategorizedStatus(req);
    switch (cat) {
        case 'completed':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'in_progress':
            return 'bg-blue-50 text-blue-750 border-blue-200';
        case 'pending':
            return 'bg-indigo-50 text-indigo-700 border-indigo-200';
        case 'overdue':
            return 'bg-rose-50 text-rose-700 border-rose-200';
        default:
            return 'bg-slate-50 text-slate-700 border-slate-200';
    }
};

const getStatusLabel = (req) => {
    const cat = getCategorizedStatus(req);
    if (cat === 'in_progress') return 'In Progress';
    if (cat === 'pending') return 'Pending';
    if (cat === 'completed') return 'Completed';
    if (cat === 'overdue') return 'Overdue';
    return typeof cat === 'string' ? cat.replace('_', ' ') : '';
};

// Setup filters including searchable fields
const filters = reactive({
    date_start: props.currentFilters.date_start || '',
    date_end: props.currentFilters.date_end || '',
    department_id: props.currentFilters.department_id || '',
    service_category: props.currentFilters.service_category || '',
    assigned_to: props.currentFilters.assigned_to || '',
    task_status: props.currentFilters.task_status || '',
    search: props.currentFilters.search || '',
    archive_type: props.currentFilters.archive_type || '',
    student_course_id: props.currentFilters.student_course_id || '',
    student_unit_id: props.currentFilters.student_unit_id || '',
    student_enrollment_status: props.currentFilters.student_enrollment_status || ''
});

const activeScope = ref(filters.archive_type || 'client_services');
const showExtendedData = ref(false);

const selectScope = (scope) => {
    activeScope.value = scope;
    filters.archive_type = scope;
    form.report_type = scope;
    applyFilters();
};

const scopeClass = (scopeId) => {
    const isActive = activeScope.value === scopeId;
    return [
        'w-full flex items-center justify-between p-3.5 rounded-2xl text-xs font-black transition-all duration-300 transform active:scale-98 border',
        isActive
            ? 'bg-indigo-100 border-indigo-500 text-black shadow-sm'
            : 'text-black bg-white hover:bg-slate-100 hover:text-black border-slate-200'
    ];
};

const kpiCardClass = (targetScope) => {
    const isActive = activeScope.value === targetScope;
    return [
        'relative overflow-hidden rounded-3xl p-6 border flex flex-col justify-between min-h-[140px] transition-all duration-500 hover:-translate-y-1 group cursor-pointer bg-white',
        isActive 
            ? 'border-indigo-500 shadow-[0_0_20px_rgba(79,70,229,0.15)] ring-4 ring-indigo-100 scale-[1.03] text-black font-black' 
            : 'border-slate-200 shadow-sm hover:border-slate-350 text-black font-semibold'
    ];
};

// Animated Counters State
const animatedTotals = reactive({
    total_requests: 0,
    active_assignments: 0,
    completed_services: 0,
    avg_turnaround: 0,
    client_satisfaction: 0,
    kpi_performance: 0
});

const animatedRevenue = reactive({});

const animateValue = (key, target, duration = 800) => {
    const startValue = animatedTotals[key] || 0;
    const startTime = performance.now();
    
    const step = (currentTime) => {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const ease = progress * (2 - progress); // easeOutQuad
        
        let currentVal = startValue + (target - startValue) * ease;
        if (key === 'client_satisfaction') {
            animatedTotals[key] = parseFloat(currentVal.toFixed(1));
        } else if (key === 'avg_turnaround') {
            animatedTotals[key] = parseFloat(currentVal.toFixed(1));
        } else {
            animatedTotals[key] = Math.round(currentVal);
        }
        
        if (progress < 1) {
            requestAnimationFrame(step);
        } else {
            animatedTotals[key] = target;
        }
    };
    
    requestAnimationFrame(step);
};

const animateRevenueValue = (curr, target, duration = 800) => {
    const startValue = animatedRevenue[curr] || 0;
    const startTime = performance.now();
    
    const step = (currentTime) => {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const ease = progress * (2 - progress); // easeOutQuad
        
        let currentVal = startValue + (target - startValue) * ease;
        animatedRevenue[curr] = parseFloat(currentVal.toFixed(2));
        
        if (progress < 1) {
            requestAnimationFrame(step);
        } else {
            animatedRevenue[curr] = target;
        }
    };
    
    requestAnimationFrame(step);
};

const triggerAnimations = () => {
    animateValue('total_requests', props.totals.total_requests || 0);
    animateValue('active_assignments', props.totals.active_assignments || 0);
    animateValue('completed_services', props.totals.completed_services || 0);
    animateValue('avg_turnaround', props.totals.avg_turnaround || 0);
    animateValue('client_satisfaction', props.totals.client_satisfaction || 0);
    animateValue('kpi_performance', props.totals.kpi_performance || 0);
    
    const revGen = props.totals.revenue_generated || {};
    // Remove any currency keys that are no longer in revGen
    Object.keys(animatedRevenue).forEach(key => {
        if (!(key in revGen)) {
            delete animatedRevenue[key];
        }
    });
    // Animate each active currency key
    Object.entries(revGen).forEach(([curr, amount]) => {
        animateRevenueValue(curr, parseFloat(amount) || 0);
    });
};

const applyFilters = () => {
    Inertia.get(route('reports.index'), filters, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

const resetAllFilters = () => {
    filters.date_start = '';
    filters.date_end = '';
    filters.department_id = '';
    filters.service_category = '';
    filters.assigned_to = '';
    filters.task_status = '';
    filters.search = '';
    filters.archive_type = activeScope.value;
    filters.student_course_id = '';
    filters.student_unit_id = '';
    filters.student_enrollment_status = '';
    applyFilters();
};

// Modal
const openGenerateModal = ref(false);
const form = reactive({
    title: '',
    report_type: activeScope.value,
    format: 'pdf',
    filters: computed(() => filters)
});

const submitGenerateReport = () => {
    Inertia.post(route('reports.store'), form, {
        onSuccess: () => {
            openGenerateModal.value = false;
            form.title = '';
        }
    });
};

// Export dynamically using active report type
const triggerQuickExport = (formatType) => {
    const url = new URL(route('reports.export'), window.location.origin);
    url.searchParams.append('format', formatType);
    url.searchParams.append('report_type', form.report_type); 
    
    if (filters.date_start) url.searchParams.append('date_start', filters.date_start);
    if (filters.date_end) url.searchParams.append('date_end', filters.date_end);
    if (filters.department_id) url.searchParams.append('department_id', filters.department_id);
    if (filters.service_category) url.searchParams.append('service_category', filters.service_category);
    if (filters.assigned_to) url.searchParams.append('assigned_to', filters.assigned_to);
    if (filters.task_status) url.searchParams.append('task_status', filters.task_status);
    if (filters.student_course_id) url.searchParams.append('student_course_id', filters.student_course_id);
    if (filters.student_unit_id) url.searchParams.append('student_unit_id', filters.student_unit_id);
    if (filters.student_enrollment_status) url.searchParams.append('student_enrollment_status', filters.student_enrollment_status);
    
    window.open(url.toString(), '_blank');
};

const totalRequestsCount = computed(() => {
    return (props.byStatus.completed || 0) + 
           (props.byStatus.in_progress || 0) + 
           (props.byStatus.pending || 0) + 
           (props.byStatus.overdue || 0);
});

// --- Dynamic ApexCharts Implementation ---
const chartsLoaded = ref(false);
let pieChartInstance = null;
let lineChartInstance = null;
let enrollmentPieChartInstance = null;
let enrollmentLineChartInstance = null;

// Dynamically fetch and install ApexCharts script tag from JSDelivr CDN
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

// Initialize or update charts
const renderCharts = () => {
    if (!window.ApexCharts) return;

    // A. Pie/Donut Chart (Service Request Statuses) with Premium Colors & Labels
    const pieOptions = {
        chart: {
            type: 'donut',
            height: 250,
            fontFamily: 'Outfit, Inter, sans-serif',
            background: 'transparent',
            events: {
                dataPointSelection: (event, chartContext, config) => {
                    const idx = config.dataPointIndex;
                    if (idx !== undefined && idx !== null && idx >= 0) {
                        const statusMap = ['completed', 'in_progress', 'pending', 'overdue'];
                        const clickedStatus = statusMap[idx];
                        if (filters.task_status === clickedStatus) {
                            filters.task_status = ''; // toggle-clear
                        } else {
                            filters.task_status = clickedStatus;
                        }
                        applyFilters();
                    }
                }
            },
            dropShadow: {
                enabled: true,
                top: 4,
                left: 0,
                blur: 8,
                color: '#000',
                opacity: 0.03
            }
        },
        theme: {
            mode: 'light'
        },
        stroke: {
            width: 2,
            colors: ['#ffffff']
        },
        colors: ['#10b981', '#3b82f6', '#6366f1', '#f43f5e'],
        labels: ['Completed', 'In Progress', 'Pending', 'Overdue'],
        series: [
            props.byStatus.completed || 0,
            props.byStatus.in_progress || 0,
            props.byStatus.pending || 0,
            props.byStatus.overdue || 0
        ],
        legend: {
            position: 'bottom',
            fontSize: '11px',
            fontFamily: 'Outfit, Inter, sans-serif',
            fontWeight: 700,
            labels: { colors: '#000000' },
            markers: { radius: 12 }
        },
        dataLabels: {
            enabled: true,
            style: {
                fontSize: '10px',
                fontFamily: 'Outfit, Inter, sans-serif',
                fontWeight: 'bold'
            },
            formatter: (val) => `${Math.round(val)}%`
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '72%',
                    labels: {
                        show: true,
                        name: {
                            show: true,
                            fontSize: '11px',
                            fontWeight: 'bold',
                            color: '#000000',
                            offsetY: -4
                        },
                        value: {
                            show: true,
                            fontSize: '18px',
                            fontWeight: 'bold',
                            color: '#000000',
                            offsetY: 4,
                            formatter: (val) => val
                        },
                        total: {
                            show: true,
                            label: 'Total Requests',
                            color: '#000000',
                            formatter: () => totalRequestsCount.value
                        }
                    }
                }
            }
        },
        tooltip: {
            theme: 'light',
            y: {
                formatter: (val) => `${val} requests`
            }
        }
    };

    if (pieChartInstance) {
        pieChartInstance.updateOptions(pieOptions);
    } else {
        const pieEl = document.querySelector('#pie-chart');
        if (pieEl) {
            pieChartInstance = new window.ApexCharts(pieEl, pieOptions);
            pieChartInstance.render();
        }
    }

    // C. Line/Area Chart (Trends) with Premium Spline & Double Gradient Fill
    const trendMonths = props.monthly.map(m => m.month);
    const trendVol = props.monthly.map(m => m.volume);
    const trendComp = props.monthly.map(m => m.completed);

    const lineOptions = {
        chart: {
            type: 'area',
            height: 250,
            toolbar: { show: false },
            fontFamily: 'Outfit, Inter, sans-serif',
            background: 'transparent',
            dropShadow: {
                enabled: true,
                top: 4,
                left: 0,
                blur: 8,
                color: '#000',
                opacity: 0.03
            }
        },
        theme: {
            mode: 'light'
        },
        colors: ['#2563eb', '#10b981'],
        stroke: { 
            width: [3, 3], 
            curve: 'smooth'
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.35,
                opacityTo: 0.02,
                stops: [0, 90, 100]
            }
        },
        series: [
            { name: 'Total Requests Influx', data: trendVol },
            { name: 'Completed Output', data: trendComp }
        ],
        xaxis: {
            categories: trendMonths,
            labels: { 
                style: { 
                    colors: '#000000', 
                    fontSize: '9px',
                    fontWeight: 700
                } 
            },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            title: { 
                text: 'Absolute Influx Volume', 
                style: { 
                    color: '#000000',
                    fontSize: '10px',
                    fontWeight: 750
                } 
            },
            labels: { style: { colors: '#000000', fontSize: '9px', fontWeight: 700 } }
        },
        legend: { 
            position: 'bottom', 
            fontSize: '11px',
            fontFamily: 'Outfit, Inter, sans-serif',
            fontWeight: 700,
            labels: { colors: '#000000' },
            markers: { radius: 12 }
        },
        grid: { 
            borderColor: '#e2e8f0',
            strokeDashArray: 4
        },
        tooltip: {
            theme: 'light'
        }
    };

    if (lineChartInstance) {
        lineChartInstance.updateOptions(lineOptions);
    } else {
        const lineEl = document.querySelector('#line-chart');
        if (lineEl) {
            lineChartInstance = new window.ApexCharts(lineEl, lineOptions);
            lineChartInstance.render();
        }
    }

    // E. Enrollment Pie Chart
    const enrollPieOptions = {
        chart: {
            type: 'donut',
            height: 250,
            fontFamily: 'Outfit, Inter, sans-serif',
            background: 'transparent',
            events: {
                dataPointSelection: (event, chartContext, config) => {
                    const idx = config.dataPointIndex;
                    if (idx !== undefined && idx !== null && idx >= 0) {
                        const statusMap = ['active', 'pending', 'completed', 'dropped'];
                        const clickedStatus = statusMap[idx];
                        if (filters.student_enrollment_status === clickedStatus) {
                            filters.student_enrollment_status = '';
                        } else {
                            filters.student_enrollment_status = clickedStatus;
                        }
                        applyFilters();
                    }
                }
            },
            dropShadow: {
                enabled: true,
                top: 4,
                left: 0,
                blur: 8,
                color: '#000',
                opacity: 0.03
            }
        },
        theme: { mode: 'light' },
        stroke: { width: 2, colors: ['#ffffff'] },
        colors: ['#10b981', '#3b82f6', '#6366f1', '#f43f5e'],
        labels: ['Active', 'Pending Review', 'Completed', 'Dropped'],
        series: [
            props.studentEnrollmentStats?.active || 0,
            props.studentEnrollmentStats?.pending || 0,
            props.studentEnrollmentStats?.completed || 0,
            props.studentEnrollmentStats?.dropped || 0
        ],
        legend: {
            position: 'bottom',
            fontSize: '11px',
            fontFamily: 'Outfit, Inter, sans-serif',
            fontWeight: 700,
            labels: { colors: '#000000' },
            markers: { radius: 12 }
        },
        dataLabels: {
            enabled: true,
            style: {
                fontSize: '10px',
                fontFamily: 'Outfit, Inter, sans-serif',
                fontWeight: 'bold'
            },
            formatter: (val) => `${Math.round(val)}%`
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '72%',
                    labels: {
                        show: true,
                        name: {
                            show: true,
                            fontSize: '11px',
                            fontWeight: 'bold',
                            color: '#000000',
                            offsetY: -4
                        },
                        value: {
                            show: true,
                            fontSize: '18px',
                            fontWeight: 'bold',
                            color: '#000000',
                            offsetY: 4,
                            formatter: (val) => val
                        },
                        total: {
                            show: true,
                            label: 'Total Students',
                            color: '#000000',
                            formatter: () => props.studentEnrollmentStats?.total || 0
                        }
                    }
                }
            }
        },
        tooltip: {
            theme: 'light',
            y: { formatter: (val) => `${val} students` }
        }
    };

    if (enrollmentPieChartInstance) {
        enrollmentPieChartInstance.updateOptions(enrollPieOptions);
    } else {
        const enrollPieEl = document.querySelector('#enrollment-pie-chart');
        if (enrollPieEl) {
            enrollmentPieChartInstance = new window.ApexCharts(enrollPieEl, enrollPieOptions);
            enrollmentPieChartInstance.render();
        }
    }

    // F. Enrollment Line Chart
    if (props.enrollmentMonthly) {
        const enrollMonths = props.enrollmentMonthly.map(m => m.month);
        const enrollCounts = props.enrollmentMonthly.map(m => m.count);

        const enrollLineOptions = {
            chart: {
                type: 'area',
                height: 250,
                toolbar: { show: false },
                fontFamily: 'Outfit, Inter, sans-serif',
                background: 'transparent',
                dropShadow: {
                    enabled: true,
                    top: 4,
                    left: 0,
                    blur: 8,
                    color: '#000',
                    opacity: 0.03
                }
            },
            theme: { mode: 'light' },
            colors: ['#6366f1'],
            stroke: { width: 3, curve: 'smooth' },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.35,
                    opacityTo: 0.02,
                    stops: [0, 90, 100]
                }
            },
            series: [
                { name: 'Enrollments Influx', data: enrollCounts }
            ],
            xaxis: {
                categories: enrollMonths,
                labels: {
                    style: {
                        colors: '#000000',
                        fontSize: '9px',
                        fontWeight: 700
                    }
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                title: {
                    text: 'Enrollment Count',
                    style: {
                        color: '#000000',
                        fontSize: '10px',
                        fontWeight: 750
                    }
                },
                labels: { style: { colors: '#000000', fontSize: '9px', fontWeight: 700 } }
            },
            legend: { show: false },
            grid: { borderColor: '#e2e8f0', strokeDashArray: 4 },
            tooltip: { theme: 'light' }
        };

        if (enrollmentLineChartInstance) {
            enrollmentLineChartInstance.updateOptions(enrollLineOptions);
        } else {
            const enrollLineEl = document.querySelector('#enrollment-line-chart');
            if (enrollLineEl) {
                enrollmentLineChartInstance = new window.ApexCharts(enrollLineEl, enrollLineOptions);
                enrollmentLineChartInstance.render();
            }
        }
    }

    chartsLoaded.value = true;
};

// Lifecycle mount
onMounted(() => {
    loadApexChartsScript()
        .then(() => {
            renderCharts();
        })
        .catch(err => {
            console.error('ApexCharts load error:', err);
        });
    triggerAnimations();
});

// Watch for props updates and refresh charts dynamically
watch(() => [props.byStatus, props.categoryTurnaround, props.monthly, props.studentEnrollmentStats, props.enrollmentMonthly], () => {
    if (window.ApexCharts) {
        renderCharts();
    }
}, { deep: true });

// Watch for totals updates and refresh counters dynamically
watch(() => props.totals, () => {
    triggerAnimations();
}, { deep: true });

// Watch for activeScope switching to trigger chart rendering post-DOM update
watch(activeScope, () => {
    nextTick(() => {
        if (window.ApexCharts) {
            renderCharts();
        }
    });
});

// Helpers
const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap');

/* Deep Space Premium Light Overrides */
:deep(.bg-gray-50) {
    background-color: #f8fafc !important;
}

:deep(main) {
    background: radial-gradient(circle at 10% 20%, rgba(79, 70, 229, 0.02) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(59, 130, 246, 0.02) 0%, transparent 40%),
                #f8fafc !important;
    color: #000000 !important;
}

:deep(header) {
    background-color: #ffffff !important;
    border-bottom: 1px solid rgba(0, 0, 0, 0.12) !important;
}

:deep(header h2), :deep(header button), :deep(header span) {
    color: #000000 !important;
}

:deep(main), :deep(header), .neo-glass-card, button, input, select, textarea {
    font-family: 'Outfit', sans-serif !important;
}

/* Glassmorphism Panel styles in Light Mode */
.neo-glass-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(0, 0, 0, 0.12);
    box-shadow: 0 4px 20px 0 rgba(0, 0, 0, 0.03);
    color: #000000 !important;
}

.neo-glass-card:hover {
    border-color: rgba(0, 0, 0, 0.1);
}

/* Custom Scrollbars */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.01);
    border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.08);
    border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(37, 99, 235, 0.25);
}

/* Animation utilities */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(16px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-up {
    animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes shimmer {
    from {
        background-position: 200% 0;
    }
    to {
        background-position: -200% 0;
    }
}

.animate-shimmer {
    background: linear-gradient(
        90deg,
        rgba(255, 255, 255, 0) 0%,
        rgba(255, 255, 255, 0.08) 20%,
        rgba(255, 255, 255, 0.15) 60%,
        rgba(255, 255, 255, 0) 100%
    );
    background-size: 200% 100%;
    animation: shimmer 2s infinite linear;
}
</style>
