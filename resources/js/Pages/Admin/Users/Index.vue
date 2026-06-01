<template>
    <Head title="User Management" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <span>User Management</span>
                <Link :href="route('admin.users.create')" class="bg-[#0a1f44] hover:bg-[#0a1f44]/80 text-white px-4 py-2 rounded-lg text-sm font-semibold transition shadow-sm">
                    + Add User
                </Link>
            </div>
        </template>

        <!-- Premium Filter and Search Section -->
        <div class="bg-white rounded-2xl border border-gray-100 p-5 mb-6 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <!-- Search Input -->
                <div class="md:col-span-1">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Search Staff</label>
                    <div class="relative">
                        <input v-model="filtersForm.search" type="text" placeholder="Name, email, phone..."
                               class="w-full border-gray-200 rounded-xl text-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] pl-9" />
                        <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                    </div>
                </div>

                <!-- Unit Selector -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">MSUNLI Unit</label>
                    <select v-model="filtersForm.unit_id" @change="onUnitChange"
                            class="w-full border-gray-200 rounded-xl text-sm focus:border-[#0a1f44] focus:ring-[#0a1f44]">
                        <option value="">All Units</option>
                        <option v-for="unit in units" :key="unit.id" :value="unit.id">
                            {{ unit.code }} - {{ unit.name }}
                        </option>
                    </select>
                </div>

                <!-- Section Selector -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Department / Section</label>
                    <select v-model="filtersForm.section_id" :disabled="!filtersForm.unit_id"
                            class="w-full border-gray-200 rounded-xl text-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] disabled:opacity-50">
                        <option value="">All Sections</option>
                        <option v-for="sec in filteredSections" :key="sec.id" :value="sec.id">
                            {{ sec.name }}
                        </option>
                    </select>
                </div>

                <!-- Spatie Role Selector -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Access Role</label>
                    <select v-model="filtersForm.role"
                            class="w-full border-gray-200 rounded-xl text-sm focus:border-[#0a1f44] focus:ring-[#0a1f44]">
                        <option value="">All Roles</option>
                        <option v-for="r in roles" :key="r.id" :value="r.name">
                            {{ r.name.replace(/_/g, ' ') }}
                        </option>
                    </select>
                </div>

                <!-- Status Selector -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Status</label>
                    <div class="flex gap-2">
                        <select v-model="filtersForm.status"
                                class="w-full border-gray-200 rounded-xl text-sm focus:border-[#0a1f44] focus:ring-[#0a1f44]">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                        </select>
                        <button @click="resetFilters" 
                                class="px-3 border border-gray-200 hover:bg-gray-50 text-gray-500 rounded-xl transition flex items-center justify-center"
                                title="Reset Filters">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H17v4.11"/></svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end gap-2 mt-4">
                <button @click="applyFilters" class="px-5 py-2 bg-[#0a1f44] text-white rounded-xl text-sm font-semibold hover:bg-[#0a1f44]/80 transition shadow-sm">
                    Apply Filters
                </button>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500">
                            <th class="px-6 py-4 font-semibold">Staff Member</th>
                            <th class="px-6 py-4 font-semibold">Contact Info</th>
                            <th class="px-6 py-4 font-semibold">Institutional Scoping</th>
                            <th class="px-6 py-4 font-semibold">Access Roles</th>
                            <th class="px-6 py-4 font-semibold">Status</th>
                            <th class="px-6 py-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-900">{{ user.name }}</div>
                                <div class="text-xs text-gray-400 font-medium capitalize" v-if="user.msunli_role">
                                    {{ user.msunli_role.name }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-600">{{ user.email }}</div>
                                <div class="text-xs text-gray-400" v-if="user.phone">{{ user.phone }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div v-if="user.department" class="font-semibold text-gray-700">
                                    {{ user.department.code }}
                                </div>
                                <div v-if="user.section" class="text-xs text-gray-500">
                                    {{ user.section.name }}
                                </div>
                                <div v-if="!user.department && !user.section" class="text-xs text-gray-400 italic">
                                    Unassigned
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="role in user.roles" :key="role.id" 
                                          class="px-2 py-0.5 bg-[#0a1f44]/10 text-[#0a1f44] rounded text-xs font-medium uppercase tracking-wider">
                                        {{ role.name.replace(/_/g, ' ') }}
                                    </span>
                                    <span v-if="!user.roles || user.roles.length === 0" class="text-xs text-gray-400 italic">No roles</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full uppercase"
                                      :class="user.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                                    {{ user.is_active ? 'Active' : 'Suspended' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-3 whitespace-nowrap">
                                <Link :href="route('admin.users.edit', user.id)" class="text-[#0a1f44] hover:text-[#f5c242] font-semibold text-sm transition">Edit</Link>
                                <button v-if="user.id !== $page.props.auth.user.id && !($page.props.auth.roles.includes('deputy_director') && user.roles.some(r => r.name === 'executive_director'))" 
                                        @click="toggleUserStatus(user)"
                                        :class="user.is_active ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800'"
                                        class="font-semibold text-sm transition focus:outline-none">
                                    {{ user.is_active ? 'Suspend' : 'Activate' }}
                                </button>
                            </td>
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">No users found matching these filters.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between" v-if="users.links">
                <span class="text-sm text-gray-500">Showing {{ users.from }} to {{ users.to }} of {{ users.total }}</span>
                <div class="flex gap-1">
                    <Link v-for="(link, k) in users.links" :key="k"
                          :href="link.url || '#'"
                          class="px-3 py-1 rounded border text-sm transition"
                          :class="link.active ? 'bg-[#0a1f44] border-[#0a1f44] text-white' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
                          v-html="link.label">
                    </Link>
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
});

const filtersForm = ref({
    search: props.filters.search || '',
    unit_id: props.filters.unit_id || '',
    section_id: props.filters.section_id || '',
    role: props.filters.role || '',
    status: props.filters.status || '',
});

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
    };
    applyFilters();
};

const toggleUserStatus = (user) => {
    if (confirm(`Are you sure you want to ${user.is_active ? 'suspend' : 'activate'} this user?`)) {
        Inertia.patch(route('admin.users.toggle', user.id));
    }
};
</script>
