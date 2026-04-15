<script setup>
import { ref, reactive } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';

// Props
defineProps({
    websites: Array,
});

// State
const showCreateModal = ref(false);
const editForm = reactive({
    id: null,
    name: '',
    logo: null,
    description: '',
    helpline_number: '',
    email: '',
    address: '',
    facebook: '',
    twitter: '',
    instagram: '',
    linkedin: '',
    youtube: '',
    tiktok: '',
    meta_title: '',
    meta_description: '',
    meta_keywords: '',
});
const deleteWebsite = ref(null);
const showDetails = ref({});

// Forms
const createForm = reactive({
    name: '',
    logo: null,
    description: '',
    helpline_number: '',
    email: '',
    address: '',
    facebook: '',
    twitter: '',
    instagram: '',
    linkedin: '',
    youtube: '',
    tiktok: '',
    meta_title: '',
    meta_description: '',
    meta_keywords: '',
});

// Functions
const openEdit = (website) => {
    editForm.id = website.id;
    Object.keys(editForm).forEach(key => {
        if (key !== 'logo' && key !== 'id') {
            editForm[key] = website[key] || '';
        } else if (key === 'logo') {
            editForm[key] = website[key] ? website[key] : null;
        }
    });
};

const openDelete = (website) => {
    deleteWebsite.value = website;
};

const toggleDetails = (id) => {
    showDetails.value[id] = !showDetails.value[id];
};

const submitCreate = () => {
    const data = new FormData();
    Object.keys(createForm).forEach(key => {
        if (createForm[key] !== null && createForm[key] !== '') {
            data.append(key, createForm[key]);
        }
    });

    router.post(route('admin.websites.store'), data, {
        onSuccess: () => {
            showCreateModal.value = false;
            resetCreateForm();
        },
    });
};

const submitEdit = () => {
    const data = new FormData();
    Object.keys(editForm).forEach(key => {
        if (key === 'logo') {
            if (editForm[key] && typeof editForm[key] === 'object') {
                data.append(key, editForm[key]);
            }
        } else if (editForm[key] !== null && editForm[key] !== '') {
            data.append(key, editForm[key]);
        }
    });
    data.append('_method', 'PUT');

    router.post(route('admin.websites.update', editForm.id), data, {
        onSuccess: () => {
            editForm.id = null;
            resetEditForm();
        },
    });
};

const submitDelete = (id) => {
    router.delete(route('admin.websites.destroy', id), {
        onSuccess: () => { deleteWebsite.value = null; }
    });
};

const resetCreateForm = () => {
    Object.keys(createForm).forEach(key => {
        if (key === 'logo') {
            createForm[key] = null;
        } else {
            createForm[key] = '';
        }
    });
};

const resetEditForm = () => {
    Object.keys(editForm).forEach(key => {
        if (key === 'logo') {
            editForm[key] = null;
        } else {
            editForm[key] = '';
        }
    });
    editForm.id = null;
};

const handleFileChange = (event, formType, field) => {
    const file = event.target.files[0];
    if (formType === 'create') {
        createForm[field] = file;
    } else {
        editForm[field] = file;
    }
};

const removeFile = (formType, field) => {
    if (formType === 'create') {
        createForm[field] = null;
    } else {
        editForm[field] = null;
    }
};

</script>

