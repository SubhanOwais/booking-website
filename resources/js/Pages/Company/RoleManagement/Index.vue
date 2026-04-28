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
        <div class="my-4 border border-gray-300 overflow-x-auto">
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
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Created By</th>
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
                            <span class="inline-flex items-center px-2 py-1 text-sm font-medium text-purple-800 bg-purple-100 border border-purple-500 rounded-md">
                                {{ role.created_by || '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-2">
                                <!-- View -->
                                <button @click="openViewModal(role)" title="View"
                                    class="p-2 text-purple-600 border border-purple-600 rounded-full shadow bg-purple-500/25 hover:bg-purple-600 hover:text-white transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                                        <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z" />
                                        <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0" />
                                    </svg>
                                </button>
                                <!-- Edit -->
                                <button @click="openEditModal(role)" title="Edit"
                                    class="p-2 text-blue-600 border border-blue-600 rounded-full shadow bg-blue-500/25 hover:bg-blue-600 hover:text-white transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>
                                <!-- Delete -->
                                <button @click="DeleteModal(role.id)" title="Delete"
                                    class="p-2 text-red-600 border border-red-600 rounded-full shadow bg-red-500/25 hover:bg-red-600 hover:text-white transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
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
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
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
                        <div v-if="permissionsError" class="flex items-center justify-between px-3 py-2 mt-4 text-white bg-red-500 rounded-md">
                            {{ permissionsError }}
                            <button @click="permissionsError = ''" class="text-white hover:text-gray-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
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
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
            @click.self="closeDeleteModal">
            <div class="relative w-full max-w-md bg-white rounded-lg shadow-xl">
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold">Delete Role</h3>
                            <p class="text-sm text-gray-500">Are you sure you want to delete this role? This action cannot be undone.</p>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50 rounded-b-lg">
                    <button @click="closeDeleteModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border rounded-md hover:bg-gray-50">Cancel</button>
                    <button @click="confirmDelete" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700">Delete</button>
                </div>
            </div>
        </div>

        <!-- Toast Container -->
        <div class="fixed top-4 right-4 z-[100] space-y-2">
            <div v-for="toast in toasts" :key="toast.id" :class="[
                'flex items-center p-4 rounded-lg shadow-lg border-l-4 transition-all duration-300 transform',
                toast.type === 'success' ? 'bg-green-50 border-green-500 text-green-800' : '',
                toast.type === 'error' ? 'bg-red-50 border-red-500 text-red-800' : '',
                toast.type === 'info' ? 'bg-blue-50 border-blue-500 text-blue-800' : '',
                toast.type === 'warning' ? 'bg-yellow-50 border-yellow-500 text-yellow-800' : '',
                toast.visible ? 'translate-x-0 opacity-100' : 'translate-x-full opacity-0',
            ]">
                <div class="flex-shrink-0 mr-3">
                    <svg v-if="toast.type === 'success'" class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    <svg v-else-if="toast.type === 'error'" class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                    <svg v-else-if="toast.type === 'warning'" class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <svg v-else class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="font-medium">{{ toast.summary }}</div>
                    <div class="text-sm">{{ toast.detail }}</div>
                </div>
                <button @click="removeToast(toast.id)" class="ml-4 text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </CompanyLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import CompanyLayout from "@/Layouts/CompanyLayout.vue";

// ── Toast System (self-contained) ────────────────────────────────────────────
const toasts = ref([])
let toastId = 0

const showToast = (type, summary, detail, duration = 5000) => {
    const id = ++toastId
    const toast = { id, type, summary, detail, visible: true }
    toasts.value.push(toast)
    setTimeout(() => removeToast(id), duration)
    return id
}

const removeToast = (id) => {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index !== -1) {
        toasts.value[index].visible = false
        setTimeout(() => {
            toasts.value.splice(index, 1)
        }, 300)
    }
}

onUnmounted(() => {
    toasts.value.forEach(toast => {
        if (toast.timeoutId) clearTimeout(toast.timeoutId)
    })
})

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
    } catch (error) {
        showToast('error', 'Error', 'Failed to fetch roles')
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
        showToast('error', 'Error', 'Failed to fetch permissions')
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
            showToast('success', 'Success', editId.value ? 'Role updated.' : 'Role created.')
            closeModal()
            fetchRoles()
        } else {
            showToast('error', 'Error', data.message || 'Something went wrong.')
        }
    } catch (err) {
        const msg = err.response?.data?.message || 'Failed to save role'
        showToast('error', 'Error', msg)
    }
}

const confirmDelete = async () => {
    try {
        const { data } = await axios.delete(route('company.roles.delete', { id: deleteId.value }))
        if (data.status === true) {
            showToast('success', 'Deleted', 'Role deleted successfully.')
            closeDeleteModal()
            fetchRoles()
        } else {
            showToast('error', 'Error', 'Could not delete role.')
        }
    } catch (err) {
        showToast('error', 'Error', 'Failed to delete role.')
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
