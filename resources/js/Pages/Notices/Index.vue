<template>
    <Head title="Notices & Announcements" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <span class="text-black font-extrabold">Notices & Announcements</span>
                <button v-if="isAdmin" @click="openCreateModal" class="bg-[#f5c242] hover:bg-yellow-500 text-[#0a1f44] font-bold px-5 py-2 rounded-xl transition duration-200 shadow flex items-center gap-2 text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Create Notice
                </button>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Filter & Search Bar -->
            <div class="bg-white rounded-2xl border border-gray-150 p-4 shadow-sm flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[200px]">
                    <div class="relative">
                        <input v-model="searchQuery" type="text" placeholder="Search notices by title or content..." 
                               class="w-full text-xs rounded-xl border-gray-200 pl-8 pr-3 py-2.5 focus:border-[#f5c242] focus:ring-1 focus:ring-[#0a1f44] placeholder-gray-400 font-medium" />
                        <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>
                <div class="w-40" v-if="isAdmin">
                    <select v-model="statusFilter" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#f5c242] focus:ring-1 focus:ring-[#0a1f44] py-2.5 font-medium">
                        <option value="all">All Statuses</option>
                        <option value="published">Published</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
                <div class="w-44">
                    <select v-model="targetFilter" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#f5c242] focus:ring-1 focus:ring-[#0a1f44] py-2.5 font-medium">
                        <option value="all">All Targets</option>
                        <option value="all_students">All Students</option>
                        <option value="specific_course">Specific Course</option>
                    </select>
                </div>
                <!-- Sort Dropdown -->
                <div class="w-40">
                    <select v-model="sortBy" class="w-full text-xs rounded-xl border-gray-200 focus:border-[#f5c242] focus:ring-1 focus:ring-[#0a1f44] py-2.5 font-medium">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="title_asc">Title (A-Z)</option>
                        <option value="title_desc">Title (Z-A)</option>
                    </select>
                </div>
                <!-- View Mode Switcher -->
                <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden p-0.5 bg-gray-50 flex-shrink-0">
                    <button type="button" @click="viewMode = 'cards'" :class="viewMode === 'cards' ? 'bg-white text-[#0A2A66] shadow-sm font-bold' : 'text-gray-400 hover:text-gray-600'" class="p-2 rounded-lg transition-all duration-200" title="Card View">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    </button>
                    <button type="button" @click="viewMode = 'table'" :class="viewMode === 'table' ? 'bg-white text-[#0A2A66] shadow-sm font-bold' : 'text-gray-400 hover:text-gray-600'" class="p-2 rounded-lg transition-all duration-200" title="Table View">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
            </div>

            <!-- Notices Feed / Bulletin Board -->
            <div v-if="filteredNotices.length === 0" class="bg-white rounded-2xl border border-gray-150 p-12 text-center text-gray-400 italic">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                No notices match your selection.
            </div>

            <!-- Card View Mode -->
            <div v-else-if="viewMode === 'cards'" class="grid grid-cols-1 gap-6">
                <!-- Premium Notice Card -->
                <div v-for="notice in filteredNotices" :key="notice.id" 
                     class="bg-white rounded-2xl border border-gray-150 p-6 shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden group hover:-translate-y-0.5">
                    
                    <!-- Top Ribbon for Draft Status -->
                    <div v-if="!notice.published_at" class="absolute top-0 right-0 bg-amber-500 text-[#0a1f44] font-black text-[9px] px-3 py-1 uppercase tracking-wider rounded-bl-xl shadow-sm">
                        Draft
                    </div>

                    <div class="flex items-start gap-4">
                        <!-- Uploader Info Icon -->
                        <div class="w-10 h-10 rounded-full bg-[#0a1f44]/5 text-[#0a1f44] font-black text-sm flex items-center justify-center border border-[#0a1f44]/15 flex-shrink-0 uppercase shadow-inner">
                            {{ notice.creator ? notice.creator.name.charAt(0) : 'A' }}
                        </div>

                        <!-- Content Area -->
                        <div class="flex-grow space-y-3 min-w-0">
                            <div class="flex flex-wrap items-baseline gap-2">
                                <!-- Prominent Title Clickable to View Detail -->
                                <h3 @click="viewNoticeDetails(notice)" class="text-lg md:text-xl font-extrabold text-[#0A2A66] hover:text-blue-700 transition cursor-pointer leading-snug break-words pr-12">
                                    {{ notice.title }}
                                </h3>
                                
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full uppercase tracking-wider"
                                      :class="notice.target_type === 'all_students' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800'">
                                    {{ notice.target_type === 'all_students' ? 'All Students' : (notice.course ? notice.course.code : 'Specific Course') }}
                                </span>
                            </div>

                            <!-- Metadata -->
                            <div class="text-[10px] text-gray-400 font-semibold flex flex-wrap gap-x-3 gap-y-1">
                                <span>Posted by: <strong class="text-gray-600">{{ notice.creator ? notice.creator.name : 'Administrator' }}</strong></span>
                                <span>&bull;</span>
                                <span>Date: <strong class="text-gray-600">{{ notice.published_at ? formatDate(notice.published_at) : 'Draft (' + formatDate(notice.created_at) + ')' }}</strong></span>
                            </div>

                            <!-- Notice Text Content -->
                            <p class="text-xs text-gray-600 leading-relaxed whitespace-pre-line break-words font-medium line-clamp-3 group-hover:line-clamp-none transition-all duration-300">
                                {{ notice.content }}
                            </p>

                            <!-- Attachments Area -->
                            <div v-if="notice.documents && notice.documents.length > 0" class="pt-3 border-t border-gray-100 space-y-2">
                                <h4 class="text-[10px] font-black text-gray-500 uppercase tracking-wider">Attachments:</h4>
                                <div class="flex flex-wrap gap-2">
                                    <a v-for="doc in notice.documents" :key="doc.id"
                                       :href="route('files.download', { type: 'uploaded_document', id: doc.id })"
                                       class="flex items-center gap-2 bg-gray-50 hover:bg-gray-100 text-gray-700 hover:text-[#0A2A66] border border-gray-200 rounded-lg px-3 py-1.5 transition text-xs font-semibold">
                                        <svg class="w-4 h-4 text-gray-450" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span class="truncate max-w-[180px]">{{ doc.filename }}</span>
                                        <span class="text-[9px] text-gray-400 font-medium">({{ (doc.file_size / 1024).toFixed(0) }} KB)</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Actions Footer -->
                            <div class="flex gap-3 pt-4 justify-end border-t border-gray-50 items-center">
                                <button @click="viewNoticeDetails(notice)" 
                                        class="text-xs font-bold text-[#0A2A66] hover:underline transition uppercase tracking-wide mr-auto flex items-center gap-1">
                                    <span>Read Full Announcement</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                                
                                <button v-if="isAdmin && !notice.published_at" @click="publishNotice(notice.id)" 
                                        class="text-xs font-bold text-green-600 hover:text-green-800 bg-green-50 hover:bg-green-100 px-3.5 py-1.5 rounded-lg border border-green-200 transition uppercase tracking-wide">
                                    Publish
                                </button>
                                <button v-if="isAdmin" @click="openEditModal(notice)" 
                                        class="text-xs font-bold text-brand-gold-dark hover:text-brand-gold bg-amber-50/50 hover:bg-amber-50 px-3.5 py-1.5 rounded-lg border border-amber-200 transition uppercase tracking-wide">
                                    Edit
                                </button>
                                <button v-if="isAdmin" @click="deleteNotice(notice)" 
                                        class="text-xs font-bold text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 px-3.5 py-1.5 rounded-lg border border-red-200 transition uppercase tracking-wide">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table View Mode -->
            <div v-else-if="viewMode === 'table'" class="bg-white rounded-2xl border border-gray-150 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-[#0A2A66] text-white uppercase text-[10px] tracking-wider font-bold">
                                <th class="p-4">Title</th>
                                <th class="p-4">Target Audience</th>
                                <th class="p-4">Posted By</th>
                                <th class="p-4">Date</th>
                                <th class="p-4" v-if="isAdmin">Status</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-150 font-medium text-gray-700">
                            <tr v-for="notice in filteredNotices" :key="notice.id" class="hover:bg-gray-50/50 transition">
                                <td class="p-4">
                                    <div class="font-extrabold text-[#0A2A66] hover:text-blue-700 cursor-pointer text-sm truncate max-w-[280px]" @click="viewNoticeDetails(notice)">
                                        {{ notice.title }}
                                    </div>
                                    <div class="text-[10px] text-gray-400 truncate max-w-[280px] mt-0.5">
                                        {{ notice.content }}
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span class="px-2.5 py-0.5 text-[9px] font-bold rounded-full uppercase tracking-wider"
                                          :class="notice.target_type === 'all_students' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800'">
                                        {{ notice.target_type === 'all_students' ? 'All Students' : (notice.course ? notice.course.code : 'Specific Course') }}
                                    </span>
                                </td>
                                <td class="p-4">{{ notice.creator ? notice.creator.name : 'Administrator' }}</td>
                                <td class="p-4">{{ notice.published_at ? formatDate(notice.published_at) : formatDate(notice.created_at) }}</td>
                                <td class="p-4" v-if="isAdmin">
                                    <span class="px-2 py-0.5 text-[9px] font-bold rounded-full uppercase tracking-wider"
                                          :class="notice.published_at ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'">
                                        {{ notice.published_at ? 'Published' : 'Draft' }}
                                    </span>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="viewNoticeDetails(notice)" class="text-blue-600 hover:text-blue-800 font-bold hover:underline transition py-1 px-2 text-[10px]">
                                            View
                                        </button>
                                        <button v-if="isAdmin && !notice.published_at" @click="publishNotice(notice.id)" class="text-green-600 hover:text-green-800 font-bold hover:underline transition py-1 px-2 text-[10px]">
                                            Publish
                                        </button>
                                        <button v-if="isAdmin" @click="openEditModal(notice)" class="text-amber-600 hover:text-amber-800 font-bold hover:underline transition py-1 px-2 text-[10px]">
                                            Edit
                                        </button>
                                        <button v-if="isAdmin" @click="deleteNotice(notice)" class="text-red-600 hover:text-red-800 font-bold hover:underline transition py-1 px-2 text-[10px]">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sleek Notice Form Modal (Create / Edit) -->
        <Teleport to="body">
            <div v-if="modalOpen" class="modal fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4" @click.self="modalOpen = false">
                <form @submit.prevent="saveNotice" class="modal-dialog modal-content bg-white rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-300 relative text-slate-800" @click.stop>
                    <div class="modal-header">
                        <h4 class="modal-title">{{ isEditing ? 'Edit Notice Announcement' : 'Create New Notice' }}</h4>
                        <button type="button" @click="modalOpen = false" class="btn-close text-white/80 hover:text-white transition focus:outline-none" data-bs-dismiss="modal">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    
                    <div class="modal-body p-6 space-y-5">
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide">Notice Title *</label>
                            <input v-model="form.title" type="text" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm" required placeholder="e.g. Winter Schedule Updates" />
                            <div v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</div>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide">Notice Content *</label>
                            <textarea v-model="form.content" rows="5" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm" required placeholder="Provide full announcement text..."></textarea>
                            <div v-if="form.errors.content" class="text-red-500 text-xs mt-1">{{ form.errors.content }}</div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide">Target Audience *</label>
                                <select v-model="form.target_type" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm" required>
                                    <option value="all_students">All Students</option>
                                    <option value="specific_course">Specific Course Offerings</option>
                                </select>
                                <div v-if="form.errors.target_type" class="text-red-500 text-xs mt-1">{{ form.errors.target_type }}</div>
                            </div>

                            <div class="space-y-1" v-if="form.target_type === 'specific_course'">
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide">Target Course *</label>
                                <select v-model="form.course_id" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm" required>
                                    <option :value="null" disabled>Select course offering...</option>
                                    <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.code }} - {{ course.title }}</option>
                                </select>
                                <div v-if="form.errors.course_id" class="text-red-500 text-xs mt-1">{{ form.errors.course_id }}</div>
                            </div>
                        </div>

                        <!-- Existing Attachments Manager for editing -->
                        <div v-if="isEditing && existingDocuments.length > 0" class="space-y-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide">Current Files (Click to delete):</label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="doc in existingDocuments" :key="doc.id" type="button"
                                        @click="toggleDeleteAttachment(doc.id)"
                                        class="flex items-center gap-2 border rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                                        :class="form.deleted_file_ids.includes(doc.id) ? 'bg-red-50 border-red-300 text-red-600 line-through' : 'bg-gray-50 hover:bg-red-50/50 border-gray-200 hover:border-red-200 text-gray-700 hover:text-red-600'">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    <span>{{ doc.filename }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Dynamic Multiple Files Uploader -->
                        <div class="space-y-3">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide">Attach Files</label>
                            
                            <div v-for="(field, idx) in fileFields" :key="field.id" class="flex items-center gap-3 bg-gray-50 p-2.5 rounded-xl border border-gray-200 transition hover:border-[#0a1f44]/20">
                                <div class="flex-grow">
                                    <input 
                                        type="file" 
                                        @change="handleFieldFileChange(idx, $event)"
                                        class="w-full text-xs text-gray-550 file:mr-3 file:py-1.5 file:px-3 file:rounded-full file:border-0 file:text-[11px] file:font-bold file:bg-[#0a1f44]/10 file:text-[#0a1f44] hover:file:bg-[#0a1f44]/20 cursor-pointer"
                                    />
                                </div>
                                <button v-if="fileFields.length > 1" type="button" @click="removeFileField(idx)" class="text-red-500 hover:text-red-700 p-1.5 hover:bg-red-50 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>

                            <div>
                                <button type="button" @click="addFileField" class="text-xs font-bold text-[#0a1f44] hover:text-white bg-[#0a1f44]/5 hover:bg-[#0a1f44] px-4 py-2 rounded-lg border border-[#0a1f44]/15 hover:border-[#0a1f44] transition-all flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Add Another File
                                </button>
                            </div>
                            <div class="text-[10px] text-gray-400 font-semibold">
                                Supported file formats: PDF, DOC, DOCX, JPG, JPEG, PNG (Max 10MB per file)
                            </div>
                            <div v-if="form.errors.files" class="text-red-500 text-xs font-semibold">{{ form.errors.files }}</div>
                        </div>

                        <!-- Publish immediately option (only for create) -->
                        <div class="flex items-center gap-2 py-1" v-if="!isEditing">
                            <input v-model="form.publish_immediately" type="checkbox" id="publish_immediately" class="rounded border-gray-300 text-[#0a1f44] focus:ring-[#0a1f44]" />
                            <label for="publish_immediately" class="text-xs font-bold text-gray-700 select-none cursor-pointer">Publish announcement immediately to students</label>
                        </div>

                        <!-- Publish on update option -->
                        <div class="flex items-center gap-2 py-1" v-if="isEditing && !editingNoticePublished">
                            <input v-model="form.publish" type="checkbox" id="publish_update" class="rounded border-gray-300 text-[#0a1f44] focus:ring-[#0a1f44]" />
                            <label for="publish_update" class="text-xs font-bold text-gray-700 select-none cursor-pointer">Publish now (Make visible to students)</label>
                        </div>
                    </div>

                    <div class="modal-footer pt-4 border-t border-gray-150 flex justify-end gap-3 px-6 py-4 bg-gray-50">
                        <button type="button" @click="modalOpen = false" class="px-4 py-2 rounded-xl border border-gray-300 font-bold text-gray-700 hover:bg-gray-50 transition text-xs uppercase tracking-wide">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 rounded-xl bg-[#0a1f44] hover:bg-[#0c2859] disabled:opacity-50 text-white font-bold transition shadow text-xs uppercase tracking-wide flex items-center gap-2">
                            <span v-if="form.processing" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            <span>Save Notice</span>
                        </button>
                    </div>
                </form>
            </div>
        </Teleport>

        <!-- Notice Detail Modal -->
        <Teleport to="body">
            <div v-if="detailModalOpen" class="modal fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4" @click.self="detailModalOpen = false">
                <div class="modal-dialog modal-content bg-white rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-300 relative text-slate-800" @click.stop>
                    <div class="modal-header">
                        <h4 class="modal-title">Notice Announcement Details</h4>
                        <button type="button" @click="detailModalOpen = false" class="btn-close text-white/80 hover:text-white transition focus:outline-none" data-bs-dismiss="modal">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    
                    <div class="modal-body p-6 space-y-6">
                        <!-- Primary Prominent Title Heading -->
                        <div class="border-b border-gray-100 pb-4 space-y-2">
                            <h2 class="text-xl md:text-2xl font-black text-[#0A2A66] leading-tight">
                                {{ selectedNotice?.title }}
                            </h2>
                            <div class="flex flex-wrap items-center gap-3 text-[10px] text-gray-500 font-semibold">
                                <span class="px-2 py-0.5 text-[9px] font-bold rounded-full uppercase tracking-wider"
                                      :class="selectedNotice?.target_type === 'all_students' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800'">
                                    {{ selectedNotice?.target_type === 'all_students' ? 'All Students' : (selectedNotice?.course ? selectedNotice.course.code : 'Specific Course') }}
                                </span>
                                <span v-if="!selectedNotice?.published_at" class="px-2 py-0.5 text-[9px] font-bold rounded-full bg-amber-500 text-[#0a1f44] uppercase tracking-wider">
                                    Draft
                                </span>
                                <span>Posted by: <strong class="text-gray-700">{{ selectedNotice?.creator ? selectedNotice.creator.name : 'Administrator' }}</strong></span>
                                <span>&bull;</span>
                                <span>Date: <strong class="text-gray-700">{{ selectedNotice?.published_at ? formatDate(selectedNotice.published_at) : formatDate(selectedNotice?.created_at) }}</strong></span>
                            </div>
                        </div>

                        <!-- Content Body -->
                        <div class="text-xs md:text-sm text-gray-600 leading-relaxed whitespace-pre-line break-words font-medium">
                            {{ selectedNotice?.content }}
                        </div>

                        <!-- Attachments Area -->
                        <div v-if="selectedNotice?.documents && selectedNotice.documents.length > 0" class="pt-4 border-t border-gray-100 space-y-3">
                            <h4 class="text-[10px] font-black text-gray-500 uppercase tracking-wider">Attachments:</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <a v-for="doc in selectedNotice.documents" :key="doc.id"
                                   :href="route('files.download', { type: 'uploaded_document', id: doc.id })"
                                   class="flex items-center gap-3 bg-gray-50 hover:bg-gray-100 text-gray-700 hover:text-[#0A2A66] border border-gray-200 rounded-xl px-4 py-3 transition text-xs font-semibold shadow-sm">
                                    <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <div class="flex-grow min-w-0">
                                        <div class="truncate font-bold text-gray-800">{{ doc.filename }}</div>
                                        <div class="text-[9px] text-gray-400 font-medium">{{ (doc.file_size / 1024).toFixed(0) }} KB</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer pt-4 border-t border-gray-150 flex justify-between gap-3 px-6 py-4 bg-gray-50">
                        <div class="flex gap-2" v-if="isAdmin">
                            <button v-if="!selectedNotice?.published_at" @click="publishNotice(selectedNotice?.id); detailModalOpen = false" 
                                    class="px-4 py-2 rounded-xl bg-green-600 hover:bg-green-700 text-white font-bold transition text-xs uppercase tracking-wide">
                                Publish
                            </button>
                            <button @click="openEditModal(selectedNotice); detailModalOpen = false" 
                                    class="px-4 py-2 rounded-xl border border-amber-300 bg-amber-50 hover:bg-amber-100 text-brand-gold-dark font-bold transition text-xs uppercase tracking-wide">
                                Edit
                            </button>
                        </div>
                        <div v-else></div>
                        <button type="button" @click="detailModalOpen = false" class="px-4 py-2 rounded-xl border border-gray-300 font-bold text-gray-700 hover:bg-gray-50 transition text-xs uppercase tracking-wide">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/inertia-vue3';
import { ref, computed, watch, onUnmounted } from 'vue';
import { Inertia } from '@inertiajs/inertia';

const props = defineProps({
    notices: Array,
    courses: Array,
});

const page = usePage();
const isAdmin = computed(() => {
    const roles = page.props.value.auth.roles || [];
    return ['executive_director', 'deputy_director', 'ict_administrator', 'admin_assistant', 'secretary'].some(r => roles.includes(r));
});

// Filters and Search
const searchQuery = ref('');
const statusFilter = ref('all');
const targetFilter = ref('all');
const sortBy = ref('newest');
const viewMode = ref('cards');

const filteredNotices = computed(() => {
    let result = props.notices.filter(notice => {
        // Search filter
        const query = searchQuery.value.toLowerCase().trim();
        const matchesQuery = !query || 
                             notice.title.toLowerCase().includes(query) || 
                             notice.content.toLowerCase().includes(query);

        // Status filter (admin only)
        const isPub = notice.published_at !== null;
        const matchesStatus = statusFilter.value === 'all' || 
                              (statusFilter.value === 'published' && isPub) || 
                              (statusFilter.value === 'draft' && !isPub);

        // Target filter
        const matchesTarget = targetFilter.value === 'all' || notice.target_type === targetFilter.value;

        return matchesQuery && matchesStatus && matchesTarget;
    });

    // Sorting filter
    if (sortBy.value === 'newest') {
        result.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    } else if (sortBy.value === 'oldest') {
        result.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
    } else if (sortBy.value === 'title_asc') {
        result.sort((a, b) => a.title.localeCompare(b.title));
    } else if (sortBy.value === 'title_desc') {
        result.sort((a, b) => b.title.localeCompare(a.title));
    }

    return result;
});

// Modal and Form management
const modalOpen = ref(false);
const isEditing = ref(false);
const detailModalOpen = ref(false);
const selectedNotice = ref(null);

const viewNoticeDetails = (notice) => {
    selectedNotice.value = notice;
    detailModalOpen.value = true;
};

watch([modalOpen, detailModalOpen], ([newVal1, newVal2]) => {
    const mainEl = document.querySelector('main');
    if (newVal1 || newVal2) {
        document.body.classList.add('overflow-hidden');
        if (mainEl) {
            mainEl.classList.add('overflow-hidden');
            mainEl.style.overflow = 'hidden';
        }
    } else {
        document.body.classList.remove('overflow-hidden');
        if (mainEl) {
            mainEl.classList.remove('overflow-hidden');
            mainEl.style.overflow = '';
        }
    }
});

onUnmounted(() => {
    document.body.classList.remove('overflow-hidden');
    const mainEl = document.querySelector('main');
    if (mainEl) {
        mainEl.classList.remove('overflow-hidden');
        mainEl.style.overflow = '';
    }
});

const editingNoticeId = ref(null);
const existingDocuments = ref([]);
const editingNoticePublished = ref(false);

const fileFields = ref([{ id: Date.now(), file: null }]);

const form = useForm({
    title: '',
    content: '',
    target_type: 'all_students',
    course_id: null,
    publish_immediately: false,
    publish: false,
    files: [],
    deleted_file_ids: [],
});

const openCreateModal = () => {
    isEditing.value = false;
    editingNoticeId.value = null;
    existingDocuments.value = [];
    editingNoticePublished.value = false;
    fileFields.value = [{ id: Date.now(), file: null }];
    
    form.reset();
    form.clearErrors();
    
    form.title = '';
    form.content = '';
    form.target_type = 'all_students';
    form.course_id = null;
    form.publish_immediately = false;
    form.publish = false;
    form.files = [];
    form.deleted_file_ids = [];
    
    modalOpen.value = true;
};

const openEditModal = (notice) => {
    isEditing.value = true;
    editingNoticeId.value = notice.id;
    existingDocuments.value = notice.documents || [];
    editingNoticePublished.value = notice.published_at !== null;
    fileFields.value = [{ id: Date.now(), file: null }];
    
    form.reset();
    form.clearErrors();
    
    form.title = notice.title;
    form.content = notice.content;
    form.target_type = notice.target_type;
    form.course_id = notice.course_id;
    form.publish_immediately = false;
    form.publish = notice.published_at !== null;
    form.files = [];
    form.deleted_file_ids = [];
    
    modalOpen.value = true;
};

// Dynamic attachment helpers
const addFileField = () => {
    fileFields.value.push({ id: Date.now(), file: null });
};

const removeFileField = (index) => {
    fileFields.value.splice(index, 1);
    updateFormFiles();
};

const handleFieldFileChange = (index, event) => {
    const file = event.target.files[0];
    if (file) {
        fileFields.value[index].file = file;
        updateFormFiles();
    }
};

const updateFormFiles = () => {
    form.files = fileFields.value.map(f => f.file).filter(file => file !== null);
};

const toggleDeleteAttachment = (id) => {
    const idx = form.deleted_file_ids.indexOf(id);
    if (idx > -1) {
        form.deleted_file_ids.splice(idx, 1);
    } else {
        form.deleted_file_ids.push(id);
    }
};

const saveNotice = () => {
    if (isEditing.value) {
        // Inertia forms support files via POST requests. To update files, Laravel expects POST with _method=PUT
        form.post(route('notices.update', editingNoticeId.value), {
            headers: {
                'X-HTTP-Method-Override': 'PUT'
            },
            onSuccess: () => {
                modalOpen.value = false;
            }
        });
    } else {
        form.post(route('notices.store'), {
            onSuccess: () => {
                modalOpen.value = false;
            }
        });
    }
};

const publishNotice = (id) => {
    if (confirm('Are you sure you want to publish this notice? It will become immediately visible to students.')) {
        Inertia.post(route('notices.publish', id));
    }
};

const deleteNotice = (notice) => {
    if (confirm(`Are you sure you want to permanently delete the notice "${notice.title}"? This cannot be undone.`)) {
        Inertia.delete(route('notices.destroy', notice.id));
    }
};

// Date helper
const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>

<style scoped>
.bg-brand-blue {
    background-color: #0a1f44;
}
.text-brand-blue {
    color: #0a1f44;
}
</style>
