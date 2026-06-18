<template>
    <Head :title="'Review Application - ' + application.full_name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Link :href="route('course-applications.index')" class="text-gray-400 hover:text-brand-blue transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    </Link>
                    <span class="font-extrabold text-[#0a1f44]">Review Enrollment Application</span>
                </div>
            </div>
        </template>

        <div class="space-y-6 max-w-6xl mx-auto">
            <!-- Stepper showing application timeline progress -->
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                <div class="relative flex justify-between items-center max-w-3xl mx-auto py-4">
                    <!-- Connector line -->
                    <div class="absolute left-0 right-0 top-1/2 -translate-y-1/2 h-1 bg-gray-150 rounded -z-0">
                        <div class="h-full bg-[#0a1f44] transition-all duration-500" :style="{ width: progressPercent + '%' }"></div>
                    </div>

                    <!-- Step 1: Submission -->
                    <div class="z-10 flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm shadow-sm transition border-2"
                             :class="stepClass(1)">
                            1
                        </div>
                        <span class="text-[10px] font-black uppercase mt-2" :class="stepTextClass(1)">Submitted</span>
                    </div>

                    <!-- Step 2: Verification -->
                    <div class="z-10 flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm shadow-sm transition border-2"
                             :class="stepClass(2)">
                            2
                        </div>
                        <span class="text-[10px] font-black uppercase mt-2" :class="stepTextClass(2)">Verified</span>
                    </div>

                    <!-- Step 3: Recommendation -->
                    <div class="z-10 flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm shadow-sm transition border-2"
                             :class="stepClass(3)">
                            3
                        </div>
                        <span class="text-[10px] font-black uppercase mt-2" :class="stepTextClass(3)">Recommended</span>
                    </div>

                    <!-- Step 4: Final Approval -->
                    <div class="z-10 flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm shadow-sm transition border-2"
                             :class="stepClass(4)">
                            4
                        </div>
                        <span class="text-[10px] font-black uppercase mt-2" :class="stepTextClass(4)">Approved</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Details Panels (2 Columns) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Student Profile Panel -->
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="bg-gradient-to-r from-[#0a1f44] to-[#0d2859] p-6 text-white">
                            <h3 class="text-lg font-black uppercase tracking-wider text-[#f5c242]">Student Application Profile</h3>
                            <p class="text-xs text-gray-300">Basic identification details and intake preferences</p>
                        </div>
                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-wider block">Full Name</span>
                                <span class="text-base font-extrabold text-brand-blue block mt-0.5">{{ application.full_name }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-wider block">National ID Number</span>
                                <span class="text-sm font-semibold text-gray-700 block mt-0.5">{{ application.national_id_number }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-wider block">Email Address</span>
                                <span class="text-sm font-semibold text-gray-700 block mt-0.5">{{ application.email }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-wider block">Phone Number</span>
                                <span class="text-sm font-semibold text-gray-700 block mt-0.5">{{ application.phone }}</span>
                            </div>
                            <div class="md:col-span-2">
                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-wider block">Physical Address</span>
                                <span class="text-sm text-gray-600 block mt-0.5 leading-relaxed bg-gray-50 p-3 rounded-xl border border-gray-150 font-medium">
                                    {{ application.physical_address }}
                                </span>
                            </div>
                            
                            <div class="md:col-span-2 border-t border-gray-100 pt-4 grid grid-cols-1 md:grid-cols-2 gap-4 bg-amber-50/20 p-4 rounded-xl">
                                <div>
                                    <span class="text-[10px] font-black text-brand-gold-dark uppercase tracking-wider block">Applying Course Intake</span>
                                    <span class="text-sm font-extrabold text-[#0a1f44] block mt-0.5">{{ application.intake?.course?.title }}</span>
                                    <span class="text-[11px] text-gray-500 font-medium">Batch: {{ application.intake?.name }} | Code: {{ application.intake?.course?.code }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-black text-brand-gold-dark uppercase tracking-wider block">Tuition Price Details</span>
                                    <span class="text-base font-black text-brand-blue block mt-0.5">{{ application.intake?.course?.currency }} {{ Number(application.intake?.course?.price).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Uploaded Documents Viewer -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- National ID Copy -->
                        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4 flex flex-col justify-between">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 text-brand-blue">
                                    <svg class="w-5 h-5 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5L10 6z"/></svg>
                                    <h4 class="font-bold text-sm uppercase tracking-wide">National ID Copy</h4>
                                </div>
                                <p class="text-xs text-gray-400">Submitted copy of applicant identity document.</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-150 text-center py-8">
                                <svg class="w-10 h-10 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span class="text-xs font-bold text-gray-500 block truncate">{{ fileName(application.national_id_copy_path) }}</span>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" @click="triggerPreview('National ID', application.national_id_copy_path, 'national_id')"
                                        class="flex-1 text-center bg-gray-50 hover:bg-[#0a1f44] hover:text-white text-[#0a1f44] font-bold text-xs py-2.5 rounded-xl border border-brand-blue/10 transition shadow-sm block">
                                    Preview
                                </button>
                                <a :href="route('files.download', { type: 'course_application', id: application.id, field: 'national_id' })"
                                   class="flex-1 text-center bg-green-55 hover:bg-green-700 hover:text-white text-green-700 border border-green-200 font-bold text-xs py-2.5 rounded-xl transition shadow-sm block">
                                    Download
                                </a>
                            </div>
                        </div>

                        <!-- Proof of Payment -->
                        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4 flex flex-col justify-between">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 text-brand-blue">
                                    <svg class="w-5 h-5 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <h4 class="font-bold text-sm uppercase tracking-wide">Proof of Payment</h4>
                                </div>
                                <p class="text-xs text-gray-400">Receipt submitted for tuition bank deposit.</p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-150 text-center py-8">
                                <svg class="w-10 h-10 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span class="text-xs font-bold text-gray-500 block truncate">{{ fileName(application.payment_proof_path) }}</span>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" @click="triggerPreview('Proof of Payment', application.payment_proof_path, 'payment_proof')"
                                        class="flex-1 text-center bg-gray-50 hover:bg-[#0a1f44] hover:text-white text-[#0a1f44] font-bold text-xs py-2.5 rounded-xl border border-brand-blue/10 transition shadow-sm block">
                                    Preview
                                </button>
                                <a :href="route('files.download', { type: 'course_application', id: application.id, field: 'payment_proof' })"
                                   class="flex-1 text-center bg-green-55 hover:bg-green-700 hover:text-white text-green-700 border border-green-200 font-bold text-xs py-2.5 rounded-xl transition shadow-sm block">
                                    Download
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Actions and History Panels (1 Column) -->
                <div class="space-y-6">
                    <!-- Workflow Action Forms (Based on Roles) -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-lg space-y-4 relative overflow-hidden"
                         v-if="application.status !== 'approved' && application.status !== 'enrolled' && application.status !== 'rejected'">
                        
                        <div class="border-b border-gray-100 pb-3">
                            <h3 class="font-black text-brand-blue text-sm uppercase tracking-wide">Review Actions</h3>
                            <p class="text-[11px] text-gray-400">Current Pipeline Status: <strong class="text-brand-gold-dark uppercase">{{ application.status }}</strong></p>
                        </div>

                        <!-- 1. Verification Section: Administrative Assistant or ICT Admin -->
                        <div v-if="application.status === 'pending' && canVerify" class="space-y-4">
                            <div class="bg-amber-50 p-3 rounded-xl border border-amber-200 text-xs text-amber-800 leading-relaxed">
                                <strong>Step 1: Application Verification</strong><br>
                                Verify ID numbers and check payment uploads. Set a temporary login password for the student.
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-700 uppercase mb-1">Temporary Password *</label>
                                    <input v-model="verifyForm.temporary_password" type="text" class="w-full rounded-xl text-xs border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="e.g. Welcome2026!" />
                                    <button @click="generatePassword" type="button" class="text-[10px] text-brand-gold-dark font-black uppercase mt-1 hover:text-brand-blue transition">Generate Random</button>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-700 uppercase mb-1">Review Comments</label>
                                    <textarea v-model="verifyForm.comment" rows="2" class="w-full rounded-xl text-xs border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="Add custom review notes..."></textarea>
                                </div>
                                <button @click="submitVerification" :disabled="submitting" class="w-full bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold text-xs py-2.5 rounded-full transition shadow flex items-center justify-center gap-1.5 disabled:opacity-50">
                                    <svg v-if="!submitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span v-if="submitting">Processing verification...</span>
                                    <span v-else>Verify & Forward</span>
                                </button>
                            </div>
                        </div>

                        <!-- 2. Recommendation Section: Deputy Director -->
                        <div v-if="application.status === 'verified' && canRecommend" class="space-y-4">
                            <div class="bg-purple-50 p-3 rounded-xl border border-purple-200 text-xs text-purple-800 leading-relaxed">
                                <strong>Step 2: Deputy Director Recommendation</strong><br>
                                Review administrative logs and recommend this candidate for enrollment.
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-700 uppercase mb-1">Recommendation Comments</label>
                                    <textarea v-model="recommendForm.comment" rows="2" class="w-full rounded-xl text-xs border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="Optional recommendation notes..."></textarea>
                                </div>
                                <button @click="submitRecommendation" :disabled="submitting" class="w-full bg-purple-700 hover:bg-purple-800 text-white font-bold text-xs py-2.5 rounded-full transition shadow flex items-center justify-center gap-1.5 disabled:opacity-50">
                                    <svg v-if="!submitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span v-if="submitting">Processing recommendation...</span>
                                    <span v-else>Recommend to Director</span>
                                </button>
                            </div>
                        </div>

                        <!-- 3. Approval Section: Executive Director (Director) -->
                        <div v-if="application.status === 'recommended' && canApprove" class="space-y-4">
                            <div class="bg-green-50 p-3 rounded-xl border border-green-200 text-xs text-green-800 leading-relaxed">
                                <strong>Step 3: Executive Director Approval</strong><br>
                                Grants final validation. This automatically creates their student account, schedules active course intake, and sends their welcome credentials.
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-700 uppercase mb-1">Approval Comments</label>
                                    <textarea v-model="approveForm.comment" rows="2" class="w-full rounded-xl text-xs border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="Optional final approval notes..."></textarea>
                                </div>
                                <button @click="submitApproval" :disabled="submitting" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold text-xs py-2.5 rounded-full transition shadow flex items-center justify-center gap-1.5 disabled:opacity-50">
                                    <svg v-if="!submitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span v-if="submitting">Processing final approval...</span>
                                    <span v-else>Approve & Activate Student</span>
                                </button>
                            </div>
                        </div>

                        <!-- 4. Global Rejection Action -->
                        <div class="pt-4 border-t border-gray-100 space-y-3">
                            <button @click="rejectionPanelOpen = !rejectionPanelOpen" class="w-full bg-gray-50 hover:bg-red-50 text-gray-500 hover:text-red-700 font-bold text-[10px] uppercase py-2 rounded-xl border border-dashed transition flex items-center justify-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>{{ rejectionPanelOpen ? 'Hide Rejection Panel' : 'Cancel/Reject Application' }}</span>
                            </button>

                            <div v-if="rejectionPanelOpen" class="bg-red-50/50 p-4 rounded-xl border border-red-200/50 space-y-3">
                                <div>
                                    <label class="block text-[10px] font-black text-red-800 uppercase mb-1">Rejection Reason *</label>
                                    <textarea v-model="rejectForm.rejection_reason" rows="2" class="w-full rounded-xl text-xs border-red-300 focus:border-red-500 focus:ring-red-500 shadow-sm" placeholder="Why is this application being rejected?..."></textarea>
                                </div>
                                <button @click="submitRejection" :disabled="submitting" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold text-xs py-2 rounded-full transition shadow disabled:opacity-50">
                                    <span v-if="submitting">Processing rejection...</span>
                                    <span v-else>Confirm Rejection</span>
                                </button>
                            </div>
                        </div>

                    </div>

                    <!-- Post-Pipeline Status Card -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm text-center space-y-3"
                         v-else>
                        <div class="w-12 h-12 rounded-full flex items-center justify-center mx-auto text-xl"
                             :class="['approved', 'enrolled'].includes(application.status) ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                            <span v-if="['approved', 'enrolled'].includes(application.status)">&check;</span>
                            <span v-else>&times;</span>
                        </div>
                        <div>
                            <h4 class="font-extrabold text-brand-blue uppercase text-xs tracking-wide">Review Completed</h4>
                            <p class="text-sm font-black mt-1" :class="['approved', 'enrolled'].includes(application.status) ? 'text-green-700' : 'text-red-700'">
                                {{ ['approved', 'enrolled'].includes(application.status) ? 'Approved & Enrolled' : 'Rejected' }}
                            </p>
                            <p class="text-xs text-gray-400 mt-1 leading-relaxed" v-if="application.rejection_reason">
                                Reason: "{{ application.rejection_reason }}"
                            </p>
                        </div>
                    </div>

                    <!-- Audited History Timeline Widget -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
                        <h3 class="font-black text-brand-blue text-sm uppercase tracking-wide border-b border-gray-100 pb-3">Approval History Log</h3>
                        
                        <div class="space-y-4">
                            <div v-for="log in application.logs" :key="log.id" class="flex gap-3 relative">
                                <div class="w-2.5 h-2.5 rounded-full bg-brand-gold mt-1.5 flex-shrink-0 z-10 shadow-sm"></div>
                                <div class="flex-1 space-y-1">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-bold text-[#0a1f44]">{{ log.user?.name }}</span>
                                        <span class="text-[9px] text-gray-400 font-medium">{{ formatTime(log.created_at) }}</span>
                                    </div>
                                    <div class="text-[10px] font-black uppercase text-brand-gold-dark">
                                        Action: {{ log.action }}
                                    </div>
                                    <p class="text-[11px] text-gray-600 italic bg-gray-50 p-2 rounded-lg border border-gray-100" v-if="log.comment">
                                        "{{ log.comment }}"
                                    </p>
                                </div>
                            </div>
                            <div v-if="!application.logs || application.logs.length === 0" class="text-xs text-gray-400 italic py-2">
                                No audit log records yet.
                            </div>
                        </div>
                    </div>
                    <!-- Email Logs Verification Widget -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-150 shadow-sm space-y-4">
                        <h3 class="font-black text-brand-blue text-sm uppercase tracking-wide border-b border-gray-100 pb-3 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l8-4.62a2 2 0 012.22 0l8 4.62A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-2.25-1.5a2 2 0 00-2.22 0l-2.25 1.5"/></svg>
                            Email Dispatch Audit Trails
                        </h3>
                        
                        <div class="space-y-4">
                            <div v-for="log in emailLogs" :key="log.id" class="p-3 bg-gray-50 border border-gray-100 rounded-xl space-y-2">
                                <div class="flex justify-between items-start">
                                    <div class="space-y-0.5">
                                        <span class="text-xs font-bold text-[#0a1f44] block">{{ log.subject }}</span>
                                        <span class="text-[10px] text-gray-400 font-medium block">To: {{ log.recipient_email }}</span>
                                    </div>
                                    <span :class="log.status === 'sent' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider">
                                        {{ log.status }}
                                    </span>
                                </div>
                                <div class="text-[9px] text-gray-400 font-medium">
                                    Dispatched: {{ formatTime(log.created_at) }}
                                </div>
                                <div v-if="log.status === 'failed'" class="mt-1.5 p-2 bg-red-50 text-[10px] text-red-700 rounded-lg border border-red-100">
                                    <strong>Troubleshooting:</strong> {{ log.error_message }}
                                </div>
                            </div>
                            <div v-if="!emailLogs || emailLogs.length === 0" class="text-xs text-gray-400 italic py-2">
                                No email transmission records found.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Universal Document Preview Modal -->
        <div v-if="previewModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/65 backdrop-blur-sm" @click.self="closePreviewModal">
            <div class="bg-white rounded-2xl w-full max-w-4xl shadow-2xl overflow-hidden border border-gray-100 h-[80vh] flex flex-col">
                <div class="bg-[#0a1f44] text-white px-6 py-4 flex justify-between items-center border-b border-[#f5c242]/20">
                    <div>
                        <h3 class="text-base font-bold text-[#f5c242] uppercase tracking-wide">Preview Material: {{ previewTitle }}</h3>
                        <p class="text-[10px] text-gray-300 mt-0.5">Document Viewer ({{ previewFileName }})</p>
                    </div>
                    <button @click="closePreviewModal" class="text-gray-300 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex-grow p-4 bg-gray-100 flex items-center justify-center overflow-auto relative">
                    <div v-if="previewLoading" class="absolute inset-0 bg-white/50 flex items-center justify-center z-10">
                        <div class="animate-spin rounded-full h-10 w-10 border-4 border-[#0a1f44] border-t-transparent"></div>
                    </div>

                    <iframe v-if="isPdf(previewMime)" :src="previewSrc" class="w-full h-full rounded border-0" @load="previewLoading = false"></iframe>
                    <img v-else-if="isImage(previewMime)" :src="previewSrc" class="max-w-full max-h-full object-contain rounded shadow" @load="previewLoading = false" />
                    <video v-else-if="isVideo(previewMime)" :src="previewSrc" controls autoplay class="max-w-full max-h-full rounded shadow" @loadeddata="previewLoading = false"></video>

                    <div v-else class="text-center p-8 bg-white rounded-xl shadow-sm border border-gray-150 max-w-md">
                        <svg class="w-12 h-12 text-amber-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <h4 class="font-extrabold text-brand-blue text-sm">Preview Not Supported Inline</h4>
                        <p class="text-xs text-gray-500 mt-1">Word Documents, Excel, and PowerPoints cannot be previewed natively in the web browser.</p>
                        <div class="mt-4 flex gap-2 justify-center">
                            <a :href="previewSrc" target="_blank" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-xl transition">
                                Open in New Tab
                            </a>
                            <a :href="previewDownloadSrc" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-bold text-xs rounded-xl transition shadow">
                                Download File
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/inertia-vue3';
import { ref, computed, reactive } from 'vue';
import { Inertia } from '@inertiajs/inertia';

const props = defineProps({
    application: Object,
    emailLogs: Array,
});

const page = usePage();
const submitting = ref(false);

// Role checking computed fields
const userRoles = computed(() => page.props.value.auth.roles || []);

const canVerify = computed(() => {
    return userRoles.value.includes('ict_administrator') || userRoles.value.includes('admin_assistant');
});

const canRecommend = computed(() => {
    return userRoles.value.includes('deputy_director');
});

const canApprove = computed(() => {
    return userRoles.value.includes('executive_director');
});

// Rejection Drawer
const rejectionPanelOpen = ref(false);

// Active forms reactive bindings
const verifyForm = reactive({
    temporary_password: '',
    comment: '',
});

const recommendForm = reactive({
    comment: '',
});

const approveForm = reactive({
    comment: '',
});

const rejectForm = reactive({
    rejection_reason: '',
});

// Progress computation
const progressPercent = computed(() => {
    switch (props.application.status) {
        case 'approved':
        case 'enrolled':
            return 100;
        case 'recommended':
            return 66;
        case 'verified':
            return 33;
        case 'pending':
        default:
            return 0;
    }
});

const generatePassword = () => {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*';
    let pass = '';
    for (let i = 0; i < 10; i++) {
        pass += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    verifyForm.temporary_password = pass;
};

// Form submission calls
const submitVerification = () => {
    if (!verifyForm.temporary_password) {
        alert('Please assign a temporary password to verify.');
        return;
    }
    submitting.value = true;
    Inertia.post(route('course-applications.verify', props.application.id), verifyForm, {
        onBefore: () => { submitting.value = true; },
        onFinish: () => { submitting.value = false; },
        onError: () => { submitting.value = false; }
    });
};

const submitRecommendation = () => {
    submitting.value = true;
    Inertia.post(route('course-applications.recommend', props.application.id), recommendForm, {
        onBefore: () => { submitting.value = true; },
        onFinish: () => { submitting.value = false; },
        onError: () => { submitting.value = false; }
    });
};

const submitApproval = () => {
    submitting.value = true;
    Inertia.post(route('course-applications.approve', props.application.id), approveForm, {
        onBefore: () => { submitting.value = true; },
        onFinish: () => { submitting.value = false; },
        onError: () => { submitting.value = false; }
    });
};

const submitRejection = () => {
    if (!rejectForm.rejection_reason) {
        alert('Please provide a reason for rejecting this application.');
        return;
    }
    submitting.value = true;
    Inertia.post(route('course-applications.reject', props.application.id), rejectForm, {
        onBefore: () => { submitting.value = true; },
        onFinish: () => { submitting.value = false; },
        onError: () => { submitting.value = false; }
    });
};

// Design helper classes
const stepClass = (stepNum) => {
    const status = props.application.status;
    let isActive = false;
    let isCompleted = false;

    if (stepNum === 1) isCompleted = true; // Always submitted
    if (stepNum === 2 && ['verified', 'recommended', 'approved', 'enrolled'].includes(status)) isCompleted = true;
    if (stepNum === 3 && ['recommended', 'approved', 'enrolled'].includes(status)) isCompleted = true;
    if (stepNum === 4 && ['approved', 'enrolled'].includes(status)) isCompleted = true;

    if (stepNum === 1 && status === 'pending') isActive = true;
    if (stepNum === 2 && status === 'verified') isActive = true;
    if (stepNum === 3 && status === 'recommended') isActive = true;
    if (stepNum === 4 && ['approved', 'enrolled'].includes(status)) isActive = true;

    if (props.application.status === 'rejected') {
        return 'bg-red-50 text-red-600 border-red-300';
    }

    if (isCompleted) {
        return 'bg-[#0a1f44] text-[#f5c242] border-[#0a1f44]';
    }
    if (isActive) {
        return 'bg-amber-100 text-brand-gold-dark border-brand-gold-dark animate-pulse';
    }
    return 'bg-white text-gray-400 border-gray-200';
};

const stepTextClass = (stepNum) => {
    const status = props.application.status;
    let isCompleted = false;

    if (stepNum === 1) isCompleted = true;
    if (stepNum === 2 && ['verified', 'recommended', 'approved', 'enrolled'].includes(status)) isCompleted = true;
    if (stepNum === 3 && ['recommended', 'approved', 'enrolled'].includes(status)) isCompleted = true;
    if (stepNum === 4 && ['approved', 'enrolled'].includes(status)) isCompleted = true;

    if (status === 'rejected') return 'text-red-600';
    return isCompleted ? 'text-brand-blue font-black' : 'text-gray-400';
};

const fileName = (path) => {
    if (!path) return 'N/A';
    return path.split('/').pop();
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatTime = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};

// Preview handlers
const previewModalOpen = ref(false);
const previewTitle = ref('');
const previewFileName = ref('');
const previewMime = ref('');
const previewSrc = ref('');
const previewDownloadSrc = ref('');
const previewLoading = ref(false);

const triggerPreview = (title, path, field) => {
    previewTitle.value = title;
    previewFileName.value = path ? path.split('/').pop() : '';
    
    const ext = previewFileName.value.split('.').pop().toLowerCase();
    let mime = 'application/octet-stream';
    if (ext === 'pdf') mime = 'application/pdf';
    else if (['jpg', 'jpeg'].includes(ext)) mime = 'image/jpeg';
    else if (ext === 'png') mime = 'image/png';
    else if (ext === 'mp4') mime = 'video/mp4';
    
    previewMime.value = mime;
    previewSrc.value = route('files.preview', { type: 'course_application', id: props.application.id, field: field });
    previewDownloadSrc.value = route('files.download', { type: 'course_application', id: props.application.id, field: field });
    
    const isInline = ['pdf', 'jpg', 'jpeg', 'png', 'mp4'].includes(ext);
    previewLoading.value = isInline;
    previewModalOpen.value = true;
};

const closePreviewModal = () => {
    previewModalOpen.value = false;
    previewTitle.value = '';
    previewFileName.value = '';
    previewMime.value = '';
    previewSrc.value = '';
    previewDownloadSrc.value = '';
};

const isPdf = (mime) => mime === 'application/pdf';
const isImage = (mime) => mime.startsWith('image/');
const isVideo = (mime) => mime.startsWith('video/');
</script>
