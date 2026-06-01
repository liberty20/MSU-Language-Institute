<template>
    <Head title="Edit User" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('admin.users.index')" class="text-gray-400 hover:text-[#0a1f44] transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <span class="text-gray-400">/</span>
                <span class="text-gray-400">Users</span>
                <span class="text-gray-400">/</span>
                <span>Edit User</span>
            </div>
        </template>

        <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <form @submit.prevent="submit" class="space-y-6">
                
                <div>
                    <h2 class="text-lg font-bold text-gray-900 mb-4 border-b border-gray-100 pb-2">Profile Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                            <input v-model="form.name" type="text" required
                                   class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm" />
                            <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                            <input v-model="form.email" type="email" required
                                   class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm" />
                            <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">New Password <span class="text-gray-400">(leave blank to keep current)</span></label>
                            <input v-model="form.password" type="password"
                                   class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm" />
                            <p v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                            <input v-model="form.password_confirmation" type="password"
                                   class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm" />
                            <p v-if="form.errors.password_confirmation" class="text-red-500 text-xs mt-1">{{ form.errors.password_confirmation }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                            <input v-model="form.phone" type="text"
                                   class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm" />
                            <p v-if="form.errors.phone" class="text-red-500 text-xs mt-1">{{ form.errors.phone }}</p>
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="text-lg font-bold text-gray-900 mb-4 border-b border-gray-100 pb-2">Institutional Assignment & Status</h2>
                    <div class="space-y-4">
                        <!-- Select Unit -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">MSUNLI Unit <span class="text-red-500">*</span></label>
                            <select v-model="form.unit_id" required @change="onUnitChange"
                                    class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm">
                                <option value="" disabled>Select parent Unit...</option>
                                <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                    {{ unit.code }} - {{ unit.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.unit_id" class="text-red-500 text-xs mt-1">{{ form.errors.unit_id }}</p>
                        </div>

                        <!-- Select Department/Section -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Department / Section <span class="text-red-500">*</span></label>
                            <select v-model="form.section_id" required :disabled="!form.unit_id" @change="onSectionChange"
                                    class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm disabled:opacity-50">
                                <option value="" disabled>Choose Department/Section...</option>
                                <option v-for="sec in filteredSections" :key="sec.id" :value="sec.id">
                                    {{ sec.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.section_id" class="text-red-500 text-xs mt-1">{{ form.errors.section_id }}</p>
                        </div>

                        <!-- Select Role -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role Assignment <span class="text-red-500">*</span></label>
                            <select v-model="form.msunli_role_id" required :disabled="!form.section_id"
                                    class="w-full border-gray-300 rounded-xl shadow-sm focus:border-[#0a1f44] focus:ring-[#0a1f44] text-sm disabled:opacity-50">
                                <option value="" disabled>Select role...</option>
                                <option v-for="role in filteredRoles" :key="role.id" :value="role.id">
                                    {{ role.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.msunli_role_id" class="text-red-500 text-xs mt-1">{{ form.errors.msunli_role_id }}</p>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-150">
                            <div>
                                <span class="block text-sm font-semibold text-gray-800">Account Status</span>
                                <span class="text-xs text-gray-500">Uncheck to suspend the user and immediately block their login access.</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span :class="form.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2.5 py-1 rounded-full text-xs font-bold uppercase">
                                    {{ form.is_active ? 'Active' : 'Suspended' }}
                                </span>
                                <input id="is_active" v-model="form.is_active" type="checkbox" 
                                       :disabled="user.id === $page.props.auth.user.id || ($page.props.auth.roles.includes('deputy_director') && user.roles.some(r => r.name === 'executive_director'))"
                                       class="rounded text-[#0a1f44] focus:ring-[#0a1f44] w-5 h-5 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4">
                    <Link :href="route('admin.users.index')"
                          class="px-5 py-2.5 border border-gray-300 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </Link>
                    <button type="submit" :disabled="form.processing"
                             class="px-6 py-2.5 bg-[#0a1f44] text-white rounded-xl text-sm font-bold hover:bg-[#0a1f44]/80 transition disabled:opacity-50 shadow-sm flex items-center gap-2">
                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Update User
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/inertia-vue3';
import { computed } from 'vue';

const props = defineProps({
    user: Object,
    units: Array,
    sections: Array,
    msunli_roles: Array,
});

const page = usePage();

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    phone: props.user.phone || '',
    unit_id: props.user.department_id || '',
    section_id: props.user.section_id || '',
    msunli_role_id: props.user.msunli_role_id || '',
    is_active: props.user.is_active === 1 || props.user.is_active === true,
});

const filteredSections = computed(() => {
    if (!form.unit_id) return [];
    return props.sections.filter(s => Number(s.unit_id) === Number(form.unit_id));
});

const filteredRoles = computed(() => {
    if (!form.section_id) return [];
    let roles = props.msunli_roles.filter(r => Number(r.section_id) === Number(form.section_id));
    
    const authRoles = page.props.value.auth.roles || [];
    const isAuthorized = authRoles.includes('ict_administrator') || authRoles.includes('executive_director');
    
    // Hide both Executive Director and Deputy Director roles from unauthorized users
    if (!isAuthorized) {
        roles = roles.filter(r => r.name !== 'Executive Director' && r.name !== 'Deputy Director');
    }
    
    return roles;
});

const onUnitChange = () => {
    form.section_id = '';
    form.msunli_role_id = '';
};

const onSectionChange = () => {
    form.msunli_role_id = '';
};

const submit = () => {
    form.put(route('admin.users.update', props.user.id));
};
</script>
