<template>
    <Head title="User Registry & Security Console" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 leading-tight">User Registry & Access Control</h2>
                    <p class="text-xs text-gray-400 mt-1">Manage system administrators, staff members, clients, and enrolled students.</p>
                </div>
                <Link :href="route('admin.users.create')" 
                      class="bg-[#0a1f44] hover:bg-[#0a1f44]/90 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    + Add New User
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Redesigned User Statistics Dashboard -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
                <div class="bg-white p-5 rounded-2xl border border-gray-150/70 shadow-sm flex items-center gap-4 hover:-translate-y-0.5 transform transition duration-300">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Registered</div>
                        <div class="text-2xl font-black text-gray-900 mt-0.5">{{ totalUsers }}</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-gray-150/70 shadow-sm flex items-center gap-4 hover:-translate-y-0.5 transform transition duration-300">
                    <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Staff Members</div>
                        <div class="text-2xl font-black text-gray-900 mt-0.5">{{ counts.staff }}</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-gray-150/70 shadow-sm flex items-center gap-4 hover:-translate-y-0.5 transform transition duration-300">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5"/></svg>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Client Accounts</div>
                        <div class="text-2xl font-black text-gray-900 mt-0.5">{{ counts.client }}</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-gray-150/70 shadow-sm flex items-center gap-4 hover:-translate-y-0.5 transform transition duration-300">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Active Students</div>
                        <div class="text-2xl font-black text-gray-900 mt-0.5">{{ counts.student }}</div>
                    </div>
                </div>
            </div>

            <!-- Redesigned Advanced Filtering Section -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
                    <span class="text-xs font-bold text-brand-blue uppercase tracking-wider flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-brand-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        Advanced Filter controls
                    </span>
                    <button @click="resetFilters" class="text-xs font-bold text-brand-gold-dark hover:text-brand-blue transition">
                        Reset Filters
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <!-- Search Input -->
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Search Registry</label>
                        <div class="relative">
                            <input v-model="filtersForm.search" type="text" placeholder="Name, email, phone..."
                                   class="w-full border-gray-200 rounded-xl text-xs focus:border-brand-gold focus:ring-brand-gold pl-8 py-2" />
                            <span class="absolute inset-y-0 left-2.5 flex items-center text-gray-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </span>
                        </div>
                    </div>

                    <!-- Unit Selector -->
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">MSUNLI Unit</label>
                        <select v-model="filtersForm.unit_id" @change="onUnitChange"
                                class="w-full border-gray-200 rounded-xl text-xs focus:border-brand-gold focus:ring-brand-gold py-2">
                            <option value="">All Units</option>
                            <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                {{ unit.code }} - {{ unit.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Section Selector -->
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Department / Section</label>
                        <select v-model="filtersForm.section_id" :disabled="!filtersForm.unit_id"
                                class="w-full border-gray-200 rounded-xl text-xs focus:border-brand-gold focus:ring-brand-gold disabled:opacity-50 py-2">
                            <option value="">All Sections</option>
                            <option v-for="sec in filteredSections" :key="sec.id" :value="sec.id">
                                {{ sec.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Spatie Role Selector -->
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Access Role</label>
                        <select v-model="filtersForm.role"
                                class="w-full border-gray-200 rounded-xl text-xs focus:border-brand-gold focus:ring-brand-gold py-2">
                            <option value="">All Roles</option>
                            <option v-for="r in roles" :key="r.id" :value="r.name">
                                {{ r.name.replace(/_/g, ' ') }}
                            </option>
                        </select>
                    </div>

                    <!-- Status Selector -->
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">User Status</label>
                        <select v-model="filtersForm.status"
                                class="w-full border-gray-200 rounded-xl text-xs focus:border-brand-gold focus:ring-brand-gold py-2">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-end mt-4">
                    <button @click="applyFilters" class="px-5 py-2 bg-[#0a1f44] text-white rounded-xl text-xs font-semibold hover:bg-[#0a1f44]/80 transition shadow-sm">
                        Apply Registry Filters
                    </button>
                </div>
            </div>

            <!-- Users Category Registry Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- Tab Headers -->
                <div class="border-b border-gray-100 px-6 py-4 bg-gray-50/50 flex flex-col lg:flex-row gap-4 lg:items-center justify-between">
                    <div class="flex flex-wrap gap-2">
                        <button @click="selectCategory('staff')" 
                                class="px-4 py-2 text-xs font-semibold rounded-xl transition flex items-center gap-2 uppercase tracking-wide"
                                :class="activeCategory === 'staff' ? 'bg-[#0a1f44] text-white shadow-sm' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-55'">
                            <span>Staff Members</span>
                            <span class="px-2 py-0.5 text-[10px] rounded-full font-bold"
                                  :class="activeCategory === 'staff' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600'">
                                {{ counts.staff }}
                            </span>
                        </button>
                        <button @click="selectCategory('client')" 
                                class="px-4 py-2 text-xs font-semibold rounded-xl transition flex items-center gap-2 uppercase tracking-wide"
                                :class="activeCategory === 'client' ? 'bg-[#0a1f44] text-white shadow-sm' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-55'">
                            <span>Clients</span>
                            <span class="px-2 py-0.5 text-[10px] rounded-full font-bold"
                                  :class="activeCategory === 'client' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600'">
                                {{ counts.client }}
                            </span>
                        </button>
                        <button @click="selectCategory('student')" 
                                class="px-4 py-2 text-xs font-semibold rounded-xl transition flex items-center gap-2 uppercase tracking-wide"
                                :class="activeCategory === 'student' ? 'bg-[#0a1f44] text-white shadow-sm' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-55'">
                            <span>Students</span>
                            <span class="px-2 py-0.5 text-[10px] rounded-full font-bold"
                                  :class="activeCategory === 'student' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600'">
                                {{ counts.student }}
                            </span>
                        </button>
                        <button @click="selectCategory('all')" 
                                class="px-4 py-2 text-xs font-semibold rounded-xl transition flex items-center gap-2 uppercase tracking-wide"
                                :class="activeCategory === 'all' ? 'bg-[#0a1f44] text-white shadow-sm' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-55'">
                            <span>All Users</span>
                            <span class="px-2 py-0.5 text-[10px] rounded-full font-bold"
                                  :class="activeCategory === 'all' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600'">
                                {{ counts.all }}
                            </span>
                        </button>
                    </div>
                    
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wide mr-1">Export:</span>
                        <button @click="exportUsers('excel')" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Excel
                        </button>
                        <button @click="exportUsers('csv')" class="bg-blue-600 hover:bg-blue-700 text-white px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            CSV
                        </button>
                        <button @click="exportUsers('pdf')" class="bg-rose-600 hover:bg-rose-700 text-white px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            PDF
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100 text-[10px] uppercase tracking-wider text-gray-400">
                                <th class="px-6 py-4 font-bold">User Information</th>
                                <th class="px-6 py-4 font-bold">Contact Details</th>
                                <th class="px-6 py-4 font-bold">Institutional Scope</th>
                                <th class="px-6 py-4 font-bold">Access Roles</th>
                                <th class="px-6 py-4 font-bold">Account Status</th>
                                <th class="px-6 py-4 font-bold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50/50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-[#0a1f44]/5 text-[#0a1f44] flex items-center justify-center font-bold text-sm shadow-sm flex-shrink-0">
                                            {{ user.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-900 text-sm">{{ user.name }}</div>
                                            <div class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider mt-0.5" v-if="user.msunli_role">
                                                {{ user.msunli_role.name }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-700">{{ user.email }}</div>
                                    <div class="text-[10px] text-gray-400 mt-0.5" v-if="user.phone">{{ user.phone }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div v-if="user.department" class="font-bold text-gray-700 text-xs flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded bg-brand-gold flex-shrink-0"></span>
                                        {{ user.department.code }}
                                    </div>
                                    <div v-if="user.section" class="text-[10px] text-gray-400 font-semibold mt-0.5">
                                        {{ user.section.name }}
                                    </div>
                                    <div v-if="!user.department && !user.section" class="text-[10px] text-gray-400 italic">
                                        Unassigned Scope
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <span v-for="role in user.roles" :key="role.id" 
                                              class="px-2 py-0.5 bg-[#0a1f44]/5 text-[#0a1f44] rounded text-[10px] font-bold uppercase tracking-wider border border-[#0a1f44]/10">
                                            {{ role.name.replace(/_/g, ' ') }}
                                        </span>
                                        <span v-if="!user.roles || user.roles.length === 0" class="text-[10px] text-gray-400 italic">No roles</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                          :class="user.is_active ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="user.is_active ? 'bg-green-500' : 'bg-red-500'"></span>
                                        {{ user.is_active ? 'Active' : 'Suspended' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="flex justify-end items-center gap-3">
                                        <Link :href="route('admin.users.edit', user.id)" 
                                              class="px-2.5 py-1.5 rounded-lg border border-gray-250/70 hover:bg-gray-50 text-gray-600 font-semibold transition hover:text-brand-blue">
                                            Edit
                                        </Link>
                                        <button v-if="user.id !== $page.props.auth.user.id && !($page.props.auth.roles.includes('deputy_director') && user.roles.some(r => r.name === 'executive_director'))" 
                                                @click="toggleUserStatus(user)"
                                                :class="user.is_active ? 'border-red-200 hover:bg-red-50 text-red-600 hover:text-red-700' : 'border-green-200 hover:bg-green-50 text-green-600 hover:text-green-700'"
                                                class="px-2.5 py-1.5 rounded-lg border font-semibold transition focus:outline-none">
                                            {{ user.is_active ? 'Suspend' : 'Activate' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-450 italic">No user accounts found matching the query filters.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between" v-if="users.links">
                    <span class="text-xs text-gray-400 font-medium">Showing {{ users.from }} to {{ users.to }} of {{ users.total }} members</span>
                    <div class="flex gap-1">
                        <Link v-for="(link, k) in users.links" :key="k"
                              :href="link.url || '#'"
                              class="px-3 py-1 rounded border text-xs font-semibold transition"
                              :class="link.active ? 'bg-[#0a1f44] border-[#0a1f44] text-white shadow-sm' : 'bg-white border-gray-200 text-gray-500 hover:bg-gray-50'"
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
import { Inertia } from '@inertiajs/inertia';
import { ref, computed } from 'vue';

const props = defineProps({
    users: Object,
    filters: Object,
    units: Array,
    sections: Array,
    roles: Array,
    counts: Object,
});

const filtersForm = ref({
    search: props.filters.search || '',
    unit_id: props.filters.unit_id || '',
    section_id: props.filters.section_id || '',
    role: props.filters.role || '',
    status: props.filters.status || '',
    category: props.filters.category || 'staff',
});

const activeCategory = computed(() => filtersForm.value.category || 'staff');
const totalUsers = computed(() => (props.counts.staff || 0) + (props.counts.client || 0) + (props.counts.student || 0));

const selectCategory = (category) => {
    filtersForm.value.category = category;
    if (category !== 'staff') {
        filtersForm.value.role = '';
    }
    applyFilters();
};

const filteredSections = computed(() => {
    if (!filtersForm.value.unit_id) return [];
    return props.sections.filter(s => Number(s.unit_id) === Number(filtersForm.value.unit_id));
});

const onUnitChange = () => {
    filtersForm.value.section_id = '';
};

const applyFilters = () => {
    Inertia.get(route('admin.users.index'), {
        search: filtersForm.value.search,
        unit_id: filtersForm.value.unit_id,
        section_id: filtersForm.value.section_id,
        role: filtersForm.value.role,
        status: filtersForm.value.status,
        category: filtersForm.value.category,
    }, {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    filtersForm.value = {
        search: '',
        unit_id: '',
        section_id: '',
        role: '',
        status: '',
        category: 'staff',
    };
    applyFilters();
};

const exportUsers = (format) => {
    const params = new URLSearchParams();
    params.append('format', format);
    params.append('category', filtersForm.value.category);
    if (filtersForm.value.search) params.append('search', filtersForm.value.search);
    if (filtersForm.value.unit_id) params.append('unit_id', filtersForm.value.unit_id);
    if (filtersForm.value.section_id) params.append('section_id', filtersForm.value.section_id);
    if (filtersForm.value.role) params.append('role', filtersForm.value.role);
    if (filtersForm.value.status) params.append('status', filtersForm.value.status);

    window.location.href = route('admin.users.export') + '?' + params.toString();
};

const toggleUserStatus = (user) => {
    if (confirm(`Are you sure you want to ${user.is_active ? 'suspend' : 'activate'} this user?`)) {
        Inertia.patch(route('admin.users.toggle', user.id));
    }
};
</script>
