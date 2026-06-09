<template>
    <Head title="System Settings Panel" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <span class="font-extrabold text-[#0a1f44]">MSUNLI Institutional Configuration</span>
                <span class="text-xs font-semibold px-3 py-1 bg-[#0a1f44]/10 text-[#0a1f44] rounded-full uppercase tracking-wider">
                    Administrative Access
                </span>
            </div>
        </template>

        <div class="space-y-6 max-w-6xl mx-auto pb-12">
            <!-- Header banner with premium aesthetics -->
            <div class="bg-gradient-to-r from-[#0a1f44] to-[#0d2859] p-8 rounded-2xl border border-[#f5c242]/10 shadow-lg text-white relative overflow-hidden">
                <div class="absolute right-0 top-0 translate-x-10 -translate-y-10 opacity-10 bg-yellow-400 w-40 h-40 rounded-full blur-3xl"></div>
                <div class="relative z-10 space-y-2">
                    <span class="text-xs text-[#f5c242] font-black uppercase tracking-widest bg-white/10 px-3 py-1 rounded-full">System Administration</span>
                    <h3 class="text-2xl font-black leading-tight">MSUNLI Settings Console</h3>
                    <p class="text-sm text-gray-300 max-w-2xl">
                        Consolidated configuration console for managing units, department sections, user roles, public short courses portal metadata, system settings, and email logs.
                    </p>
                </div>
            </div>

            <!-- Tabbed Controls -->
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-wrap gap-2">
                <button v-for="tab in tabsList" :key="tab.value"
                        @click="activeTab = tab.value"
                        class="px-5 py-2.5 text-xs font-bold rounded-xl transition-all inline-flex items-center gap-2"
                        :class="activeTab === tab.value 
                            ? 'bg-[#0a1f44] text-[#f5c242] shadow-md' 
                            : 'text-gray-600 hover:bg-gray-50 hover:text-brand-blue'">
                    <span v-html="tab.icon"></span>
                    <span>{{ tab.label }}</span>
                </button>
            </div>

            <!-- Error and Success Banners in settings -->
            <div v-if="$page.props.flash.success" class="bg-green-50 border-l-4 border-green-500 text-green-800 p-4 rounded-xl shadow-sm text-xs font-semibold">
                {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash.error" class="bg-red-50 border-l-4 border-red-500 text-red-800 p-4 rounded-xl shadow-sm text-xs font-semibold">
                {{ $page.props.flash.error }}
            </div>

            <!-- TAB 1: System Config (General Configuration) -->
            <div v-show="activeTab === 'config'" class="space-y-6">
                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                    <h3 class="text-lg font-bold text-brand-blue mb-2">Portal Options</h3>
                    <p class="text-sm text-gray-500 mb-6">Modify default environment variables and access controls for the MSULI institute.</p>

                    <form @submit.prevent="saveConfig" class="space-y-6 max-w-2xl">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Site Name</label>
                                <input v-model="configForm.site_name" type="text" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm" required />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Administrative Email</label>
                                <input v-model="configForm.admin_email" type="email" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm" required />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Support Phone</label>
                                <input v-model="configForm.support_phone" type="text" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm" required />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Max Upload Size (MB)</label>
                                <input v-model="configForm.max_upload_size" type="number" min="1" max="100" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm" required />
                            </div>
                            <div class="flex items-center gap-2 py-2">
                                <input v-model="configForm.maintenance_mode" type="checkbox" id="maintenance_mode" class="rounded border-gray-300 text-brand-blue focus:ring-brand-blue" />
                                <label for="maintenance_mode" class="text-sm font-bold text-gray-700 select-none">Enable System Maintenance Mode</label>
                            </div>
                            <div class="flex items-center gap-2 py-2">
                                <input v-model="configForm.allow_registrations" type="checkbox" id="allow_registrations" class="rounded border-gray-300 text-brand-blue focus:ring-brand-blue" />
                                <label for="allow_registrations" class="text-sm font-bold text-gray-700 select-none">Allow Public Student Registrations</label>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-100 flex justify-end">
                            <button type="submit" class="px-6 py-2.5 bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold rounded-xl transition shadow text-sm">
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TAB 2: Mail Delivery Monitor -->
            <div v-show="activeTab === 'mail'" class="space-y-6">
                <!-- Statistics cards -->
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Dispatched</div>
                        <div class="text-2xl font-black text-brand-blue mt-1">{{ stats.total }}</div>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Delivered / Sent</div>
                        <div class="text-2xl font-black text-green-600 mt-1">{{ stats.sent + stats.delivered }}</div>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Pending Queue</div>
                        <div class="text-2xl font-black text-amber-500 mt-1">{{ stats.pending }}</div>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Bounced</div>
                        <div class="text-2xl font-black text-purple-650 mt-1">{{ stats.bounced }}</div>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm col-span-2 lg:col-span-1">
                        <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Failures</div>
                        <div class="text-2xl font-black text-red-650 mt-1">{{ stats.failed }}</div>
                    </div>
                </div>

                <!-- Filters and logs table -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex flex-wrap gap-4 items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-bold text-brand-blue">Delivery Logs</span>
                            <div class="flex rounded-lg border border-gray-200 bg-white p-1 text-xs">
                                <button v-for="st in ['all', 'sent', 'pending', 'failed', 'bounced']" :key="st"
                                        @click="filterStatus(st)"
                                        class="px-3 py-1.5 rounded-md font-semibold transition uppercase"
                                        :class="activeStatus === st ? 'bg-[#0a1f44] text-white shadow-sm' : 'text-gray-500 hover:text-brand-blue'">
                                    {{ st }}
                                </button>
                            </div>
                        </div>

                        <!-- Search -->
                        <div class="relative w-full md:w-64">
                            <input v-model="searchQuery" @input="applySearch" type="text" placeholder="Search logs..." 
                                   class="w-full border-gray-200 rounded-xl text-sm focus:border-brand-gold focus:ring-brand-gold pl-9" />
                            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500">
                                    <th class="px-6 py-4 font-semibold">Recipient</th>
                                    <th class="px-6 py-4 font-semibold">Subject & Type</th>
                                    <th class="px-6 py-4 font-semibold">Status</th>
                                    <th class="px-6 py-4 font-semibold">Details / Error</th>
                                    <th class="px-6 py-4 font-semibold text-right">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-sm">
                                <tr v-for="log in emailLogs.data" :key="log.id" class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900">{{ log.recipient_email }}</div>
                                        <div class="text-xs text-gray-400 mt-0.5" v-if="log.sender_email">From: {{ log.sender_email }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-brand-blue">{{ log.subject }}</div>
                                        <div class="text-xs text-gray-400 mt-0.5 capitalize">{{ log.message_type.replace(/_/g, ' ') }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-full uppercase tracking-wider"
                                            :class="{
                                                'bg-green-100 text-green-700': log.status === 'sent' || log.status === 'delivered',
                                                'bg-amber-100 text-amber-700': log.status === 'pending',
                                                'bg-purple-100 text-purple-700': log.status === 'bounced',
                                                'bg-red-100 text-red-700': log.status === 'failed'
                                            }">
                                            {{ log.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-xs max-w-xs truncate" :title="log.error_message">
                                        <span class="text-gray-500" v-if="log.status === 'failed'">{{ log.error_message || 'Unknown network error' }}</span>
                                        <span class="text-gray-450 italic" v-else>Dispatched successfully</span>
                                    </td>
                                    <td class="px-6 py-4 text-right text-gray-500 text-xs">
                                        {{ formatDateTime(log.created_at) }}
                                    </td>
                                </tr>
                                <tr v-if="emailLogs.data.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                        No email logs found matching the filters.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between" v-if="emailLogs.links">
                        <span class="text-sm text-gray-500">Showing {{ emailLogs.from }} to {{ emailLogs.to }} of {{ emailLogs.total }}</span>
                        <div class="flex gap-1">
                            <Link v-for="(link, k) in emailLogs.links" :key="k"
                                  :href="link.url || '#'"
                                  class="px-3 py-1 rounded border text-sm transition"
                                  :class="link.active ? 'bg-[#0a1f44] border-[#0a1f44] text-white' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
                                  v-html="link.label">
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: MSUNLI Units -->
            <div v-show="activeTab === 'units'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Add Unit Form -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
                    <h3 class="font-extrabold text-brand-blue text-sm uppercase tracking-wide border-b border-gray-100 pb-3">Create New Unit</h3>
                    <form @submit.prevent="submitUnit" class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-700 uppercase mb-1">Unit Full Name *</label>
                            <input v-model="unitForm.name" type="text" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="e.g. Special Needs Services Unit" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-700 uppercase mb-1">Unit Short Code *</label>
                            <input v-model="unitForm.code" type="text" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="e.g. SNSU" />
                        </div>
                        <button type="submit" class="w-full bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold text-xs py-2.5 rounded-full transition shadow">
                            + Add Unit
                        </button>
                    </form>
                </div>

                <!-- Units List -->
                <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500">
                                    <th class="px-6 py-4 font-semibold">Unit Code</th>
                                    <th class="px-6 py-4 font-semibold">Unit Full Name</th>
                                    <th class="px-6 py-4 font-semibold text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-sm">
                                <tr v-for="unit in units" :key="unit.id" class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 font-black text-[#0a1f44] text-xs uppercase tracking-wide">
                                        <span class="bg-[#0a1f44]/10 text-[#0a1f44] px-2.5 py-1 rounded">
                                            {{ unit.code }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-semibold text-gray-700">{{ unit.name }}</td>
                                    <td class="px-6 py-4 text-right space-x-2">
                                        <button @click="deleteUnit(unit.id)" class="text-red-600 hover:text-red-800 text-xs font-bold transition">Delete</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 4: MSUNLI Departments/Sections -->
            <div v-show="activeTab === 'sections'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Add Section Form -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
                    <h3 class="font-extrabold text-brand-blue text-sm uppercase tracking-wide border-b border-gray-100 pb-3">Create Department/Section</h3>
                    <form @submit.prevent="submitSection" class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-700 uppercase mb-1">Select Unit *</label>
                            <select v-model="sectionForm.unit_id" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm">
                                <option value="" disabled>Choose parent Unit...</option>
                                <option v-for="u in units" :key="u.id" :value="u.id">{{ u.code }} ({{ u.name }})</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-700 uppercase mb-1">Section Name *</label>
                            <input v-model="sectionForm.name" type="text" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="e.g. Lexicography" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-700 uppercase mb-1">Section Code (Optional)</label>
                            <input v-model="sectionForm.code" type="text" class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="e.g. LEX" />
                        </div>
                        <button type="submit" class="w-full bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold text-xs py-2.5 rounded-full transition shadow">
                            + Add Section
                        </button>
                    </form>
                </div>

                <!-- Sections List -->
                <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500">
                                    <th class="px-6 py-4 font-semibold">Parent Unit</th>
                                    <th class="px-6 py-4 font-semibold">Section Name</th>
                                    <th class="px-6 py-4 font-semibold">Code</th>
                                    <th class="px-6 py-4 font-semibold text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-sm">
                                <tr v-for="sec in sections" :key="sec.id" class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-0.5 bg-[#0a1f44]/10 text-[#0a1f44] rounded text-xs font-bold uppercase tracking-wider">
                                            {{ sec.unit?.code }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-semibold text-gray-700">{{ sec.name }}</td>
                                    <td class="px-6 py-4 text-xs font-medium text-gray-500">{{ sec.code }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <button @click="deleteSection(sec.id)" class="text-red-600 hover:text-red-800 text-xs font-bold transition">Delete</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 5: MSUNLI Roles -->
            <div v-show="activeTab === 'roles'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Add Role Form -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
                    <h3 class="font-extrabold text-brand-blue text-sm uppercase tracking-wide border-b border-gray-100 pb-3">Create Institutional Role</h3>
                    <form @submit.prevent="submitRole" class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-700 uppercase mb-1">Select Unit *</label>
                            <select v-model="selectedUnitId" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm">
                                <option value="" disabled>Choose Unit...</option>
                                <option v-for="u in units" :key="u.id" :value="u.id">{{ u.code }} ({{ u.name }})</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-700 uppercase mb-1">Select Department/Section *</label>
                            <select v-model="roleForm.section_id" required :disabled="filteredSections.length === 0" class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm disabled:opacity-50">
                                <option value="" disabled>Choose Section...</option>
                                <option v-for="s in filteredSections" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-700 uppercase mb-1">Role Title *</label>
                            <input v-model="roleForm.name" type="text" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="e.g. Language Researcher" />
                            <span class="text-[9px] text-gray-400 block mt-1 leading-relaxed">* Note: Adding a role dynamically registers a corresponding Spatie Role slug in the RBAC outbox automatically.</span>
                        </div>
                        <button type="submit" class="w-full bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold text-xs py-2.5 rounded-full transition shadow">
                            + Add Role
                        </button>
                    </form>
                </div>

                <!-- Roles List -->
                <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500">
                                    <th class="px-6 py-4 font-semibold">Unit & Section</th>
                                    <th class="px-6 py-4 font-semibold">Role Name</th>
                                    <th class="px-6 py-4 font-semibold">RBAC Spatie Slug</th>
                                    <th class="px-6 py-4 font-semibold text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-sm">
                                <tr v-for="role in roles" :key="role.id" class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-[#0a1f44] text-xs uppercase tracking-wider">
                                            {{ role.section?.unit?.code }}
                                        </div>
                                        <div class="text-[10px] text-gray-400 font-semibold uppercase mt-0.5">{{ role.section?.name }}</div>
                                    </td>
                                    <td class="px-6 py-4 font-semibold text-gray-700">{{ role.name }}</td>
                                    <td class="px-6 py-4 text-xs font-semibold text-brand-gold-dark mt-0.5">
                                        {{ role.spatie_role?.name }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button @click="deleteRole(role.id)" class="text-red-600 hover:text-red-800 text-xs font-bold transition">Delete</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 6: Short Courses Public Portal Config -->
            <div v-show="activeTab === 'short-courses'" class="bg-white p-8 rounded-2xl border border-gray-100 shadow-sm space-y-8 text-xs">
                <div>
                    <h3 class="font-extrabold text-[#0a1f44] text-base uppercase border-b border-gray-100 pb-3">Short Courses Public Information Portal Config</h3>
                    <p class="text-gray-400 text-xs mt-1">Configure Announcements, FAQs, Testimonials, Banking, and Contact Information directly.</p>
                </div>

                <form @submit.prevent="submitPortalSettings" class="space-y-8">
                    <!-- A. Contact Information -->
                    <div class="space-y-4">
                        <h4 class="font-bold text-[#0a1f44] text-sm border-b border-gray-50 pb-2">1. Contact Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-700 uppercase mb-1">Email Address *</label>
                                <input v-model="portalForm.contactInfo.email" type="email" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-700 uppercase mb-1">Landline Phone *</label>
                                <input v-model="portalForm.contactInfo.phone" type="text" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-700 uppercase mb-1">Mobile Contact *</label>
                                <input v-model="portalForm.contactInfo.mobile" type="text" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-700 uppercase mb-1">Office Hours *</label>
                                <input v-model="portalForm.contactInfo.hours" type="text" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" />
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-black text-gray-700 uppercase mb-1">Office Location Address *</label>
                                <input v-model="portalForm.contactInfo.location" type="text" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" />
                            </div>
                        </div>
                    </div>

                    <!-- B. Banking Details -->
                    <div class="space-y-4">
                        <h4 class="font-bold text-[#0a1f44] text-sm border-b border-gray-50 pb-2">2. Banking Details</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-700 uppercase mb-1">Account Name *</label>
                                <input v-model="portalForm.bankingDetails.account_name" type="text" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-700 uppercase mb-1">Bank Name *</label>
                                <input v-model="portalForm.bankingDetails.bank" type="text" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-700 uppercase mb-1">Branch Name *</label>
                                <input v-model="portalForm.bankingDetails.branch" type="text" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-700 uppercase mb-1">Account Type *</label>
                                <input v-model="portalForm.bankingDetails.type" type="text" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="e.g. Current Account" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-700 uppercase mb-1">Local Account Number *</label>
                                <input v-model="portalForm.bankingDetails.account_number" type="text" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-700 uppercase mb-1">Nostro USD Account Number *</label>
                                <input v-model="portalForm.bankingDetails.nostro_number" type="text" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" />
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-black text-gray-700 uppercase mb-1">Currency Accepted *</label>
                                <input v-model="portalForm.bankingDetails.currency_accepted" type="text" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="e.g. USD, ZiG" />
                            </div>
                        </div>
                    </div>

                    <!-- C. Announcements -->
                    <div class="space-y-4">
                        <div class="flex justify-between items-center border-b border-gray-50 pb-2">
                            <h4 class="font-bold text-[#0a1f44] text-sm">3. Announcements &amp; Notices</h4>
                            <button type="button" @click="addAnnouncement" class="text-xs font-black text-blue-600 hover:underline">+ Add Notice</button>
                        </div>
                        <div class="space-y-4">
                            <div v-for="(ann, idx) in portalForm.announcements" :key="idx" class="p-4 bg-gray-50 rounded-2xl border border-gray-150 relative space-y-3">
                                <button type="button" @click="removeAnnouncement(idx)" class="absolute top-2 right-4 text-xs font-black text-red-650 hover:underline">Remove</button>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-[9px] font-black text-gray-700 uppercase mb-0.5">Date *</label>
                                        <input v-model="ann.date" type="date" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" />
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-[9px] font-black text-gray-700 uppercase mb-0.5">Notice Title *</label>
                                        <input v-model="ann.title" type="text" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="e.g. Winter 2026 UEB Admission" />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[9px] font-black text-gray-700 uppercase mb-0.5">Notice Description Text *</label>
                                    <textarea v-model="ann.text" rows="2" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="Details of announcement..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- D. FAQs -->
                    <div class="space-y-4">
                        <div class="flex justify-between items-center border-b border-gray-50 pb-2">
                            <h4 class="font-bold text-[#0a1f44] text-sm">4. Frequently Asked Questions</h4>
                            <button type="button" @click="addFaq" class="text-xs font-black text-blue-600 hover:underline">+ Add FAQ</button>
                        </div>
                        <div class="space-y-4">
                            <div v-for="(faq, idx) in portalForm.faqs" :key="idx" class="p-4 bg-gray-50 rounded-2xl border border-gray-150 relative space-y-3">
                                <button type="button" @click="removeFaq(idx)" class="absolute top-2 right-4 text-xs font-black text-red-650 hover:underline">Remove</button>
                                <div>
                                    <label class="block text-[9px] font-black text-gray-700 uppercase mb-0.5">Question Text *</label>
                                    <input v-model="faq.question" type="text" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="e.g. How do I apply?" />
                                </div>
                                <div>
                                    <label class="block text-[9px] font-black text-gray-700 uppercase mb-0.5">Answer Text *</label>
                                    <textarea v-model="faq.answer" rows="2" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="Helpful answer details..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- E. Testimonials -->
                    <div class="space-y-4">
                        <div class="flex justify-between items-center border-b border-gray-50 pb-2">
                            <h4 class="font-bold text-[#0a1f44] text-sm">5. Testimonials &amp; Success Stories</h4>
                            <button type="button" @click="addTestimonial" class="text-xs font-black text-blue-600 hover:underline">+ Add Testimonial</button>
                        </div>
                        <div class="space-y-4">
                            <div v-for="(t, idx) in portalForm.testimonials" :key="idx" class="p-4 bg-gray-50 rounded-2xl border border-gray-150 relative space-y-3">
                                <button type="button" @click="removeTestimonial(idx)" class="absolute top-2 right-4 text-xs font-black text-red-650 hover:underline">Remove</button>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[9px] font-black text-gray-700 uppercase mb-0.5">Student Name *</label>
                                        <input v-model="t.name" type="text" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="e.g. Sandra Gumbo" />
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-black text-gray-700 uppercase mb-0.5">Assigned Course *</label>
                                        <input v-model="t.course" type="text" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="e.g. Unified English Braille" />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[9px] font-black text-gray-700 uppercase mb-0.5">Testimonial Quote Text *</label>
                                    <textarea v-model="t.text" rows="2" required class="w-full text-xs rounded-xl border-gray-300 focus:border-brand-gold focus:ring-brand-gold shadow-sm" placeholder="Quote from the student..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-5 border-t border-gray-150 flex justify-end">
                        <button type="submit" class="bg-[#0a1f44] hover:bg-[#0c2859] text-[#f5c242] font-black text-xs px-8 py-3 rounded-full transition shadow-md uppercase tracking-wider">
                            Save Portal Configurations
                        </button>
                    </div>
                </form>
            </div>

            <!-- TAB 7: Danger Zone (Database Reset) -->
            <div v-show="activeTab === 'reset'" class="space-y-6">
                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                    <h3 class="text-lg font-bold text-red-600 mb-2">Danger Zone - System Data Reset</h3>
                    <p class="text-sm text-gray-500 mb-6">Wipe out operational data models. This will clean the system records to start fresh without sample data.</p>

                    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-xl mb-6">
                        <div class="flex gap-3">
                            <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <div>
                                <h4 class="text-sm font-bold text-red-800">Critical warning: Permanent Deletion</h4>
                                <p class="text-xs text-red-700 mt-1">
                                    Performing this operation will permanently delete:
                                </p>
                                <ul class="list-disc pl-5 text-xs text-red-700 mt-1 space-y-0.5">
                                    <li>All Clients and their linked User accounts</li>
                                    <li>All Quotations and Service Requests</li>
                                    <li>All Assignments, Instructor Tasks, and Subtasks</li>
                                    <li>All Client Payments and proof records</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <button @click="confirmReset = true" class="bg-red-650 hover:bg-red-700 text-white font-bold px-6 py-2.5 rounded-xl transition shadow text-sm">
                        Reset Operational Database
                    </button>
                </div>
            </div>

        </div>

        <!-- Confirm Reset Modal -->
        <div v-if="confirmReset" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl p-6 space-y-4">
                <div class="flex items-center gap-3 text-red-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <h3 class="text-lg font-bold">Confirm Database Reset?</h3>
                </div>
                <p class="text-sm text-gray-500 leading-relaxed">
                    Are you absolutely sure you want to clean the system? This will delete all client histories, request workflows, billing quotations, and instructor assignments. <strong>This action cannot be undone.</strong>
                </p>
                <div class="pt-4 flex justify-end gap-3">
                    <button @click="confirmReset = false" class="px-4 py-2 border border-gray-300 rounded-xl font-semibold text-gray-700 hover:bg-gray-50 transition text-sm">
                        Cancel
                    </button>
                    <button @click="executeReset" class="px-4 py-2 bg-red-650 hover:bg-red-750 text-white font-bold rounded-xl transition shadow text-sm">
                        Yes, Reset Data
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/inertia-vue3';
import { ref, reactive, computed } from 'vue';
import { Inertia } from '@inertiajs/inertia';

const props = defineProps({
    units: Array,
    sections: Array,
    roles: Array,
    spatieRoles: Array,
    faqs: Array,
    testimonials: Array,
    announcements: Array,
    contactInfo: Object,
    bankingDetails: Object,
    emailLogs: Object,
    stats: Object,
    config: Object,
    filters: Object,
});

const activeTab = ref('config');
const confirmReset = ref(false);

const tabsList = computed(() => {
    const list = [
        { value: 'config', label: 'System Configuration', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>' },
        { value: 'mail', label: 'Mail Delivery Monitor', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>' },
        { value: 'units', label: 'MSUNLI Units', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>' },
        { value: 'sections', label: 'Departments & Sections', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>' },
        { value: 'roles', label: 'Institutional Roles', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>' },
        { value: 'short-courses', label: 'Short Courses Portal', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>' }
    ];
    if (usePage().props.value.auth.roles.includes('deputy_director')) {
        list.push({ value: 'reset', label: 'Danger Zone', icon: '<svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>' });
    }
    return list;
});

// Config Form state
const configForm = ref({
    site_name: props.config.site_name || '',
    admin_email: props.config.admin_email || '',
    support_phone: props.config.support_phone || '',
    max_upload_size: props.config.max_upload_size || 10,
    maintenance_mode: props.config.maintenance_mode === 1 || props.config.maintenance_mode === true,
    allow_registrations: props.config.allow_registrations === 1 || props.config.allow_registrations === true,
});

const searchQuery = ref(props.filters.search || '');
const activeStatus = computed(() => props.filters.status || 'all');

const saveConfig = () => {
    Inertia.post(route('admin.settings.config.update'), configForm.value, {
        preserveState: true,
    });
};

const filterStatus = (status) => {
    Inertia.get(route('admin.settings.index'), {
        status: status,
        search: searchQuery.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const applySearch = () => {
    Inertia.get(route('admin.settings.index'), {
        status: activeStatus.value,
        search: searchQuery.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const executeReset = () => {
    confirmReset.value = false;
    Inertia.post(route('admin.settings.reset'), {}, {
        onSuccess: () => {
            activeTab.value = 'config';
        }
    });
};

const formatDateTime = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) + 
           ' ' + date.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
};

// Unit Form CRUD
const unitForm = reactive({
    name: '',
    code: '',
});

const submitUnit = () => {
    Inertia.post(route('admin.settings.units.store'), unitForm, {
        onSuccess: () => {
            unitForm.name = '';
            unitForm.code = '';
        }
    });
};

const deleteUnit = (id) => {
    if (confirm('Are you sure you want to delete this Unit? All sections configured under it will be cascadingly deleted.')) {
        Inertia.delete(route('admin.settings.units.destroy', id));
    }
};

// Section Form CRUD
const sectionForm = reactive({
    unit_id: '',
    name: '',
    code: '',
});

const submitSection = () => {
    Inertia.post(route('admin.settings.sections.store'), sectionForm, {
        onSuccess: () => {
            sectionForm.name = '';
            sectionForm.code = '';
        }
    });
};

const deleteSection = (id) => {
    if (confirm('Are you sure you want to delete this Department/Section?')) {
        Inertia.delete(route('admin.settings.sections.destroy', id));
    }
};

// Role Form CRUD
const selectedUnitId = ref('');
const roleForm = reactive({
    section_id: '',
    name: '',
});

const filteredSections = computed(() => {
    if (!selectedUnitId.value) return [];
    return props.sections.filter(s => Number(s.unit_id) === Number(selectedUnitId.value));
});

const submitRole = () => {
    Inertia.post(route('admin.settings.roles.store'), roleForm, {
        onSuccess: () => {
            roleForm.name = '';
            roleForm.section_id = '';
            selectedUnitId.value = '';
        }
    });
};

const deleteRole = (id) => {
    if (confirm('Are you sure you want to delete this institutional role?')) {
        Inertia.delete(route('admin.settings.roles.destroy', id));
    }
};

// Portal settings Form CRUD
const portalForm = reactive({
    faqs: props.faqs ? JSON.parse(JSON.stringify(props.faqs)) : [],
    testimonials: props.testimonials ? JSON.parse(JSON.stringify(props.testimonials)) : [],
    announcements: props.announcements ? JSON.parse(JSON.stringify(props.announcements)) : [],
    contactInfo: props.contactInfo ? JSON.parse(JSON.stringify(props.contactInfo)) : { email: '', phone: '', mobile: '', location: '', hours: '' },
    bankingDetails: props.bankingDetails ? JSON.parse(JSON.stringify(props.bankingDetails)) : { account_name: '', bank: '', branch: '', account_number: '', nostro_number: '', type: '', currency_accepted: '' }
});

const addFaq = () => {
    portalForm.faqs.push({ question: '', answer: '' });
};
const removeFaq = (index) => {
    portalForm.faqs.splice(index, 1);
};

const addTestimonial = () => {
    portalForm.testimonials.push({ name: '', course: '', text: '' });
};
const removeTestimonial = (index) => {
    portalForm.testimonials.splice(index, 1);
};

const addAnnouncement = () => {
    portalForm.announcements.push({ date: new Date().toISOString().split('T')[0], title: '', text: '' });
};
const removeAnnouncement = (index) => {
    portalForm.announcements.splice(index, 1);
};

const submitPortalSettings = () => {
    Inertia.post(route('admin.settings.short-courses.update'), portalForm, {
        onSuccess: () => {
            // Success!
        }
    });
};
</script>
