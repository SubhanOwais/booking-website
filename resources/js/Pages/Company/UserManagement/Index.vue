<template>
    <CompanyLayout>
        <!-- Page Header -->
        <div class="">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-800">User Management</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Manage your company's users, roles and permissions</p>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="fetchUsers"
                        class="flex items-center gap-2 px-3 py-2 text-sm text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                        <i class="bi bi-arrow-clockwise" :class="{ 'animate-spin': loading }"></i>
                        Refresh
                    </button>
                    <button @click="openCreateModal"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary/90 transition-colors shadow-sm">
                        <i class="bi bi-person-plus"></i>
                        Add User
                    </button>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="py-6">
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <!-- Search & Filter Bar -->
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between gap-3">
                    <div class="relative flex-1 max-w-sm">
                        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input v-model="searchQuery" type="text" placeholder="Search users..."
                            class="w-full pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary" />
                    </div>
                    <div class="flex items-center gap-2">
                        <select v-model="filterStatus"
                            class="text-sm pr-8 border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-slate-600">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <!-- Loading / Empty / Table -->
                <div v-if="loading" class="p-8">
                    <div v-for="i in 4" :key="i" class="flex items-center gap-4 mb-4">
                        <div class="w-10 h-10 rounded-full bg-slate-200 animate-pulse"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3 bg-slate-200 rounded animate-pulse w-1/4"></div>
                            <div class="h-3 bg-slate-200 rounded animate-pulse w-1/3"></div>
                        </div>
                        <div class="h-6 w-16 bg-slate-200 rounded-full animate-pulse"></div>
                        <div class="h-6 w-16 bg-slate-200 rounded-full animate-pulse"></div>
                    </div>
                </div>
                <div v-else-if="filteredUsers.length === 0" class="flex flex-col items-center justify-center py-16 px-4">
                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                        <i class="bi bi-people text-2xl text-slate-400"></i>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-700">No users found</h3>
                    <p class="text-sm text-slate-400 mt-1">
                        {{ searchQuery ? 'Try adjusting your search.' : 'Get started by adding a new user.' }}
                    </p>
                    <button v-if="!searchQuery" @click="openCreateModal"
                        class="mt-4 px-4 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary/90 transition-colors">
                        Add First User
                    </button>
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide w-12">#</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">User</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Contact</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Roles</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                                <th class="text-center px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(user, index) in filteredUsers" :key="user.id" class="hover:bg-slate-50/70 transition-colors group">
                                <td class="px-4 py-3 text-slate-400 text-xs">{{ index + 1 }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="relative flex-shrink-0">
                                            <img v-if="user.profile_picture"
                                                :src="'/storage/' + user.profile_picture"
                                                class="w-9 h-9 rounded-full object-cover border-2 border-white shadow-sm"
                                                :alt="user.name" />
                                            <div v-else
                                                class="w-9 h-9 rounded-full bg-gradient-to-br from-primary/20 to-primary/40 flex items-center justify-center border-2 border-white shadow-sm">
                                                <span class="text-xs font-bold text-primary">{{ user.name?.charAt(0)?.toUpperCase() }}</span>
                                            </div>
                                            <span v-if="user.is_active"
                                                class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-green-400 border-2 border-white rounded-full"></span>
                                        </div>
                                        <div>
                                            <p class="font-medium text-slate-800 text-sm leading-tight">{{ user.name }}</p>
                                            <p class="text-xs text-slate-400 leading-tight mt-0.5">{{ user.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5 text-slate-500">
                                        <i class="bi bi-telephone text-xs"></i>
                                        <span class="text-sm">{{ user.phone_number || '—' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        <span v-for="(role, i) in user.roles" :key="i"
                                            class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium"
                                            :class="role.name.trim().toLowerCase() === 'company owner'
                                                ? 'bg-purple-50 text-purple-700 border border-purple-200'
                                                : 'bg-blue-50 text-blue-700 border border-blue-200'">
                                            {{ role.name }}
                                        </span>
                                        <span v-if="user.roles.length === 0"
                                            class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-red-50 text-red-600 border border-red-200">
                                            No Role
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold"
                                        :class="user.is_active ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-600'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="user.is_active ? 'bg-green-500' : 'bg-red-400'"></span>
                                        {{ user.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Edit button hidden for company owner -->
                                        <button
                                            v-if="!isCompanyOwner(user)" @click="openEditModal(user)"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg text-blue-600 hover:bg-blue-50 border border-transparent hover:border-blue-200 transition-all"
                                            title="Edit">
                                            <i class="bi bi-pencil text-sm"></i>
                                        </button>
                                        <!-- Delete button hidden for company owner -->
                                        <button
                                            v-if="!isCompanyOwner(user)" @click="openDeleteModal(user.id)"
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
                <div v-if="filteredUsers.length > 0" class="px-4 py-3 border-t border-slate-100 flex items-center justify-between">
                    <p class="text-xs text-slate-400">Showing {{ filteredUsers.length }} of {{ users.length }} users</p>
                </div>
            </div>
        </div>

        <!-- Create / Edit Modal -->
        <Teleport to="body">
            <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeModal"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col z-10">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                        <div>
                            <h2 class="text-base font-bold text-slate-800">{{ editID ? 'Edit User' : 'Create New User' }}</h2>
                            <p class="text-xs text-slate-400 mt-0.5">{{ editID ? 'Update user information and roles' : 'Fill in the details to add a new user' }}</p>
                        </div>
                        <button @click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-500 transition-colors">
                            <i class="bi bi-x-lg text-sm"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="overflow-y-auto flex-1 px-6 py-4 space-y-4">
                        <!-- Avatar Section -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-2">Profile Picture</label>
                            <div class="border border-slate-200 rounded-xl bg-gray-100 p-3 overflow-y-auto max-h-[239px]" :class="{ 'border-red-400': errors.userImage }">
                                <div class="avatar-grid">
                                    <div v-for="(avatar, idx) in defaultAvatars" :key="idx"
                                        class="avatar-item cursor-pointer rounded-full overflow-hidden transition-all bg-white border border-gray-200"
                                        :class="selectedAvatar === avatar ? 'border-primary shadow-md shadow-primary/20 scale-105' : 'hover:border-slate-200'"
                                        @click="selectAvatar(avatar)">
                                        <img :src="'/images/avatars/' + avatar" class="w-full h-full object-cover" :alt="'Avatar ' + (idx + 1)" />
                                    </div>
                                    <div class="avatar-item relative">
                                        <input type="file" @change="handleImageUpload" accept="image/*" id="custom-avatar-upload" class="hidden" />
                                        <label for="custom-avatar-upload"
                                            class="w-full h-full rounded-full flex items-center justify-center cursor-pointer border-2 overflow-hidden transition-all"
                                            :class="imagePreview ? 'border-primary' : 'border-dashed border-slate-300 hover:border-primary bg-slate-50'">
                                            <img v-if="imagePreview" :src="imagePreview" class="w-full h-full object-cover" />
                                            <i v-else class="bi bi-plus-lg text-slate-400 text-lg"></i>
                                        </label>
                                        <button v-if="imagePreview" @click.prevent="removeImage"
                                            class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center z-10 hover:bg-red-600 transition-colors">
                                            <i class="bi bi-x text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <p v-if="errors.userImage" class="text-xs text-red-500 mt-1">{{ errors.userImage }}</p>
                        </div>

                        <!-- Form Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Full Name <span class="text-red-400">*</span></label>
                                <input type="text" v-model="form.name" @input="clearError('name')" placeholder="Enter full name"
                                    class="w-full px-3 py-2 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                                    :class="errors.name ? 'border-red-400 bg-red-50' : 'border-slate-200'" />
                                <p v-if="errors.name" class="text-xs text-red-500 mt-1">{{ errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Email Address <span class="text-red-400">*</span></label>
                                <input type="email" v-model="form.email" @input="clearError('email')" placeholder="user@company.com"
                                    class="w-full px-3 py-2 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                                    :class="errors.email ? 'border-red-400 bg-red-50' : 'border-slate-200'" />
                                <p v-if="errors.email" class="text-xs text-red-500 mt-1">{{ errors.email }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Phone Number <span class="text-red-400">*</span></label>
                                <input type="tel" v-model="form.phone_number" @input="clearError('phone_number')" placeholder="+92 300 0000000"
                                    class="w-full px-3 py-2 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                                    :class="errors.phone_number ? 'border-red-400 bg-red-50' : 'border-slate-200'" />
                                <p v-if="errors.phone_number" class="text-xs text-red-500 mt-1">{{ errors.phone_number }}</p>
                            </div>

                            <!-- Password Field - Visible ONLY to Company Owner when editing -->
                            <div v-if="!editID || (editID && isLoggedInUserOwner)">
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">
                                    {{ editID ? 'Change Password' : 'Password' }}
                                    <span v-if="!editID" class="text-red-400">*</span>
                                    <span v-else class="text-slate-400 font-normal">(leave blank to keep)</span>
                                </label>
                                <div class="relative">
                                    <input :type="showPassword ? 'text' : 'password'" v-model="form.password" @input="clearError('password')" placeholder="••••••••"
                                        class="w-full px-3 py-2 pr-10 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                                        :class="errors.password ? 'border-red-400 bg-red-50' : 'border-slate-200'" />
                                    <button type="button" @click="showPassword = !showPassword"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                        <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'" class="text-sm"></i>
                                    </button>
                                </div>
                                <p v-if="errors.password" class="text-xs text-red-500 mt-1">{{ errors.password }}</p>
                            </div>

                            <!-- Custom Multi-Select for Roles -->
                            <div class="relative" ref="roleDropdownRef">
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Role <span class="text-red-400">*</span></label>
                                <div class="relative">
                                    <button type="button" @click="toggleRoleDropdown"
                                        class="w-full px-3 py-2 text-left border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors flex justify-between items-center"
                                        :class="errors.role ? 'border-red-400 bg-red-50' : 'border-slate-200'">
                                        <span class="text-sm text-slate-700">{{ selectedRolesText || 'Select role(s)' }}</span>
                                        <i class="bi bi-chevron-down text-slate-400 text-sm"></i>
                                    </button>
                                    <div v-if="roleDropdownOpen" class="absolute z-50 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                        <div v-for="role in roleList" :key="role.value" class="px-3 py-2 hover:bg-slate-50 flex items-center gap-2">
                                            <input type="checkbox" :value="role.value" v-model="form.role" :id="'role-' + role.value"
                                                class="w-4 h-4 text-primary rounded border-slate-300 focus:ring-primary" />
                                            <label :for="'role-' + role.value" class="text-sm text-slate-700 cursor-pointer flex-1">{{ role.label }}</label>
                                        </div>
                                    </div>
                                </div>
                                <p v-if="errors.role" class="text-xs text-red-500 mt-1">{{ errors.role }}</p>
                            </div>

                            <!-- Status Toggle -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Account Status</label>
                                <div class="flex items-center justify-between px-3 py-2 border border-slate-200 rounded-lg">
                                    <span class="text-sm text-slate-600">{{ form.is_active ? 'Active' : 'Inactive' }}</span>
                                    <button type="button" @click="form.is_active = !form.is_active"
                                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none"
                                        :class="form.is_active ? 'bg-primary' : 'bg-slate-200'">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform"
                                            :class="form.is_active ? 'translate-x-6' : 'translate-x-1'"></span>
                                    </button>
                                </div>
                            </div>

                            <!-- Address (full width) -->
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Address</label>
                                <input type="text" v-model="form.address" placeholder="Enter address"
                                    class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors" />
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50 rounded-b-2xl">
                        <button @click="closeModal" class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">Cancel</button>
                        <button @click="submitForm" :disabled="submitting"
                            class="px-5 py-2 text-sm font-medium text-white bg-primary rounded-lg hover:bg-primary/90 transition-colors disabled:opacity-60 disabled:cursor-not-allowed flex items-center gap-2 shadow-sm">
                            <svg v-if="submitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            {{ submitting ? 'Saving...' : (editID ? 'Update User' : 'Create User') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeDeleteModal"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md z-10 overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-exclamation-triangle text-red-600"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-800">Delete User</h3>
                                <p class="text-sm text-slate-500 mt-1 leading-relaxed">Are you sure you want to delete this user? This action is permanent and cannot be undone.</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3 px-6 py-4 bg-slate-50 border-t border-slate-100">
                        <button @click="closeDeleteModal" class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">Cancel</button>
                        <button @click="confirmDelete" :disabled="deleting"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-60 flex items-center gap-2">
                            <svg v-if="deleting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            {{ deleting ? 'Deleting...' : 'Delete User' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Toast Container -->
        <div class="fixed top-4 right-4 z-[100] space-y-2">
            <div v-for="toast in toasts" :key="toast.id" :class="[
                'flex items-center p-4 rounded-lg shadow-lg border-l-4 transition-all duration-300 transform',
                toast.type === 'success' ? 'bg-green-50 border-green-500 text-green-800' : '',
                toast.type === 'error'   ? 'bg-red-50 border-red-500 text-red-800'       : '',
                toast.type === 'info'    ? 'bg-blue-50 border-blue-500 text-blue-800'    : '',
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
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 20 20">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </CompanyLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import axios from 'axios'
import { usePage } from '@inertiajs/vue3'
import CompanyLayout from "@/Layouts/CompanyLayout.vue"

// ── Get logged-in user from Inertia shared props ─────────────────────────────
const page = usePage()
const authUser = computed(() => page.props.auth.user)

// Check if logged-in user is Company Owner
const isLoggedInUserOwner = computed(() => {
    return authUser.value?.roles?.some(role => role.name.toLowerCase() === 'company owner') ?? false
})

// ── Props from Inertia ────────────────────────────────────────────────────────
const props = defineProps({
    users: {
        type: Array,
        default: () => []
    }
})

// ── State ─────────────────────────────────────────────────────────────────────
const loading    = ref(false)
const submitting = ref(false)
const deleting   = ref(false)
const users      = ref(Array.isArray(props.users) ? props.users : [])  // Ensure array
const roleList   = ref([])
const errors     = ref({})

// Filter / search
const searchQuery  = ref('')
const filterStatus = ref('')

// Modals
const isModalOpen     = ref(false)
const showDeleteModal = ref(false)
const deleteTargetId  = ref(null)

// Form state
const editID         = ref(null)
const showPassword   = ref(false)
const selectedAvatar = ref(null)
const imagePreview   = ref('')
const userImage      = ref(null)

// Role dropdown
const roleDropdownOpen = ref(false)
const roleDropdownRef = ref(null)

// Click outside handler
const handleClickOutside = (event) => {
    if (roleDropdownOpen.value && roleDropdownRef.value && !roleDropdownRef.value.contains(event.target)) {
        roleDropdownOpen.value = false
    }
}

// Watch for dropdown open state
watch(roleDropdownOpen, (isOpen) => {
    if (isOpen) {
        document.addEventListener('click', handleClickOutside)
    } else {
        document.removeEventListener('click', handleClickOutside)
    }
})

// Clean up on unmount
onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})

// Toggle function
const toggleRoleDropdown = (event) => {
    event.stopPropagation()
    roleDropdownOpen.value = !roleDropdownOpen.value
}

const form = ref({
    name:         '',
    email:        '',
    password:     '',
    phone_number: '',
    is_active:    true,
    role:         [],
    address:      '',
})

// Toast system
const toasts = ref([])
let toastId = 0

// ── Avatars ───────────────────────────────────────────────────────────────────
const defaultAvatars = Array.from({ length: 50 }, (_, i) => `avatar${i + 1}.png`)

// ── Computed ──────────────────────────────────────────────────────────────────
const filteredUsers = computed(() => {
    // Guard against non-array
    if (!Array.isArray(users.value)) return []
    return users.value.filter(u => {
        const matchesSearch = !searchQuery.value ||
            u.name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            u.email?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            u.phone_number?.includes(searchQuery.value)
        const matchesStatus = !filterStatus.value ||
            (filterStatus.value === 'active' && u.is_active) ||
            (filterStatus.value === 'inactive' && !u.is_active)
        return matchesSearch && matchesStatus
    })
})

const selectedRolesText = computed(() => {
    if (!form.value.role.length) return ''
    return roleList.value
        .filter(r => form.value.role.includes(r.value))
        .map(r => r.label)
        .join(', ')
})

const isCompanyOwner = (user) => {
    return user.roles?.some(role => role.name.toLowerCase() === 'company owner') ?? false
}

// ── Toast helpers ─────────────────────────────────────────────────────────────
const showToast = (type, summary, detail, duration = 5000) => {
    const id = ++toastId
    toasts.value.push({ id, type, summary, detail, duration, visible: true })
    setTimeout(() => removeToast(id), duration)
}

const removeToast = (id) => {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index !== -1) {
        toasts.value[index].visible = false
        setTimeout(() => toasts.value.splice(index, 1), 300)
    }
}

// ── Lifecycle ─────────────────────────────────────────────────────────────────
onMounted(() => {
    fetchRoles()
})

// ── API Calls ─────────────────────────────────────────────────────────────────
const fetchUsers = async () => {
    try {
        loading.value = true
        const { data } = await axios.get(route('company.users.get'))
        // Ensure data is an array
        users.value = Array.isArray(data) ? data : []
    } catch (error) {
        console.error(error)
        showToast('error', 'Error', 'Failed to fetch users')
        users.value = []
    } finally {
        loading.value = false
    }
}

const fetchRoles = async () => {
    try {
        const { data } = await axios.get(route('company.users.roles'))
        roleList.value = Array.isArray(data) ? data.map(r => ({ label: r.name, value: r.name })) : []
    } catch (error) {
        console.error(error)
        showToast('error', 'Error', 'Failed to fetch roles')
        roleList.value = []
    }
}

// ── Avatar / Image ────────────────────────────────────────────────────────────
const selectAvatar = async (avatar) => {
    try {
        const res  = await fetch('/images/avatars/' + avatar)
        const blob = await res.blob()
        userImage.value    = new File([blob], avatar, { type: blob.type })
        imagePreview.value = ''
        selectedAvatar.value = avatar
    } catch (e) { console.error(e) }
}

const handleImageUpload = (e) => {
    const file = e.target.files[0]
    if (!file) return
    userImage.value      = file
    selectedAvatar.value = null
    const reader = new FileReader()
    reader.onloadend = () => { imagePreview.value = reader.result }
    reader.readAsDataURL(file)
}

const removeImage = () => {
    userImage.value    = null
    imagePreview.value = ''
}

// ── Validation ────────────────────────────────────────────────────────────────
const clearError = (field) => { delete errors.value[field] }

const validate = () => {
    errors.value = {}
    if (!editID.value && !userImage.value && !selectedAvatar.value)
        errors.value.userImage = 'Profile picture is required.'
    if (!form.value.name)         errors.value.name         = 'Name is required.'
    if (!form.value.email)        errors.value.email        = 'Email is required.'
    if (!editID.value && !form.value.password)
                                  errors.value.password     = 'Password is required.'
    if (!form.value.phone_number) errors.value.phone_number = 'Phone number is required.'
    if (!form.value.role?.length) errors.value.role         = 'Role is required.'
    return Object.keys(errors.value).length === 0
}

// ── Submit ────────────────────────────────────────────────────────────────────
const submitForm = async () => {
    if (!validate()) return
    try {
        submitting.value = true
        const fd = new FormData()
        fd.append('editID', editID.value ?? '')
        fd.append('name', form.value.name)
        fd.append('email', form.value.email)
        fd.append('phone_number', form.value.phone_number)
        fd.append('is_active', form.value.is_active ? 1 : 0)
        fd.append('address', form.value.address ?? '')
        // Send roles as array
        form.value.role.forEach(role => fd.append('role[]', role))
        if (form.value.password) fd.append('password', form.value.password)

        if (userImage.value) {
            fd.append('profile_picture', userImage.value)
        } else if (selectedAvatar.value) {
            fd.append('profile_picture', selectedAvatar.value)
        }

        const url = editID.value
            ? route('company.users.update')
            : route('company.users.store')

        await axios.post(url, fd, { headers: { 'Content-Type': 'multipart/form-data' } })

        showToast('success', editID.value ? 'User Updated' : 'User Created', 'User saved successfully.')
        await fetchUsers()
        closeModal()
    } catch (err) {
        if (err.response?.data?.errors) {
            // Show validation errors as toasts
            Object.values(err.response.data.errors).flat().forEach(msg => {
                showToast('error', 'Validation Error', msg)
            })
        } else {
            showToast('error', 'Error', err.response?.data?.message || 'Something went wrong.')
        }
    } finally {
        submitting.value = false
    }
}

// ── Modal helpers ─────────────────────────────────────────────────────────────
const resetForm = () => {
    editID.value         = null
    selectedAvatar.value = null
    userImage.value      = null
    imagePreview.value   = ''
    showPassword.value   = false
    errors.value         = {}
    form.value = {
        name: '', email: '', password: '', phone_number: '',
        is_active: true, role: [], address: '',
    }
}

const openCreateModal = () => {
    resetForm()
    isModalOpen.value = true
}

const openEditModal = (user) => {
    resetForm()
    editID.value = user.id
    form.value = {
        name:         user.name,
        email:        user.email,
        password:     '',
        phone_number: user.phone_number,
        is_active:    Boolean(user.is_active),
        role:         user.roles.map(r => r.name),
        address:      user.address ?? '',
    }
    isModalOpen.value = true
}

const closeModal = () => {
    isModalOpen.value = false
    roleDropdownOpen.value = false
    resetForm()
}

// ── Delete ────────────────────────────────────────────────────────────────────
const openDeleteModal = (id) => {
    deleteTargetId.value = id
    showDeleteModal.value = true
}
const closeDeleteModal = () => {
    showDeleteModal.value = false
    deleteTargetId.value = null
}

const confirmDelete = async () => {
    try {
        deleting.value = true
        await axios.delete(route('company.users.destroy', deleteTargetId.value))
        showToast('success', 'Deleted', 'User deleted successfully.')
        await fetchUsers()
        closeDeleteModal()
    } catch (err) {
        showToast('error', 'Error', err.response?.data?.message || 'Failed to delete user.')
    } finally {
        deleting.value = false
    }
}
</script>

<style scoped>
.avatar-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 6px;
    padding: 4px;
}
.avatar-item { aspect-ratio: 1; }
@media (max-width: 480px) {
    .avatar-grid { grid-template-columns: repeat(5, 1fr); }
}
</style>