<template>
    <AdminLayout>

        <Head title="Website Settings" />

        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Website Management</h1>
                    <p class="mt-1 text-sm text-gray-600">Manage multiple websites and their settings</p>
                </div>
                <button @click="showCreateModal = true"
                    class="inline-flex items-center gap-1 px-3 py-1 text-sm font-medium text-white transition-colors rounded-lg bg-primary">
                    <i class="text-xl bi bi-plus"></i>
                    Add New
                </button>
            </div>
        </div>

        <!-- Websites Grid -->
        <div v-if="websites && websites.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <div v-for="site in websites" :key="site.id"
                class="overflow-hidden transition-shadow bg-white border border-gray-200 shadow-sm rounded-xl hover:shadow-md">
                <!-- Card Header -->
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="relative flex-shrink-0">
                                <div v-if="site.logo"
                                    class="w-16 h-16 overflow-hidden border border-gray-200 rounded-lg">
                                    <img :src="`/storage/${site.logo}`" :alt="site.name"
                                        class="object-contain w-full h-full" />
                                </div>
                                <div v-else
                                    class="flex items-center justify-center p-3 rounded-lg bg-secondary/20 text-primary">
                                    <i class="text-2xl bi bi-globe-americas"></i>
                                </div>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900">{{ site.name }}</h3>
                                <p class="mt-1 text-sm text-gray-500 line-clamp-2">
                                    {{ site.description || 'No description available' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Info -->
                    <div class="mt-4 space-y-2">
                        <div v-if="site.helpline_number" class="flex items-center gap-2 text-sm">
                            <i class="text-md bi bi-telephone-inbound-fill text-primary"></i>
                            <span class="text-gray-600">{{ site.helpline_number }}</span>
                        </div>
                        <div v-if="site.email" class="flex items-center gap-2 text-sm">
                            <i class="bi bi-envelope-at-fill text-md text-primary"></i>
                            <span class="text-gray-600">{{ site.email }}</span>
                        </div>
                        <div v-if="site.address" class="flex items-start gap-2 text-sm">
                            <i class="bi bi-geo-alt-fill text-md text-primary"></i>
                            <span class="text-gray-600 line-clamp-2">{{ site.address }}</span>
                        </div>
                    </div>

                    <!-- Social Media Icons -->
                    <div v-if="site.facebook || site.twitter || site.instagram || site.linkedin || site.youtube || site.tiktok"
                        class="mt-4">
                        <p class="mb-2 text-xs font-medium text-gray-500">SOCIAL MEDIA</p>
                        <div class="flex gap-2">
                            <a v-if="site.facebook" :href="site.facebook" target="_blank"
                                class="p-2 text-blue-600 transition-colors rounded-lg bg-blue-50 hover:bg-blue-100">
                                <span class="text-sm font-medium">FB</span>
                            </a>
                            <a v-if="site.twitter" :href="site.twitter" target="_blank"
                                class="p-2 transition-colors rounded-lg text-sky-500 bg-sky-50 hover:bg-sky-100">
                                <span class="text-sm font-medium">TW</span>
                            </a>
                            <a v-if="site.instagram" :href="site.instagram" target="_blank"
                                class="p-2 text-pink-600 transition-colors rounded-lg bg-pink-50 hover:bg-pink-100">
                                <span class="text-sm font-medium">IG</span>
                            </a>
                            <a v-if="site.linkedin" :href="site.linkedin" target="_blank"
                                class="p-2 text-blue-700 transition-colors rounded-lg bg-blue-50 hover:bg-blue-100">
                                <span class="text-sm font-medium">IN</span>
                            </a>
                            <a v-if="site.youtube" :href="site.youtube" target="_blank"
                                class="p-2 text-red-600 transition-colors rounded-lg bg-red-50 hover:bg-red-100">
                                <span class="text-sm font-medium">YT</span>
                            </a>
                            <a v-if="site.tiktok" :href="site.tiktok" target="_blank"
                                class="p-2 text-gray-800 transition-colors bg-gray-100 rounded-lg hover:bg-gray-200">
                                <span class="text-sm font-medium">TT</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Card Footer -->
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <button @click="toggleDetails(site.id)"
                            class="text-sm font-medium text-blue-600 hover:text-blue-700">
                            {{ showDetails[site.id] ? 'Hide Details' : 'View Details' }}
                        </button>
                        <div class="flex gap-2">
                            <button @click="openEdit(site)" class="p-1 px-3 transition-colors rounded-lg bg-primary/20"
                                title="Edit">
                                <i class="text-lg bi bi-pencil-fill text-primary"></i>
                            </button>
                            <button @click="openDelete(site)"
                                class="p-1 px-3 transition-colors rounded-lg bg-red-500/40" title="Delete">
                                <i class="text-lg text-red-700 bi bi-trash3-fill"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Expanded Details -->
                    <div v-if="showDetails[site.id]" class="pt-4 mt-4 border-t border-gray-200">
                        <div class="space-y-3">
                            <div v-if="site.meta_title">
                                <p class="text-xs font-medium text-gray-500">Meta Title</p>
                                <p class="text-sm text-gray-700">{{ site.meta_title }}</p>
                            </div>
                            <div v-if="site.meta_description">
                                <p class="text-xs font-medium text-gray-500">Meta Description</p>
                                <p class="text-sm text-gray-700">{{ site.meta_description }}</p>
                            </div>
                            <div v-if="site.meta_keywords">
                                <p class="text-xs font-medium text-gray-500">Meta Keywords</p>
                                <p class="text-sm text-gray-700">{{ site.meta_keywords }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center">
            <div class="max-w-md mx-auto">
                <div class="inline-flex items-center justify-center mb-4 text-5xl bg-gray-100 rounded-full">
                    <i class="bi bi-globe"></i>
                </div>
                <h3 class="mb-2 text-lg font-semibold text-gray-900">No websites yet</h3>
                <p class="mb-6 text-gray-500">Get started by creating your first website configuration.</p>
                <button @click="showCreateModal = true"
                    class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-white transition-colors rounded-lg bg-primary">
                    <i class="text-xl bi bi-plus"></i>
                    Create First Website
                </button>
            </div>
        </div>

        <!-- Create Modal -->
        <div v-if="showCreateModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50">
            <div class="w-full max-w-4xl bg-white shadow-2xl rounded-2xl">
                <div
                    class="flex items-center justify-between px-6 py-4 mb-0 bg-gray-100 border-b border-gray-300 rounded-t-2xl">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Create New Website</h2>
                        <p class="text-sm text-gray-600">Configure your website settings</p>
                    </div>
                    <button @click="showCreateModal = false" class="p-2 text-gray-400 hover:text-gray-500">
                        <i class="text-3xl bi bi-x-circle-fill text-primary"></i>
                    </button>
                </div>

                <form @submit.prevent="submitCreate">
                    <div class="grid gap-6 p-6 md:grid-cols-2 max-h-[70vh] overflow-y-auto">
                        <!-- Left Column -->
                        <div class="space-y-6">
                            <!-- Basic Information -->
                            <div class="p-5 bg-gray-50 rounded-xl">
                                <h3 class="mb-4 font-semibold text-gray-900">Basic Information</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-700">
                                            Website Name *
                                        </label>
                                        <input type="text" v-model="createForm.name" placeholder="Enter website name"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            required />
                                    </div>

                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-700">
                                            Description
                                        </label>
                                        <textarea v-model="createForm.description"
                                            placeholder="Enter website description" rows="3"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Media Uploads -->
                            <div class="p-5 bg-gray-50 rounded-xl">
                                <h3 class="mb-4 font-semibold text-gray-900">Media</h3>
                                <div class="space-y-4">
                                    <!-- Logo Upload -->
                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-700">
                                            Logo
                                        </label>
                                        <div v-if="!createForm.logo" class="flex items-center justify-center w-full">
                                            <label
                                                class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                                    <i class="text-2xl text-gray-600 bi bi-image"></i>
                                                    <p class="mb-2 text-sm text-gray-500">Click to upload logo</p>
                                                    <p class="text-xs text-gray-500">PNG, JPG, WEBP up to 2MB</p>
                                                </div>
                                                <input type="file" @change="handleFileChange($event, 'create', 'logo')"
                                                    class="hidden" accept="image/*" />
                                            </label>
                                        </div>
                                        <div v-else
                                            class="flex items-center gap-4 p-4 border border-gray-200 rounded-lg">
                                            <div class="w-16 h-16 overflow-hidden border border-gray-200 rounded-lg">
                                                <img :src="URL.createObjectURL(createForm.logo)"
                                                    class="object-contain w-full h-full" />
                                            </div>
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-gray-900">{{ createForm.logo.name }}
                                                </p>
                                                <p class="text-sm text-gray-500">{{ (createForm.logo.size /
                                                    1024).toFixed(2) }} KB</p>
                                            </div>
                                            <button type="button" @click="removeFile('create', 'logo')"
                                                class="p-2 text-gray-400 hover:text-red-500">
                                                <span v-html="renderIcon('xMark')"></span>
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Contact Information -->
                            <div class="p-5 bg-gray-50 rounded-xl">
                                <h3 class="mb-4 font-semibold text-gray-900">Contact Information</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-700">
                                            Helpline Number
                                        </label>
                                        <input type="text" v-model="createForm.helpline_number"
                                            placeholder="Enter helpline number"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                    </div>

                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-700">
                                            Email Address
                                        </label>
                                        <input type="email" v-model="createForm.email" placeholder="Enter email address"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                    </div>

                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-700">
                                            Address
                                        </label>
                                        <textarea v-model="createForm.address" placeholder="Enter physical address"
                                            rows="3"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6">
                            <!-- Social Media -->
                            <div class="p-5 bg-gray-50 rounded-xl">
                                <h3 class="mb-4 font-semibold text-gray-900">Social Media</h3>
                                <div class="space-y-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center justify-center w-10 h-10 bg-blue-100 rounded-lg">
                                            <span class="text-sm font-bold text-blue-600">FB</span>
                                        </div>
                                        <input type="url" v-model="createForm.facebook"
                                            placeholder="https://facebook.com/username"
                                            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-sky-100">
                                            <span class="text-sm font-bold text-sky-600">TW</span>
                                        </div>
                                        <input type="url" v-model="createForm.twitter"
                                            placeholder="https://twitter.com/username"
                                            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center justify-center w-10 h-10 bg-pink-100 rounded-lg">
                                            <span class="text-sm font-bold text-pink-600">IG</span>
                                        </div>
                                        <input type="url" v-model="createForm.instagram"
                                            placeholder="https://instagram.com/username"
                                            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center justify-center w-10 h-10 bg-blue-100 rounded-lg">
                                            <span class="text-sm font-bold text-blue-700">IN</span>
                                        </div>
                                        <input type="url" v-model="createForm.linkedin"
                                            placeholder="https://linkedin.com/company/username"
                                            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center justify-center w-10 h-10 bg-red-100 rounded-lg">
                                            <span class="text-sm font-bold text-red-600">YT</span>
                                        </div>
                                        <input type="url" v-model="createForm.youtube"
                                            placeholder="https://youtube.com/username"
                                            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center justify-center w-10 h-10 bg-gray-100 rounded-lg">
                                            <span class="text-sm font-bold text-gray-800">TT</span>
                                        </div>
                                        <input type="url" v-model="createForm.tiktok"
                                            placeholder="https://tiktok.com/@username"
                                            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                    </div>
                                </div>
                            </div>

                            <!-- SEO Settings -->
                            <div class="p-5 bg-gray-50 rounded-xl">
                                <h3 class="mb-4 font-semibold text-gray-900">SEO Settings</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-700">
                                            Meta Title
                                        </label>
                                        <input type="text" v-model="createForm.meta_title"
                                            placeholder="Enter meta title for SEO"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                    </div>

                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-700">
                                            Meta Description
                                        </label>
                                        <textarea v-model="createForm.meta_description"
                                            placeholder="Enter meta description for SEO" rows="3"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                                    </div>

                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-700">
                                            Meta Keywords
                                        </label>
                                        <input type="text" v-model="createForm.meta_keywords"
                                            placeholder="keyword1, keyword2, keyword3"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 px-6 py-4 bg-gray-100 border-t border-gray-300 rounded-b-2xl">
                        <button type="button" @click="showCreateModal = false"
                            class="px-4 py-2 font-medium text-gray-700 transition-colors border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 font-medium text-white transition-colors rounded-lg bg-primary">
                            Create Website
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Modal -->
        <div v-if="editForm.id" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50">
            <div class="w-full max-w-4xl bg-white shadow-2xl rounded-2xl">
                <div
                    class="flex items-center justify-between px-6 py-4 mb-0 bg-gray-100 border-b border-gray-300 rounded-t-2xl">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Edit Website</h2>
                        <p class="text-sm text-gray-600">Update website settings</p>
                    </div>
                    <button @click="editForm.id = null" class="p-2 text-gray-400 hover:text-gray-500">
                        <i class="text-3xl bi bi-x-circle-fill text-primary"></i>
                    </button>
                </div>

                <form @submit.prevent="submitEdit">
                    <div class="grid gap-6 p-6 md:grid-cols-2 max-h-[70vh] overflow-y-auto">
                        <!-- Left Column -->
                        <div class="space-y-6">
                            <!-- Basic Information -->
                            <div class="p-5 bg-gray-50 rounded-xl">
                                <h3 class="mb-4 font-semibold text-gray-900">Basic Information</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-700">
                                            Website Name *
                                        </label>
                                        <input type="text" v-model="editForm.name" placeholder="Enter website name"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            required />
                                    </div>

                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-700">
                                            Description
                                        </label>
                                        <textarea v-model="editForm.description" placeholder="Enter website description"
                                            rows="3"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Media Uploads -->
                            <div class="p-5 bg-gray-50 rounded-xl">
                                <h3 class="mb-4 font-semibold text-gray-900">Media</h3>
                                <div class="space-y-4">
                                    <!-- Logo Upload -->
                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-700">
                                            Logo
                                            <span v-if="editForm.logo && typeof editForm.logo === 'string'"
                                                class="ml-2 text-xs text-gray-500">(Current logo will be
                                                replaced)</span>
                                        </label>
                                        <div v-if="!editForm.logo || typeof editForm.logo === 'string'"
                                            class="flex items-center justify-center w-full">
                                            <label
                                                class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                                    <i class="text-2xl text-gray-600 bi bi-image"></i>
                                                    <p class="mb-2 text-sm text-gray-500">Click to upload new logo</p>
                                                    <p class="text-xs text-gray-500">PNG, JPG, WEBP up to 2MB</p>
                                                </div>
                                                <input type="file" @change="handleFileChange($event, 'edit', 'logo')"
                                                    class="hidden" accept="image/*" />
                                            </label>
                                        </div>
                                        <div v-else
                                            class="flex items-center gap-4 p-4 border border-gray-200 rounded-lg">
                                            <div class="w-16 h-16 overflow-hidden border border-gray-200 rounded-lg">
                                                <img :src="URL.createObjectURL(editForm.logo)"
                                                    class="object-contain w-full h-full" />
                                            </div>
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-gray-900">{{ editForm.logo.name }}
                                                </p>
                                                <p class="text-sm text-gray-500">{{ (editForm.logo.size /
                                                    1024).toFixed(2) }} KB</p>
                                            </div>
                                            <button type="button" @click="removeFile('edit', 'logo')"
                                                class="p-2 text-gray-400 hover:text-red-500">
                                                <span v-html="renderIcon('xMark')"></span>
                                            </button>
                                        </div>
                                        <div v-if="editForm.logo && typeof editForm.logo === 'string'"
                                            class="mt-2 text-xs text-gray-500">
                                            Current logo: {{ editForm.logo }}
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Contact Information -->
                            <div class="p-5 bg-gray-50 rounded-xl">
                                <h3 class="mb-4 font-semibold text-gray-900">Contact Information</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-700">
                                            Helpline Number
                                        </label>
                                        <input type="text" v-model="editForm.helpline_number"
                                            placeholder="Enter helpline number"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                    </div>

                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-700">
                                            Email Address
                                        </label>
                                        <input type="email" v-model="editForm.email" placeholder="Enter email address"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                    </div>

                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-700">
                                            Address
                                        </label>
                                        <textarea v-model="editForm.address" placeholder="Enter physical address"
                                            rows="3"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6">
                            <!-- Social Media -->
                            <div class="p-5 bg-gray-50 rounded-xl">
                                <h3 class="mb-4 font-semibold text-gray-900">Social Media</h3>
                                <div class="space-y-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center justify-center w-10 h-10 bg-blue-100 rounded-lg">
                                            <span class="text-sm font-bold text-blue-600">FB</span>
                                        </div>
                                        <input type="url" v-model="editForm.facebook"
                                            placeholder="https://facebook.com/username"
                                            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-sky-100">
                                            <span class="text-sm font-bold text-sky-600">TW</span>
                                        </div>
                                        <input type="url" v-model="editForm.twitter"
                                            placeholder="https://twitter.com/username"
                                            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center justify-center w-10 h-10 bg-pink-100 rounded-lg">
                                            <span class="text-sm font-bold text-pink-600">IG</span>
                                        </div>
                                        <input type="url" v-model="editForm.instagram"
                                            placeholder="https://instagram.com/username"
                                            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center justify-center w-10 h-10 bg-blue-100 rounded-lg">
                                            <span class="text-sm font-bold text-blue-700">IN</span>
                                        </div>
                                        <input type="url" v-model="editForm.linkedin"
                                            placeholder="https://linkedin.com/company/username"
                                            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center justify-center w-10 h-10 bg-red-100 rounded-lg">
                                            <span class="text-sm font-bold text-red-600">YT</span>
                                        </div>
                                        <input type="url" v-model="editForm.youtube"
                                            placeholder="https://youtube.com/username"
                                            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center justify-center w-10 h-10 bg-gray-100 rounded-lg">
                                            <span class="text-sm font-bold text-gray-800">TT</span>
                                        </div>
                                        <input type="url" v-model="editForm.tiktok"
                                            placeholder="https://tiktok.com/@username"
                                            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                    </div>
                                </div>
                            </div>

                            <!-- SEO Settings -->
                            <div class="p-5 bg-gray-50 rounded-xl">
                                <h3 class="mb-4 font-semibold text-gray-900">SEO Settings</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-700">
                                            Meta Title
                                        </label>
                                        <input type="text" v-model="editForm.meta_title"
                                            placeholder="Enter meta title for SEO"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                    </div>

                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-700">
                                            Meta Description
                                        </label>
                                        <textarea v-model="editForm.meta_description"
                                            placeholder="Enter meta description for SEO" rows="3"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                                    </div>

                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-700">
                                            Meta Keywords
                                        </label>
                                        <input type="text" v-model="editForm.meta_keywords"
                                            placeholder="keyword1, keyword2, keyword3"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 px-6 py-4 bg-gray-100 border-t border-gray-300 rounded-b-2xl">
                        <button type="button" @click="editForm.id = null"
                            class="px-3 py-2 font-medium text-gray-700 transition-colors border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 font-medium text-white transition-colors rounded-lg bg-primary">
                            Update Website
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="deleteWebsite"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50">
            <div class="w-full max-w-md p-8 bg-white shadow-2xl rounded-2xl">
                <div class="text-center">
                    <div class="inline-flex items-center justify-center mx-auto mb-4">
                        <i class="text-5xl text-gray-600 bi bi-trash-fill"></i>
                    </div>
                    <h2 class="mb-2 text-2xl font-bold text-gray-900">Delete Website</h2>
                    <p class="mb-6 text-gray-600">
                        Are you sure you want to delete <strong class="font-semibold text-gray-900">{{
                            deleteWebsite.name }}</strong>? This action cannot be undone.
                    </p>
                    <div class="flex justify-center gap-3">
                        <button @click="deleteWebsite = null"
                            class="px-6 py-2 font-medium text-gray-700 transition-colors border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                        <button @click="submitDelete(deleteWebsite.id)"
                            class="px-6 py-2 font-medium text-white transition-colors bg-red-600 rounded-lg hover:bg-red-700">
                            Delete Website
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.max-h-\[90vh\] {
    max-height: 90vh;
}
</style>
