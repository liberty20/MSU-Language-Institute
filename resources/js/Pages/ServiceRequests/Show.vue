<template>
    <Head :title="`Request ${serviceRequest.reference_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('service-requests.index')" class="text-gray-400 hover:text-[#0a1f44] transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </Link>
                    <span class="text-gray-400">/</span>
                    <span>{{ serviceRequest.reference_number }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide"
                        :class="{
                            'bg-yellow-100 text-yellow-800': displayStatus === 'pending',
                            'bg-blue-100 text-blue-800': displayStatus === 'in_progress',
                            'bg-green-100 text-green-800': displayStatus === 'completed',
                            'bg-purple-100 text-purple-800': displayStatus === 'quoted',
                            'bg-teal-100 text-teal-800': displayStatus === 'approved',
                            'bg-orange-100 text-orange-800': displayStatus === 'review',
                            'bg-gray-100 text-gray-700': displayStatus === 'cancelled',
                            'bg-indigo-100 text-indigo-800': displayStatus === 'pending_coordinator_action',
                            'bg-emerald-100 text-emerald-800': displayStatus === 'delivered',
                            'bg-cyan-100 text-cyan-800': displayStatus === 'assigned',
                        }">
                        {{ displayStatusLabel }}
                    </span>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Request Details -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#0a1f44]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Request Details
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Title</p>
                            <p class="text-gray-900 font-medium">{{ serviceRequest.title }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Description</p>
                            <p class="text-gray-700 whitespace-pre-wrap leading-relaxed">{{ serviceRequest.description }}</p>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 pt-2 border-t border-gray-100">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Service Category</p>
                                <p class="text-gray-800 font-medium capitalize">{{ serviceRequest.service_category.replace(/_/g, ' ') }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Priority</p>
                                <span class="px-2.5 py-1 rounded text-xs font-bold uppercase"
                                    :class="{
                                        'bg-red-100 text-red-800': serviceRequest.priority === 'urgent',
                                        'bg-orange-100 text-orange-800': serviceRequest.priority === 'high',
                                        'bg-blue-100 text-blue-800': serviceRequest.priority === 'medium',
                                        'bg-gray-100 text-gray-600': serviceRequest.priority === 'low',
                                    }">
                                    {{ serviceRequest.priority }}
                                </span>
                            </div>
                            <div v-if="serviceRequest.deadline">
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Deadline</p>
                                <p class="text-gray-800 font-medium">{{ serviceRequest.deadline }}</p>
                            </div>
                            <div v-if="serviceRequest.source_language">
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Source Language</p>
                                <p class="text-gray-800 font-medium">{{ serviceRequest.source_language }}</p>
                            </div>
                            <div v-if="serviceRequest.target_language && (Array.isArray(serviceRequest.target_language) ? serviceRequest.target_language.length > 0 : serviceRequest.target_language)">
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Target Language(s)</p>
                                <p class="text-gray-800 font-medium">{{ Array.isArray(serviceRequest.target_language) ? serviceRequest.target_language.join(', ') : serviceRequest.target_language }}</p>
                            </div>
                            <div v-if="serviceRequest.estimated_hours">
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Est. Hours</p>
                                <p class="text-gray-800 font-medium">{{ serviceRequest.estimated_hours }}h</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assignments -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#0a1f44]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Assignments
                        </h2>
                        <Link v-if="canManage || $page.props.auth.roles.includes('coordinator') || $page.props.auth.roles.includes('deputy_director') || $page.props.auth.roles.includes('executive_director')" :href="route('assignments.create', { service_request_id: serviceRequest.id })"
                              class="text-xs bg-[#0a1f44] text-white px-3 py-1.5 rounded-lg hover:bg-[#0a1f44]/80 transition font-medium">
                            + Assign Staff
                        </Link>
                    </div>
                    <div v-if="serviceRequest.assignments && serviceRequest.assignments.length > 0" class="space-y-3">
                        <div v-for="assignment in serviceRequest.assignments" :key="assignment.id"
                             class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-[#0a1f44] flex items-center justify-center text-white font-bold text-sm">
                                    {{ (assignment.assigned_to?.name || assignment.assignee?.name || 'U').charAt(0) }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 text-sm">{{ assignment.assigned_to?.name || assignment.assignee?.name }}</p>
                                    <p class="text-xs text-gray-500 capitalize">{{ assignment.role_in_task }}</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium capitalize"
                                :class="{
                                    'bg-blue-100 text-blue-700': assignment.status === 'in_progress',
                                    'bg-green-100 text-green-700': assignment.status === 'completed',
                                    'bg-yellow-100 text-yellow-700': assignment.status === 'assigned',
                                }">
                                {{ assignment.status.replace(/_/g, ' ') }}
                            </span>
                        </div>
                    </div>
                    <p v-else class="text-gray-500 text-sm">No staff assigned yet.</p>
                </div>

                <!-- Attachments Board -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6">
                    <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2 border-b border-gray-50 pb-3">
                        <svg class="w-5 h-5 text-[#0a1f44]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                        </svg>
                        Request Attachments & Source Files
                    </h2>

                    <!-- Client Upload Form -->
                    <form v-if="$page.props.auth.roles.includes('client') && serviceRequest.status !== 'completed'" @submit.prevent="submitAttachment" class="space-y-4 bg-gray-50/50 p-4 rounded-xl border border-gray-150">
                        <div class="text-xs font-bold text-gray-600 uppercase tracking-wider">Add New Attachment</div>
                        <div class="flex flex-col sm:flex-row gap-3 items-end">
                            <div class="flex-1 w-full space-y-1">
                                <input type="file" @change="handleAttachFileChange" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-[#0a1f44] hover:file:bg-blue-100 cursor-pointer" />
                                <input v-model="attachForm.description" placeholder="Brief description (e.g., source document PDF)" class="w-full text-xs border-gray-300 rounded-lg focus:border-brand-blue focus:ring-brand-blue" />
                            </div>
                            <button type="submit" :disabled="attachForm.processing || !attachForm.file" class="bg-[#0a1f44] hover:bg-[#152a4d] disabled:opacity-50 text-white text-xs font-bold px-4 py-2.5 rounded-lg transition shadow-sm shrink-0">
                                Upload
                            </button>
                        </div>
                        <div v-if="attachForm.errors.file" class="text-red-500 text-xs font-semibold mt-1">{{ attachForm.errors.file }}</div>
                    </form>

                    <!-- List of Request Files -->
                    <div class="space-y-4">
                        <!-- Source documents -->
                        <div>
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Source Documents</h3>
                            <div v-if="!serviceRequest.documents?.length" class="text-xs text-gray-500 italic">No source files uploaded.</div>
                            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div v-for="doc in serviceRequest.documents" :key="doc.id" class="flex items-center justify-between p-3 rounded-xl border border-gray-150 bg-white shadow-sm">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-[#0a1f44] flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold text-gray-900 truncate" :title="doc.filename">{{ doc.filename }}</p>
                                            <p class="text-[10px] text-gray-400 font-medium truncate">{{ doc.description || 'Client upload' }} • By {{ doc.uploader?.name || 'Client' }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <button type="button" @click="openPreview(doc)" class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-xs font-bold transition border border-blue-150" title="Preview">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            <span>Preview</span>
                                        </button>
                                        <a :href="route('documents.download', { document: doc.id, download: 1 })" class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-50 hover:bg-gray-100 text-gray-700 rounded-lg text-xs font-bold transition border border-gray-250" title="Download">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                            <span>Download</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Staff deliverables -->
                        <div class="pt-4 border-t border-gray-100">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Completed Deliverables</h3>
                            <div v-if="!staffDeliverables.length" class="text-xs text-gray-500 italic">No final deliverables submitted yet.</div>
                            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div v-for="doc in staffDeliverables" :key="doc.id" class="flex items-center justify-between p-3 rounded-xl border border-green-150 bg-green-50/10 shadow-sm">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-8 h-8 rounded-lg bg-green-100 text-green-700 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold text-gray-900 truncate" :title="doc.filename">{{ doc.filename }}</p>
                                            <p class="text-[10px] text-gray-500 font-medium truncate">{{ doc.description || 'Completed Deliverable' }} • By {{ doc.uploader?.name || 'Staff' }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <button type="button" @click="openPreview(doc)" class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-50 hover:bg-green-100 text-green-700 rounded-lg text-xs font-bold transition border border-green-150" title="Preview">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            <span>Preview</span>
                                        </button>
                                        <a :href="route('documents.download', { document: doc.id, download: 1 })" class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-bold transition border border-green-700 shadow-sm" title="Download">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                            <span>Download</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quotations -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#0a1f44]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Quotations
                        </h2>
                        <Link v-if="canCreateQuotation" :href="route('quotations.create', { service_request_id: serviceRequest.id })"
                              class="text-xs bg-[#f5c242] text-[#0a1f44] px-3 py-1.5 rounded-lg hover:bg-yellow-400 transition font-bold">
                            + Create Quotation
                        </Link>
                    </div>

                    <div v-if="serviceRequest.quotations && serviceRequest.quotations.length > 0" class="space-y-3">
                        <div v-for="quotation in serviceRequest.quotations" :key="quotation.id"
                             class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-[#0a1f44] flex items-center justify-center text-white font-bold text-sm">
                                    Q
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 text-sm">Quotation #{{ quotation.reference_number || quotation.id }}</p>
                                    <p class="text-xs text-gray-500">Amount: {{ quotation.currency }} {{ Number(quotation.amount).toFixed(2) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium capitalize"
                                    :class="getQuotationStatus(quotation).class">
                                    {{ getQuotationStatus(quotation).label }}
                                </span>
                                <Link :href="route('quotations.show', quotation.id)" class="text-xs text-[#0a1f44] hover:text-[#f5c242] font-semibold transition">
                                    View Details →
                                </Link>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-gray-500 text-sm">No quotations created yet.</p>
                </div>

                <!-- CC Reviews History Timeline -->
                <div v-if="!$page.props.auth.roles.includes('client') && serviceRequest.cc_reviews && serviceRequest.cc_reviews.length > 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
                    <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2 mb-4">
                        <svg class="w-5 h-5 text-[#0a1f44]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Internal Review History (CC)
                    </h2>
                    <div class="flow-root">
                        <ul role="list" class="-mb-8">
                            <li v-for="(review, rIdx) in serviceRequest.cc_reviews" :key="review.id">
                                <div class="relative pb-8">
                                    <span v-if="rIdx !== serviceRequest.cc_reviews.length - 1" class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white" :class="review.status === 'reviewed' ? 'bg-green-100 text-green-700' : (review.status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500')">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4" v-if="review.status === 'reviewed'"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" v-else-if="review.status === 'rejected'"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3" v-else/>
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="flex-1 min-w-0 pt-1.5 flex justify-between space-x-4">
                                            <div>
                                                <p class="text-xs text-gray-500">
                                                    CC Review requested to <span class="font-semibold text-gray-900">{{ review.reviewer?.name }}</span> by <span class="font-semibold text-gray-900">{{ review.sender?.name }}</span>
                                                </p>
                                                <p v-if="review.comments" class="text-xs text-gray-700 italic mt-1 bg-gray-50 p-2 rounded-lg border border-gray-100">
                                                    "{{ review.comments }}"
                                                </p>
                                            </div>
                                            <div class="text-right text-[10px] whitespace-nowrap text-gray-400">
                                                <span class="px-2 py-0.5 rounded-full capitalize font-semibold" :class="review.status === 'reviewed' ? 'bg-green-50 text-green-600 border border-green-100' : (review.status === 'rejected' ? 'bg-red-50 text-red-600 border border-red-100' : 'bg-yellow-50 text-yellow-600 border border-yellow-100')">
                                                    {{ review.status }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">

                <!-- Client Info -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Client</h3>
                    <div v-if="serviceRequest.client">
                        <p class="font-bold text-gray-900">{{ serviceRequest.client.organization || serviceRequest.client.contact_person }}</p>
                        <p class="text-sm text-gray-500 mt-1">{{ serviceRequest.client.email }}</p>
                        <p class="text-sm text-gray-500">{{ serviceRequest.client.phone }}</p>
                        <p class="text-xs capitalize bg-blue-50 text-blue-700 px-2 py-1 rounded mt-2 inline-block font-medium">{{ serviceRequest.client.client_type }}</p>
                        <Link :href="route('clients.show', serviceRequest.client.id)"
                              class="block mt-3 text-xs text-[#0a1f44] hover:text-[#f5c242] font-medium transition">
                            View Client Profile →
                        </Link>
                    </div>
                </div>

                <!-- Meta Info -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Details</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Submitted by</span>
                            <span class="font-medium text-gray-900">{{ serviceRequest.submitted_by?.name || serviceRequest.submitter?.name || 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Assigned to</span>
                            <span class="font-medium text-gray-900">{{ serviceRequest.assignedTo?.name || 'Unassigned' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Created</span>
                            <span class="font-medium text-gray-900">{{ new Date(serviceRequest.created_at).toLocaleDateString() }}</span>
                        </div>
                        <div v-if="serviceRequest.completed_at" class="flex justify-between">
                            <span class="text-gray-500">Completed</span>
                            <span class="font-medium text-green-700">{{ new Date(serviceRequest.completed_at).toLocaleDateString() }}</span>
                        </div>
                    </div>
                </div>

                <!-- 1. Admin Assistant Client Delivery Card -->
                <div v-if="serviceRequest.status === 'admin_submission' && $page.props.auth.roles.includes('admin_assistant')" class="bg-gradient-to-br from-green-50 to-emerald-100/50 border border-green-200 rounded-2xl p-6 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-green-800 uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        Submit Deliverable to Client
                    </h3>
                    <p class="text-xs text-gray-700 leading-relaxed">
                        The Director/Deputy Director has approved this deliverable. Review the files on the left and submit them to the client.
                    </p>

                    <!-- Lock Warning if payment is not verified yet -->
                    <div v-if="!hasVerifiedPayment" class="p-3 bg-amber-50 border border-amber-250 rounded-xl flex items-start gap-2.5">
                        <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div class="text-xs">
                            <p class="font-bold text-amber-850">Delivery Locked</p>
                            <p class="text-amber-750 mt-0.5">Client has not uploaded proof of payment or the transaction is pending verification in the Finance module.</p>
                        </div>
                    </div>

                    <form @submit.prevent="deliverCompletedTask" class="space-y-3">
                        <textarea v-model="deliveryForm.notes" placeholder="Optional delivery notes for the client..." rows="2" class="w-full text-xs border-gray-300 rounded-lg focus:border-brand-blue focus:ring-brand-blue" :disabled="!hasVerifiedPayment"></textarea>
                        <button type="submit" :disabled="deliveryForm.processing || !hasVerifiedPayment" class="w-full bg-green-600 text-white hover:bg-green-700 disabled:opacity-50 px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm flex items-center justify-center gap-2">
                            <span v-if="deliveryForm.processing" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            <span>Deliver to Client</span>
                        </button>
                    </form>
                </div>

                <!-- 2. Director Approval Card -->
                <div v-if="serviceRequest.status === 'director_approval' && (['executive_director', 'deputy_director'].some(r => $page.props.auth.roles.includes(r)) || isDirectorApprovalView)" class="bg-gradient-to-br from-blue-50 to-indigo-100/50 border border-blue-200 rounded-2xl p-6 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-blue-800 uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Review and Approve Deliverable
                    </h3>
                    <p class="text-xs text-gray-700 leading-relaxed">
                        A Coordinator has submitted this deliverable for your final approval. Review the document and approve to route it to the Administrative Assistant for client delivery, or reject to send it back.
                    </p>

                    <div class="space-y-3">
                        <textarea v-model="directorNotes" placeholder="Optional approval notes or comments..." rows="2" class="w-full text-xs border-gray-300 rounded-lg focus:border-brand-blue focus:ring-brand-blue"></textarea>
                        <div class="flex gap-2">
                            <button @click="submitDirectorApprove" :disabled="submittingAction" class="flex-1 bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm">
                                Approve Deliverable
                            </button>
                            <button @click="showDirectorRejectModal = true" :disabled="submittingAction" class="flex-1 bg-red-600 text-white hover:bg-red-700 disabled:opacity-50 px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm">
                                Reject & Send Back
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 3. Coordinator/Admin Assistant CC Review Request Card -->
                <div v-if="((serviceRequest.status === 'review' && $page.props.auth.roles.includes('coordinator')) || (serviceRequest.status === 'admin_submission' && $page.props.auth.roles.includes('admin_assistant'))) && coordinators.length > 0" class="bg-gradient-to-br from-purple-50 to-indigo-100/50 border border-purple-200 rounded-2xl p-6 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-purple-800 uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        Request CC Review
                    </h3>
                    <p class="text-xs text-gray-700 leading-relaxed">
                        Send this deliverable to Coordinators for optional internal review.
                    </p>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase mb-1">Select Coordinators (CC)</label>
                            <select v-model="selectedReviewers" multiple class="w-full text-xs border-gray-300 rounded-lg focus:border-brand-blue focus:ring-brand-blue h-20">
                                <option v-for="coord in coordinators" :key="coord.id" :value="coord.id">{{ coord.name }}</option>
                            </select>
                        </div>
                        <textarea v-model="ccNotes" placeholder="Optional notes for reviewers..." rows="2" class="w-full text-xs border-gray-300 rounded-lg focus:border-brand-blue focus:ring-brand-blue"></textarea>
                        <button @click="submitCcReview" :disabled="submittingAction || selectedReviewers.length === 0" class="w-full bg-purple-600 text-white hover:bg-purple-700 disabled:opacity-50 px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm">
                            Send CC Review Request
                        </button>
                    </div>
                </div>

                <!-- 4. Coordinator CC Response Card -->
                <div v-if="(serviceRequest.status === 'review' || serviceRequest.status === 'admin_submission') && $page.props.auth.roles.includes('coordinator') && pendingCcReview" class="bg-gradient-to-br from-amber-50 to-orange-100/50 border border-orange-200 rounded-2xl p-6 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-orange-800 uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-5 h-5 text-orange-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                        </svg>
                        Pending CC Review Response
                    </h3>
                    <p class="text-xs text-gray-700 leading-relaxed">
                        Another user has requested your review on this deliverable. Leave your comments and submit.
                    </p>

                    <div class="space-y-3">
                        <textarea v-model="ccResponseComments" placeholder="Enter your review comments..." rows="2" class="w-full text-xs border-gray-300 rounded-lg focus:border-brand-blue focus:ring-brand-blue"></textarea>
                        <div class="flex gap-2">
                            <button @click="submitCcResponse('approved')" :disabled="submittingAction || !ccResponseComments" class="flex-1 bg-green-600 text-white hover:bg-green-700 disabled:opacity-50 px-3 py-2 rounded-xl text-xs font-bold transition shadow-sm">
                                Approve Review
                            </button>
                            <button @click="submitCcResponse('rejected')" :disabled="submittingAction || !ccResponseComments" class="flex-1 bg-red-600 text-white hover:bg-red-700 disabled:opacity-50 px-3 py-2 rounded-xl text-xs font-bold transition shadow-sm">
                                Reject Review
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Coordinator Decision Card (Perform or Delegate) -->
                <div v-if="serviceRequest.status === 'pending_coordinator_action' && serviceRequest.assigned_to === $page.props.auth.user.id" class="bg-gradient-to-br from-indigo-50 to-purple-100/50 border border-indigo-200 rounded-2xl p-6 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-indigo-800 uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        Coordinator Decision Required
                    </h3>
                    <p class="text-xs text-gray-700 leading-relaxed">
                        This service request is assigned to you. You can either perform the task personally or delegate it to an eligible staff member under your supervision.
                    </p>

                    <div class="flex gap-2">
                        <button @click="submitPerformTask" :disabled="submittingAction" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white disabled:opacity-50 px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm text-center">
                            Perform Task
                        </button>
                        <button @click="showDelegationForm = !showDelegationForm" :disabled="submittingAction" class="flex-1 bg-white hover:bg-gray-50 text-indigo-700 border border-indigo-300 disabled:opacity-50 px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm text-center">
                            {{ showDelegationForm ? 'Cancel Delegation' : 'Delegate Task' }}
                        </button>
                    </div>

                    <div v-if="showDelegationForm" class="space-y-3 pt-3 border-t border-indigo-100">
                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase mb-1">Select Staff Member *</label>
                            <select v-model="delegationForm.assigned_to" class="w-full text-xs border-gray-300 rounded-lg focus:border-brand-blue focus:ring-brand-blue">
                                <option value="">-- Choose Staff --</option>
                                <option v-for="member in eligibleStaff" :key="member.id" :value="member.id">{{ member.name }} ({{ member.email }})</option>
                            </select>
                            <p v-if="delegationForm.errors.assigned_to" class="text-red-500 text-[10px] mt-1">{{ delegationForm.errors.assigned_to }}</p>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase mb-1">Delegation Instructions / Notes</label>
                            <textarea v-model="delegationForm.instructions" placeholder="Enter special instructions or notes for the staff member..." rows="3" class="w-full text-xs border-gray-300 rounded-lg focus:border-brand-blue focus:ring-brand-blue"></textarea>
                            <p v-if="delegationForm.errors.instructions" class="text-red-500 text-[10px] mt-1">{{ delegationForm.errors.instructions }}</p>
                        </div>
                        <button @click="submitDelegateTask" :disabled="submittingAction || !delegationForm.assigned_to" class="w-full bg-indigo-600 hover:bg-indigo-750 text-white disabled:opacity-50 px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm">
                            Confirm Delegation
                        </button>
                    </div>
                </div>

                <!-- 5. Coordinator Forward to Director Card -->
                <div v-if="serviceRequest.status === 'review' && $page.props.auth.roles.includes('coordinator') && directors.length > 0" class="bg-gradient-to-br from-yellow-50 to-amber-100/50 border border-yellow-250 rounded-2xl p-6 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-amber-800 uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        Forward for Final Approval
                    </h3>
                    <p class="text-xs text-gray-700 leading-relaxed">
                        Once you have completed your review, forward this deliverable to the Director or Deputy Director for final approval.
                    </p>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase mb-1">Select Director/Deputy Director</label>
                            <select v-model="selectedDirector" class="w-full text-xs border-gray-300 rounded-lg focus:border-brand-blue focus:ring-brand-blue">
                                <option value="">-- Choose Director --</option>
                                <option v-for="dir in directors" :key="dir.id" :value="dir.id">{{ dir.name }}</option>
                            </select>
                        </div>
                        <textarea v-model="forwardNotes" placeholder="Optional notes for the Director..." rows="2" class="w-full text-xs border-gray-300 rounded-lg focus:border-brand-blue focus:ring-brand-blue"></textarea>
                        <button @click="submitForwardToDirector" :disabled="submittingAction || !selectedDirector" class="w-full bg-[#0a1f44] text-white hover:bg-[#152a4d] disabled:opacity-50 px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm">
                            Forward to Director
                        </button>
                    </div>
                </div>

                <!-- 5b. Coordinator Direct Approval (Bypass Director) Card -->
                <div v-if="serviceRequest.status === 'review' && $page.props.auth.roles.includes('coordinator') && directRoutingEnabled" class="bg-gradient-to-br from-green-50 to-emerald-100/50 border border-green-200 rounded-2xl p-6 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-green-800 uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Direct Deliverable Approval
                    </h3>
                    <p class="text-xs text-gray-700 leading-relaxed">
                        The system configuration allows you to bypass the Directorate approval. Approve this deliverable to route it directly to the Administrative Assistant for client submission.
                    </p>

                    <div class="space-y-3">
                        <textarea v-model="coordinatorNotes" placeholder="Optional approval notes or comments..." rows="2" class="w-full text-xs border-gray-300 rounded-lg focus:border-brand-blue focus:ring-brand-blue"></textarea>
                        <button @click="submitCoordinatorApprove" :disabled="submittingAction" class="w-full bg-green-600 text-white hover:bg-green-700 disabled:opacity-50 px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm">
                            Approve &amp; Route to Admin Assistant
                        </button>
                    </div>
                </div>

                <!-- Director Rejection Modal -->
                <div v-if="showDirectorRejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl p-6 space-y-4">
                        <h3 class="text-lg font-bold text-red-700 uppercase tracking-wide">Confirm Rejection</h3>
                        <p class="text-xs text-gray-500">Provide the reason for rejecting the deliverable. This will return the request to the Coordinator Review stage.</p>
                        <div>
                            <label class="block text-[10px] font-black text-gray-700 uppercase mb-1">Rejection Reason *</label>
                            <textarea v-model="rejectionReason" rows="3" class="w-full rounded-xl text-xs border-gray-300 focus:border-red-500 focus:ring-red-500 shadow-sm" placeholder="Why is this deliverable being rejected?..."></textarea>
                        </div>
                        <div class="flex gap-2 justify-end">
                            <button @click="showDirectorRejectModal = false" class="px-4 py-2 bg-gray-150 text-gray-700 rounded-xl text-xs font-semibold hover:bg-gray-200 transition">Cancel</button>
                            <button @click="submitDirectorReject" :disabled="!rejectionReason || submittingAction" class="px-4 py-2 bg-red-600 text-white rounded-xl text-xs font-semibold hover:bg-red-700 transition disabled:opacity-50">Confirm Rejection</button>
                        </div>
                    </div>
                </div>

                <!-- Client Actions -->
                <div v-if="$page.props.auth.roles.includes('client') && serviceRequest.status === 'pending'" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Actions</h3>
                    <div class="space-y-2">
                        <Link :href="route('service-requests.edit', serviceRequest.id)"
                              class="block w-full text-center bg-[#0a1f44] text-white py-2.5 px-4 rounded-xl text-sm font-semibold hover:bg-[#0a1f44]/80 transition">
                            Edit Request
                        </Link>
                    </div>
                </div>
            </div>
        </div>
        <DocumentPreviewModal :show="showPreview" :document="previewDoc" @close="showPreview = false" />
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/inertia-vue3';
import { ref, computed } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import DocumentPreviewModal from '@/Components/DocumentPreviewModal.vue';

const previewDoc = ref(null);
const showPreview = ref(false);

const openPreview = (doc) => {
    previewDoc.value = doc;
    showPreview.value = true;
};

const props = defineProps({
    serviceRequest: Object,
    coordinators: Array,
    directors: Array,
    eligibleStaff: Array,
    isDirectorApprovalView: Boolean,
    directRoutingEnabled: Boolean,
});

const page = usePage();
const submittingAction = ref(false);
const showDelegationForm = ref(false);

// Status display: for client users, map internal statuses to client-friendly labels
const isClient = computed(() => page.props.value.auth.roles.includes('client'));

const displayStatus = computed(() => {
    const raw = props.serviceRequest.status;
    if (isClient.value) {
        // Clients should see internal coordinator workflow states as "in_progress"
        const clientMap = {
            'pending_coordinator_action': 'pending',
            'assigned': 'pending',
            'review': 'in_progress',
            'director_approval': 'in_progress',
            'admin_submission': 'in_progress',
        };
        return clientMap[raw] || raw;
    }
    return raw;
});

const displayStatusLabel = computed(() => {
    return displayStatus.value.replace(/_/g, ' ');
});

const delegationForm = useForm({
    assigned_to: '',
    instructions: '',
});

const submitPerformTask = () => {
    submittingAction.value = true;
    Inertia.post(route('service-requests.perform', props.serviceRequest.id), {}, {
        onSuccess: () => {
            submittingAction.value = false;
        },
        onFinish: () => {
            submittingAction.value = false;
        }
    });
};

const submitDelegateTask = () => {
    submittingAction.value = true;
    delegationForm.post(route('service-requests.delegate', props.serviceRequest.id), {
        onSuccess: () => {
            delegationForm.reset();
            showDelegationForm.value = false;
            submittingAction.value = false;
        },
        onFinish: () => {
            submittingAction.value = false;
        }
    });
};

const attachForm = useForm({
    file: null,
    description: '',
});

const deliveryForm = useForm({
    notes: '',
});

// Coordinator CC Review Form
const selectedReviewers = ref([]);
const ccNotes = ref('');

// Coordinator CC Response Form
const ccResponseComments = ref('');

const pendingCcReview = computed(() => {
    if (!props.serviceRequest.cc_reviews) return null;
    const currentUserId = page.props.value.auth.user.id;
    return props.serviceRequest.cc_reviews.find(r => parseInt(r.reviewer_id) === parseInt(currentUserId) && r.status === 'pending');
});

// Coordinator Forward Form
const selectedDirector = ref('');
const forwardNotes = ref('');
const coordinatorNotes = ref('');

// Director Approval Forms
const directorNotes = ref('');
const showDirectorRejectModal = ref(false);
const rejectionReason = ref('');

const handleAttachFileChange = (e) => {
    attachForm.file = e.target.files[0];
};

const submitAttachment = () => {
    attachForm.post(route('service-requests.attach', props.serviceRequest.id), {
        onSuccess: () => {
            attachForm.reset();
        }
    });
};

const deliverCompletedTask = () => {
    deliveryForm.post(route('service-requests.deliver', props.serviceRequest.id), {
        onSuccess: () => {
            deliveryForm.reset();
        }
    });
};

const submitCcReview = () => {
    if (selectedReviewers.value.length === 0) return;
    submittingAction.value = true;
    Inertia.post(route('service-requests.cc-review', props.serviceRequest.id), {
        reviewer_ids: selectedReviewers.value,
        notes: ccNotes.value,
    }, {
        onSuccess: () => {
            selectedReviewers.value = [];
            ccNotes.value = '';
            submittingAction.value = false;
        },
        onFinish: () => { submittingAction.value = false; }
    });
};

const submitCcResponse = (status) => {
    if (!pendingCcReview.value) return;
    submittingAction.value = true;
    Inertia.post(route('cc-reviews.respond', pendingCcReview.value.id), {
        comments: ccResponseComments.value,
        status: status,
    }, {
        onSuccess: () => {
            ccResponseComments.value = '';
            submittingAction.value = false;
        },
        onFinish: () => { submittingAction.value = false; }
    });
};

const submitForwardToDirector = () => {
    if (!selectedDirector.value) return;
    submittingAction.value = true;
    Inertia.post(route('service-requests.forward', props.serviceRequest.id), {
        director_id: selectedDirector.value,
        notes: forwardNotes.value,
    }, {
        onSuccess: () => {
            selectedDirector.value = '';
            forwardNotes.value = '';
            submittingAction.value = false;
        },
        onFinish: () => { submittingAction.value = false; }
    });
};

const submitDirectorApprove = () => {
    submittingAction.value = true;
    Inertia.post(route('service-requests.director-approve', props.serviceRequest.id), {
        notes: directorNotes.value,
    }, {
        onSuccess: () => {
            directorNotes.value = '';
            submittingAction.value = false;
        },
        onFinish: () => { submittingAction.value = false; }
    });
};

const submitCoordinatorApprove = () => {
    submittingAction.value = true;
    Inertia.post(route('service-requests.coordinator-approve', props.serviceRequest.id), {
        notes: coordinatorNotes.value,
    }, {
        onSuccess: () => {
            coordinatorNotes.value = '';
            submittingAction.value = false;
        },
        onFinish: () => { submittingAction.value = false; }
    });
};

const submitDirectorReject = () => {
    if (!rejectionReason.value) return;
    submittingAction.value = true;
    Inertia.post(route('service-requests.director-reject', props.serviceRequest.id), {
        reason: rejectionReason.value,
    }, {
        onSuccess: () => {
            rejectionReason.value = '';
            showDirectorRejectModal.value = false;
            submittingAction.value = false;
        },
        onFinish: () => { submittingAction.value = false; }
    });
};

const staffDeliverables = computed(() => {
    if (!props.serviceRequest.assignments) return [];
    if (page.props.value.auth.roles.includes('client') && props.serviceRequest.status !== 'completed') {
        return [];
    }
    return props.serviceRequest.assignments.flatMap(a => a.documents || []);
});

const hasVerifiedPayment = computed(() => {
    if (!props.serviceRequest.payments) return false;
    return props.serviceRequest.payments.some(p => p.status === 'verified');
});

const canManage = computed(() => {
    const perms = page.props.value.auth.permissions || [];
    return perms.includes('manage service_requests') || perms.includes('manage system');
});

const canCreateQuotation = computed(() => {
    const roles = page.props.value.auth.roles || [];
    return roles.includes('admin_assistant') || roles.includes('ict_administrator') || roles.includes('secretary');
});

const getQuotationStatus = (quotation) => {
    if (quotation.status === 'draft') {
        return quotation.approvals && quotation.approvals.length > 0
            ? { label: 'Needs Revision', class: 'bg-orange-100 text-orange-800' }
            : { label: 'Draft', class: 'bg-gray-100 text-gray-700' };
    }
    if (quotation.status === 'submitted') {
        return { label: 'Awaiting Review', class: 'bg-blue-100 text-blue-800' };
    }
    if (quotation.status === 'reviewed') {
        return { label: 'Pending Recommendation', class: 'bg-purple-100 text-purple-800' };
    }
    if (quotation.status === 'pending_approval') {
        return { label: 'Recommended', class: 'bg-yellow-100 text-yellow-800' };
    }
    if (quotation.status === 'approved') {
        return { label: 'Approved', class: 'bg-green-100 text-green-800' };
    }
    if (quotation.status === 'rejected') {
        return { label: 'Rejected', class: 'bg-red-100 text-red-800' };
    }
    return { label: quotation.status.replace(/_/g, ' '), class: 'bg-gray-100 text-gray-700' };
};
</script>
