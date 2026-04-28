<template>
    <CompanyLayout>
        <div class="flex flex-wrap w-full">
            <div class="flex justify-between w-full">
                <div class="h-auto">
                    <h1 class="my-0 text-xl font-bold text-gray-800 lg:text-2xl">Roles list</h1>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="fetchRoles"
                        class="flex items-center gap-2 px-3 py-2 text-sm text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                        <i class="bi bi-arrow-clockwise" :class="{ 'animate-spin': loading }"></i>
                        Refresh
                    </button>
                    <button @click="openModal"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary/90 transition-colors shadow-sm">
                        <i class="bi bi-person-plus"></i>
                        Create New
                    </button>
                </div>
            </div>
        </div>

        <!-- Roles Table (custom, no PrimeVue) -->
        <div class="my-4 border border-gray-300 rounded-xl overflow-x-auto">
            <!-- Loading skeleton -->
            <div v-if="loading" class="flex flex-col p-4">
                <div v-for="i in 4" :key="i" class="flex items-center gap-4 mb-4">
                    <div class="h-8 bg-gray-200 rounded w-32 animate-pulse"></div>
                    <div class="flex-1 h-8 bg-gray-200 rounded animate-pulse"></div>
                    <div class="w-24 h-8 bg-gray-200 rounded animate-pulse"></div>
                    <div class="w-24 h-8 bg-gray-200 rounded animate-pulse"></div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else-if="roles.length === 0" class="p-8 text-center text-gray-500">
                No Role found.
            </div>

            <!-- Table -->
            <table v-else class="min-w-full bg-white">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Role Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Permissions</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase min-w-40">Created
                            By</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="role in roles" :key="role.id" class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ role.name }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                <span v-for="perm in role.permissions" :key="perm.id"
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ perm.name }}
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span
                                class="inline-flex items-center px-3 py-1 text-xs font-semibold text-cyan-700 bg-cyan-100 border border-cyan-200 rounded-md">
                                {{ role.created_by || '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center">

                                <!-- View -->
                                <button @click="openViewModal(role)"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg text-purple-600 hover:bg-purple-50 border border-transparent hover:border-purple-200 transition-all"
                                    title="View">
                                    <i class="bi bi-eye text-sm"></i>
                                </button>

                                <!-- Edit -->
                                <button @click="openEditModal(role)"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg text-blue-600 hover:bg-blue-50 border border-transparent hover:border-blue-200 transition-all"
                                    title="Edit">
                                    <i class="bi bi-pencil text-sm"></i>
                                </button>

                                <!-- Delete -->
                                <button @click="DeleteModal(role.id)"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg text-red-500 hover:bg-red-50 border border-transparent hover:border-red-200 transition-all"
                                    title="Delete">
                                    <i class="bi bi-trash text-sm"></i>
                                </button>

                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
            @click.self="closeModal">
            <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl max-h-[90vh] flex flex-col">
                <!-- Header -->
                <div class="flex justify-between px-6 py-4 bg-gray-100 border-b rounded-t-2xl">
                    <h2 class="text-lg font-bold">{{ editId ? 'Edit Role' : 'Create New Role' }}</h2>
                    <button @click="closeModal" class="text-gray-500 hover:text-gray-900">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="flex-1 p-6 overflow-y-auto">
                    <!-- Role Name -->
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Role Name</label>
                        <input type="text" v-model="roleName"
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                            :class="roleNameError ? 'border-red-500' : 'border-gray-300'"
                            placeholder="Enter role name" />
                        <div v-if="roleNameError" class="mt-1 text-sm text-red-500">{{ roleNameError }}</div>
                    </div>

                    <!-- Permissions Table -->
                    <div class="max-h-[50vh] overflow-y-auto">
                        <div v-if="loadingPermissions" class="space-y-2">
                            <div v-for="i in 6" :key="i" class="w-full h-10 bg-gray-200 rounded animate-pulse"></div>
                        </div>
                        <table v-else class="min-w-full border border-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-2 text-left">Module</th>
                                    <th class="px-4 py-2 text-center">View</th>
                                    <th class="px-4 py-2 text-center">Create</th>
                                    <th class="px-4 py-2 text-center">Edit</th>
                                    <th class="px-4 py-2 text-center">Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(section, key) in permissions" :key="key" class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-2 font-medium">{{ section.label }}</td>
                                    <!-- View -->
                                    <td class="px-4 py-2 text-center">
                                        <input type="checkbox" :value="key" @change="togglePermission(section, key)"
                                            :checked="selectedPermissions.includes(key)"
                                            class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                                    </td>
                                    <!-- Create -->
                                    <td class="px-4 py-2 text-center">
                                        <input v-if="section.permissions[`create-${key.replace('-management', '')}`]"
                                            type="checkbox" :value="`create-${key.replace('-management', '')}`"
                                            @change="togglePermission(section, `create-${key.replace('-management', '')}`)"
                                            :checked="selectedPermissions.includes(`create-${key.replace('-management', '')}`)"
                                            class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                                        <span v-else>—</span>
                                    </td>
                                    <!-- Edit -->
                                    <td class="px-4 py-2 text-center">
                                        <input v-if="section.permissions[`edit-${key.replace('-management', '')}`]"
                                            type="checkbox" :value="`edit-${key.replace('-management', '')}`"
                                            @change="togglePermission(section, `edit-${key.replace('-management', '')}`)"
                                            :checked="selectedPermissions.includes(`edit-${key.replace('-management', '')}`)"
                                            class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                                        <span v-else>—</span>
                                    </td>
                                    <!-- Delete -->
                                    <td class="px-4 py-2 text-center">
                                        <input v-if="section.permissions[`delete-${key.replace('-management', '')}`]"
                                            type="checkbox" :value="`delete-${key.replace('-management', '')}`"
                                            @change="togglePermission(section, `delete-${key.replace('-management', '')}`)"
                                            :checked="selectedPermissions.includes(`delete-${key.replace('-management', '')}`)"
                                            class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
                                        <span v-else>—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div v-if="permissionsError"
                            class="flex items-center justify-between px-3 py-2 mt-4 text-white bg-red-500 rounded-md">
                            {{ permissionsError }}
                            <button @click="permissionsError = ''" class="text-white hover:text-gray-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex justify-between items-center px-6 py-4 bg-gray-100 border-t rounded-b-2xl">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" @change="selectAllPermissions" :checked="isAllSelected"
                            class="w-5 h-5 text-blue-600 border-gray-300 rounded" />
                        <span class="text-sm font-medium">Select All Permissions</span>
                    </label>
                    <button v-if="!disableSubmitted" @click="submitForm"
                        class="px-5 py-2 text-white bg-primary rounded-lg hover:bg-primary/90 transition">
                        Submit
                    </button>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
            @click.self="closeDeleteModal">
            <div class="relative w-full max-w-md bg-white rounded-lg shadow-xl">
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold">Delete Role</h3>
                            <p class="text-sm text-gray-500">Are you sure you want to delete this role? This action
                                cannot be undone.</p>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50 rounded-b-lg">
                    <button @click="closeDeleteModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border rounded-md hover:bg-gray-50">Cancel</button>
                    <button @click="confirmDelete"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700">Delete</button>
                </div>
            </div>
        </div>
    </CompanyLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import CompanyLayout from "@/Layouts/CompanyLayout.vue";
import toast from '@/Services/toast'

// ── State ─────────────────────────────────────────────────────────────────────
const isOpen = ref(false)
const editId = ref(null)
const showDeleteModal = ref(false)
const deleteId = ref(null)
const loading = ref(false)
const disableSubmitted = ref(false)

const loadingPermissions = ref(false)
const permissions = ref({})
const selectedPermissions = ref([])
const roles = ref([])
const roleName = ref('')
const roleNameError = ref('')
const permissionsError = ref('')

// ── Computed ──────────────────────────────────────────────────────────────────
const isAllSelected = computed(() => {
    if (!permissions.value || Object.keys(permissions.value).length === 0) return false
    const allKeys = []
    Object.values(permissions.value).forEach(section => {
        if (section.permissions) {
            allKeys.push(...Object.keys(section.permissions))
        }
    })
    return allKeys.length > 0 && allKeys.every(key => selectedPermissions.value.includes(key))
})

// ── API calls (using company routes) ─────────────────────────────────────────
const fetchRoles = async () => {
    try {
        loading.value = true
        const { data } = await axios.get(route('company.roles.list'))
        roles.value = data
        toast.info('Loaded', 'Roles fetched successfully.')
    } catch (error) {
        toast.error('Error', 'Failed to fetch roles')
        roles.value = []
    } finally {
        loading.value = false
    }
}

const fetchPermissions = async () => {
    try {
        loadingPermissions.value = true
        const { data } = await axios.get(route('company.permissions'))
        permissions.value = data
    } catch (error) {
        toast.error('Error', 'Failed to fetch permissions')
        permissions.value = {}
    } finally {
        loadingPermissions.value = false
    }
}

const submitForm = async () => {
    if (!roleName.value.trim()) {
        roleNameError.value = 'Role name is required.'
        return
    }
    roleNameError.value = ''

    if (selectedPermissions.value.length === 0) {
        permissionsError.value = 'At least one permission must be selected.'
        return
    }
    permissionsError.value = ''

    try {
        const payload = {
            name: roleName.value,
            editId: editId.value || null,
            permissions: selectedPermissions.value
        }

        const { data } = await axios.post(route('company.roles.save'), payload)

        if (data.status === 'success') {
            toast.success(
                'Success',
                editId.value ? 'Role updated.' : 'Role created.'
            )
            closeModal()
            fetchRoles()
        } else {
            toast.error('Error', data.message || 'Something went wrong.')
        }

    } catch (err) {
        const msg = err.response?.data?.message || 'Failed to save role'
        toast.error('Error', msg)
    }
}


const confirmDelete = async () => {
    try {
        const { data } = await axios.delete(
            route('company.roles.delete', { id: deleteId.value })
        )

        if (data.status === true) {
            toast.success('Deleted', 'Role deleted successfully.')
            closeDeleteModal()
            fetchRoles()
        } else {
            toast.error('Error', 'Could not delete role.')
        }

    } catch (err) {
        toast.error('Error', 'Failed to delete role.')
    }
}

// ── Modal helpers ────────────────────────────────────────────────────────────
const openModal = () => {
    editId.value = null
    roleName.value = ''
    selectedPermissions.value = []
    roleNameError.value = ''
    permissionsError.value = ''
    disableSubmitted.value = false
    fetchPermissions()
    isOpen.value = true
}

const openEditModal = (role) => {
    editId.value = role.id
    roleName.value = role.name
    selectedPermissions.value = role.permissions.map(p => p.name)
    roleNameError.value = ''
    permissionsError.value = ''
    disableSubmitted.value = false
    fetchPermissions()
    isOpen.value = true
}

const openViewModal = (role) => {
    editId.value = role.id
    roleName.value = role.name
    selectedPermissions.value = role.permissions.map(p => p.name)
    roleNameError.value = ''
    permissionsError.value = ''
    disableSubmitted.value = true
    fetchPermissions()
    isOpen.value = true
}

const closeModal = () => {
    isOpen.value = false
    editId.value = null
    roleName.value = ''
    selectedPermissions.value = []
    disableSubmitted.value = false
    roleNameError.value = ''
    permissionsError.value = ''
}

const DeleteModal = (id) => {
    deleteId.value = id
    showDeleteModal.value = true
}

const closeDeleteModal = () => {
    showDeleteModal.value = false
    deleteId.value = null
}

// ── Permission toggles ───────────────────────────────────────────────────────
const togglePermission = (section, permissionKey) => {
    const idx = selectedPermissions.value.indexOf(permissionKey)
    if (idx === -1) {
        selectedPermissions.value.push(permissionKey)
    } else {
        selectedPermissions.value.splice(idx, 1)
    }
}

const selectAllPermissions = (event) => {
    const checked = event.target.checked
    if (checked) {
        const allKeys = []
        Object.values(permissions.value).forEach(section => {
            if (section.permissions) {
                allKeys.push(...Object.keys(section.permissions))
            }
        })
        selectedPermissions.value = [...new Set(allKeys)]
    } else {
        selectedPermissions.value = []
    }
}

// ── Lifecycle ────────────────────────────────────────────────────────────────
onMounted(() => {
    fetchRoles()
})
</script>
