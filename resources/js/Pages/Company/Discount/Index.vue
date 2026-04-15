<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import CompanyLayout from '@/Layouts/CompanyLayout.vue';
import axios from 'axios';

// Filter dropdown state
const showFilterDropdown = ref(false);
const filterDropdownRef = ref(null);

// Close filter dropdown when clicking outside
const handleClickOutside = (event) => {
    if (filterDropdownRef.value && !filterDropdownRef.value.contains(event.target)) {
        showFilterDropdown.value = false;
    }
};

const clearFilters = () => {
    searchQuery.value = '';
    filterStatus.value = 'all';
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

const props = defineProps({
    discounts: Array,
    mainCities: Array,
    availableCompanies: Array
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const selectedDiscount = ref(null);

console.log('Available Companies:', props.availableCompanies);

const mappedCities = ref([]);
const loadingMappedCities = ref(false);

const createForm = useForm({
    name: '',
    discount_percentage: '',
    main_city_id: null,
    mapped_city_ids: [],
    company_ids: [],
    is_active: true,
    start_date: '',
    end_date: ''
});

const editForm = useForm({
    name: '',
    discount_percentage: '',
    main_city_id: null,
    mapped_city_ids: [],
    company_ids: [],
    is_active: true,
    start_date: '',
    end_date: ''
});

const searchQuery = ref('');
const filterStatus = ref('all');

const filteredDiscounts = computed(() => {
    let filtered = props.discounts;

    if (searchQuery.value) {
        filtered = filtered.filter(discount =>
            discount.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            discount.main_city_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            discount.mapped_city_names.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (discount.company_names_display && discount.company_names_display.toLowerCase().includes(searchQuery.value.toLowerCase()))
        );
    }

    if (filterStatus.value !== 'all') {
        if (filterStatus.value === 'deleted') {
            filtered = filtered.filter(d => d.deleted_at);
        } else {
            filtered = filtered.filter(d => !d.deleted_at && d.status === filterStatus.value);
        }
    }

    return filtered;
});

watch(() => createForm.main_city_id, async (newMainCityId) => {
    if (newMainCityId) {
        await fetchMappedCities(newMainCityId);
        createForm.mapped_city_ids = [];
    } else {
        mappedCities.value = [];
    }
});

watch(() => editForm.main_city_id, async (newMainCityId) => {
    if (newMainCityId) {
        await fetchMappedCities(newMainCityId);
    } else {
        mappedCities.value = [];
    }
});

const fetchMappedCities = async (mainCityId) => {
    loadingMappedCities.value = true;
    try {
        const response = await axios.get(`/company/discount/mapped-cities/${mainCityId}`);
        if (response.data.success) {
            mappedCities.value = response.data.mapped_cities;
        } else {
            mappedCities.value = [];
        }
    } catch (error) {
        console.error('Failed to fetch mapped cities:', error);
        mappedCities.value = [];
    } finally {
        loadingMappedCities.value = false;
    }
};

const openCreateModal = () => {
    createForm.reset();
    mappedCities.value = [];
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
    mappedCities.value = [];
};

const submitCreate = () => {
    createForm.post(route('company.discount.store'), {
        onSuccess: () => {
            closeCreateModal();
        }
    });
};

const openEditModal = async (discount) => {
    selectedDiscount.value = discount;

    if (discount.main_city_id) {
        await fetchMappedCities(discount.main_city_id);
    }

    editForm.name = discount.name;
    editForm.discount_percentage = discount.discount_percentage;
    editForm.main_city_id = discount.main_city_id;
    editForm.mapped_city_ids = discount.mapped_city_ids;
    editForm.company_ids = discount.company_ids || [];
    editForm.is_active = discount.is_active;
    editForm.start_date = discount.start_date ? discount.start_date.substring(0, 16) : '';
    editForm.end_date = discount.end_date ? discount.end_date.substring(0, 16) : '';

    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editForm.reset();
    selectedDiscount.value = null;
    mappedCities.value = [];
};

const submitEdit = () => {
    editForm.put(route('company.discount.update', selectedDiscount.value.id), {
        onSuccess: () => {
            closeEditModal();
        }
    });
};

const openDeleteModal = (discount) => {
    selectedDiscount.value = discount;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedDiscount.value = null;
};

const confirmDelete = () => {
    router.delete(route('company.discount.destroy', selectedDiscount.value.id), {
        onSuccess: () => {
            closeDeleteModal();
        }
    });
};

const toggleActive = (id) => {
    router.post(route('company.discount.toggle-active', id));
};

const restoreDiscount = (id) => {
    router.post(route('company.discount.restore', id));
};

const forceDeleteDiscount = (id) => {
    if (confirm('Are you sure you want to permanently delete this discount? This action cannot be undone!')) {
        router.delete(route('company.discount.force-delete', id));
    }
};

const getStatusColor = (status) => {
    const colors = {
        'active': 'bg-green-100 text-green-800',
        'inactive': 'bg-gray-100 text-gray-800',
        'upcoming': 'bg-blue-100 text-blue-800',
        'expired': 'bg-red-100 text-red-800'
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>

    <Head title="Discount Cities" />

    <CompanyLayout>
        <div>
            <!-- Header with Filter & Add buttons -->
            <div class="mb-6 md:flex md:items-center md:justify-between">
                <div class="flex-1 min-w-0">
                    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                        🏷️ Discount Cities Management
                    </h2>
                </div>
                <div class="flex mt-4 md:mt-0 md:ml-4 gap-3">
                    <!-- Filter Button -->
                    <div class="relative" ref="filterDropdownRef">
                        <button @click="showFilterDropdown = !showFilterDropdown"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Filter
                            <span v-if="searchQuery || filterStatus !== 'all'"
                                class="ml-1 px-1.5 py-0.5 text-xs font-bold text-white bg-indigo-500 rounded-full">
                                {{ (searchQuery ? 1 : 0) + (filterStatus !== 'all' ? 1 : 0) }}
                            </span>
                        </button>

                        <!-- Filter Dropdown -->
                        <div v-if="showFilterDropdown"
                            class="absolute right-0 z-20 w-80 mt-2 bg-white rounded-xl shadow-xl border-2 border-gray-200 p-4">
                            <div class="space-y-4">
                                <!-- Search -->
                                <div>
                                    <label
                                        class="block mb-1 text-xs font-semibold text-gray-500 uppercase">Search</label>
                                    <input v-model="searchQuery" type="text"
                                        placeholder="Search by name, cities, or companies..."
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500" />
                                </div>
                                <!-- Status Filter -->
                                <div>
                                    <label
                                        class="block mb-1 text-xs font-semibold text-gray-500 uppercase">Status</label>
                                    <select v-model="filterStatus"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                        <option value="all">All Status</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="upcoming">Upcoming</option>
                                        <option value="expired">Expired</option>
                                        <option value="deleted">Deleted</option>
                                    </select>
                                </div>
                                <!-- Reset Button -->
                                <button v-if="searchQuery || filterStatus !== 'all'" @click="clearFilters"
                                    class="w-full px-3 py-2 text-sm text-red-600 bg-red-200 rounded-lg hover:bg-red-300">
                                    Clear all filters
                                </button>
                                <p class="text-xs text-gray-400 text-center pt-1 border-t border-gray-100 mt-2">
                                    Showing {{ filteredDiscounts.length }} of {{ discounts?.length ?? 0 }} discounts
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Add New Discount Button -->
                    <button @click="openCreateModal"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="w-5 h-5 mr-2 -ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                clip-rule="evenodd" />
                        </svg>
                        Add New Discount
                    </button>
                </div>
            </div>

            <div class="overflow-hidden bg-white shadow sm:rounded-lg border border-primary">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-white uppercase">
                                    Name</th>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-white uppercase">
                                    Discount</th>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-white uppercase">
                                    Main City</th>
                                <th
                                    class="px-6 py-3 text-xs font-medium tracking-wider text-left text-white uppercase min-w-96">
                                    Mapped Cities</th>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-white uppercase">
                                    Companies</th>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-white uppercase">
                                    Status</th>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-white uppercase">
                                    Duration</th>
                                <th
                                    class="px-6 py-3 text-xs font-medium tracking-wider text-right text-white uppercase">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="discount in filteredDiscounts" :key="discount.id"
                                :class="{ 'bg-red-50': discount.deleted_at }">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ discount.name }}</div>
                                    <!-- <div class="mt-1 text-xs text-gray-500">Created By: {{ discount.created_by }}</div> -->
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-indigo-600">{{ discount.discount_percentage }}%
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 text-sm font-medium text-blue-800 bg-blue-100 rounded-full">
                                        {{ discount.main_city_name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <span v-for="city in discount.mapped_city_names_array" :key="city"
                                            class="px-2 py-1 text-xs font-medium text-indigo-800 bg-indigo-100 rounded-lg">
                                            {{ city }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div v-if="discount.company_ids && discount.company_ids.length > 0"
                                        class="flex flex-wrap gap-1">
                                        <span v-for="company in discount.company_names_array" :key="company"
                                            class="px-2 py-1 text-xs font-medium text-purple-800 bg-purple-100 rounded-lg">
                                            {{ company }}
                                        </span>
                                    </div>
                                    <div v-else>
                                        <span
                                            class="px-2 py-1 text-xs font-medium text-green-800 bg-green-100 rounded-lg">
                                            All Companies
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="discount.deleted_at"
                                        class="inline-flex px-2 text-xs font-semibold leading-5 text-red-800 bg-red-100 rounded-full cursor-pointer">
                                        Deleted
                                    </span>
                                    <div v-else>
                                        <span @click="toggleActive(discount.id)"
                                            :class="['px-2 inline-flex text-xs cursor-pointer leading-5 font-semibold rounded-full', getStatusColor(discount.status)]">
                                            {{ discount.status }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                    <div v-if="discount.start_date || discount.end_date">
                                        <div v-if="discount.start_date">From: {{ discount.start_date }}</div>
                                        <div v-if="discount.end_date">To: {{ discount.end_date }}</div>
                                    </div>
                                    <div v-else class="text-gray-400">No limit</div>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-right whitespace-nowrap">
                                    <div class="flex justify-end space-x-2">
                                        <template v-if="!discount.deleted_at">
                                            <button @click="toggleActive(discount.id)" :class="[
                                                'text-lg hover:scale-110 transition-transform',
                                                discount.is_active ? 'text-green-600 hover:text-green-800' : 'text-gray-400 hover:text-gray-600'
                                            ]"
                                                :title="discount.is_active ? 'Active - Click to Deactivate' : 'Inactive - Click to Activate'">
                                                {{ discount.is_active ? '✅' : '⭕' }}
                                            </button>
                                            <button @click="openEditModal(discount)"
                                                class="text-indigo-600 hover:text-indigo-900" title="Edit">✏️</button>
                                            <button @click="openDeleteModal(discount)"
                                                class="text-red-600 hover:text-red-900" title="Delete">🗑️</button>
                                        </template>
                                        <template v-else>
                                            <button @click="restoreDiscount(discount.id)"
                                                class="text-green-600 hover:text-green-900" title="Restore">♻️</button>
                                            <button @click="forceDeleteDiscount(discount.id)"
                                                class="text-red-600 hover:text-red-900"
                                                title="Permanently Delete">❌</button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredDiscounts.length === 0">
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <div class="mb-2 text-4xl">📭</div>
                                    <div>No discounts found</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <teleport to="body">
            <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75">
                    </div>
                    <div
                        class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                        <form @submit.prevent="submitCreate">
                            <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
                                <div class="sm:flex sm:items-start">
                                    <div class="w-full">
                                        <h3 class="mb-4 text-lg font-medium leading-6 text-gray-900">Create New Discount
                                        </h3>
                                        <div class="space-y-4">
                                            <div class="flex items-center gap-4">
                                                <div class="flex-1">
                                                    <label class="block text-sm font-medium text-gray-700">Discount Name
                                                        *</label>
                                                    <input v-model="createForm.name" type="text" required
                                                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                        placeholder="e.g., Summer Sale 2024" />
                                                    <div v-if="createForm.errors.name"
                                                        class="mt-1 text-sm text-red-600">{{
                                                            createForm.errors.name }}</div>
                                                </div>

                                                <div class="flex-1">
                                                    <label class="block text-sm font-medium text-gray-700">Discount
                                                        Percentage
                                                        *</label>
                                                    <input v-model="createForm.discount_percentage" type="number"
                                                        min="0" max="100" required
                                                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                        placeholder="0-100" />
                                                    <div v-if="createForm.errors.discount_percentage"
                                                        class="mt-1 text-sm text-red-600">{{
                                                            createForm.errors.discount_percentage
                                                        }}</div>
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Select Main City
                                                    *</label>
                                                <select v-model="createForm.main_city_id" required
                                                    class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    <option :value="null">-- Select Main City --</option>
                                                    <option v-for="city in mainCities" :key="city.id" :value="city.id">
                                                        {{
                                                            city.name }}</option>
                                                </select>
                                                <div v-if="createForm.errors.main_city_id"
                                                    class="mt-1 text-sm text-red-600">{{
                                                        createForm.errors.main_city_id }}</div>
                                            </div>

                                            <div v-if="createForm.main_city_id">
                                                <label class="block mb-2 text-sm font-medium text-gray-700">
                                                    Select Mapped Cities *
                                                </label>
                                                <div v-if="loadingMappedCities" class="text-sm text-gray-500">
                                                    Loading cities...
                                                </div>
                                                <div v-else
                                                    class="grid grid-cols-2 gap-2 p-3 overflow-y-auto border border-gray-300 rounded-md max-h-48">
                                                    <label v-for="city in mappedCities" :key="city.id"
                                                        class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                                        <input type="checkbox" :value="city.id"
                                                            v-model="createForm.mapped_city_ids"
                                                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" />
                                                        {{ city.name }}
                                                    </label>
                                                    <div v-if="mappedCities.length === 0"
                                                        class="text-sm text-gray-400 col-span-2">
                                                        No cities available
                                                    </div>
                                                </div>
                                                <div class="mt-1 text-xs text-gray-500">
                                                    Selected: {{ createForm.mapped_city_ids.length }} cities
                                                </div>
                                                <div v-if="createForm.errors.mapped_city_ids"
                                                    class="mt-1 text-sm text-red-600">
                                                    {{ createForm.errors.mapped_city_ids }}
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block mb-2 text-sm font-medium text-gray-700">
                                                    Select Companies
                                                </label>
                                                <div
                                                    class="grid grid-cols-2 gap-2 p-3 overflow-y-auto border border-gray-300 rounded-md max-h-40">
                                                    <label v-for="company in availableCompanies" :key="company.id"
                                                        class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                                        <input type="checkbox" :value="company.id"
                                                            v-model="createForm.company_ids"
                                                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" />
                                                        {{ company.name }}
                                                    </label>
                                                    <div v-if="availableCompanies.length === 0"
                                                        class="text-sm text-gray-400 col-span-2">
                                                        No companies available
                                                    </div>
                                                </div>
                                                <div class="mt-1 text-xs text-gray-500">
                                                    {{ createForm.company_ids.length > 0
                                                        ? `Selected: ${createForm.company_ids.length} companies`
                                                        : 'Leave empty to apply to all companies'
                                                    }}
                                                </div>
                                                <div v-if="createForm.errors.company_ids"
                                                    class="mt-1 text-sm text-red-600">
                                                    {{ createForm.errors.company_ids }}
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Start
                                                        Date</label>
                                                    <input v-model="createForm.start_date" type="datetime-local"
                                                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                                    <div v-if="createForm.errors.start_date"
                                                        class="mt-1 text-sm text-red-600">
                                                        {{ createForm.errors.start_date }}</div>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">End
                                                        Date</label>
                                                    <input v-model="createForm.end_date" type="datetime-local"
                                                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                                    <div v-if="createForm.errors.end_date"
                                                        class="mt-1 text-sm text-red-600">{{
                                                            createForm.errors.end_date }}</div>
                                                </div>
                                            </div>

                                            <div class="flex items-center">
                                                <input v-model="createForm.is_active" type="checkbox"
                                                    class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" />
                                                <label class="block ml-2 text-sm text-gray-900">Active (discount will be
                                                    applied
                                                    immediately)</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="px-4 py-3 bg-gray-50 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button type="submit" :disabled="createForm.processing"
                                    class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                                    {{ createForm.processing ? 'Creating...' : 'Create Discount' }}
                                </button>
                                <button type="button" @click="closeCreateModal"
                                    class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </teleport>

        <!-- Edit Modal (Same structure as Create, just use editForm) -->
        <teleport to="body">
            <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="closeEditModal">
                    </div>
                    <div
                        class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                        <form @submit.prevent="submitEdit">
                            <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
                                <div class="sm:flex sm:items-start">
                                    <div class="w-full">
                                        <h3 class="mb-4 text-lg font-medium leading-6 text-gray-900">Edit Discount</h3>
                                        <div class="space-y-4">
                                            <div class="flex items-center gap-4">
                                                <div class="flex-1">
                                                    <label class="block text-sm font-medium text-gray-700">Discount Name
                                                        *</label>
                                                    <input v-model="editForm.name" type="text" required
                                                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                                    <div v-if="editForm.errors.name" class="mt-1 text-sm text-red-600">
                                                        {{
                                                            editForm.errors.name }}</div>
                                                </div>

                                                <div class="flex-1">
                                                    <label class="block text-sm font-medium text-gray-700">Discount
                                                        Percentage
                                                        *</label>
                                                    <input v-model="editForm.discount_percentage" type="number" min="0"
                                                        max="100" required
                                                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                                    <div v-if="editForm.errors.discount_percentage"
                                                        class="mt-1 text-sm text-red-600">{{
                                                            editForm.errors.discount_percentage }}
                                                    </div>
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Select Main City
                                                    *</label>
                                                <select v-model="editForm.main_city_id" required
                                                    class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    <option :value="null">-- Select Main City --</option>
                                                    <option v-for="city in mainCities" :key="city.id" :value="city.id">
                                                        {{
                                                            city.name }}</option>
                                                </select>
                                                <div v-if="editForm.errors.main_city_id"
                                                    class="mt-1 text-sm text-red-600">{{
                                                        editForm.errors.main_city_id }}</div>
                                            </div>

                                            <div v-if="editForm.main_city_id">
                                                <label class="block mb-2 text-sm font-medium text-gray-700">
                                                    Select Mapped Cities *
                                                </label>
                                                <div v-if="loadingMappedCities" class="text-sm text-gray-500">
                                                    Loading cities...
                                                </div>
                                                <div v-else
                                                    class="grid grid-cols-2 gap-2 p-3 overflow-y-auto border border-gray-300 rounded-md max-h-48">
                                                    <label v-for="city in mappedCities" :key="city.id"
                                                        class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                                        <input type="checkbox" :value="city.id"
                                                            v-model="editForm.mapped_city_ids"
                                                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" />
                                                        {{ city.name }}
                                                    </label>
                                                    <div v-if="mappedCities.length === 0"
                                                        class="text-sm text-gray-400 col-span-2">
                                                        No cities available
                                                    </div>
                                                </div>
                                                <div class="mt-1 text-xs text-gray-500">
                                                    Selected: {{ editForm.mapped_city_ids.length }} cities
                                                </div>
                                                <div v-if="editForm.errors.mapped_city_ids"
                                                    class="mt-1 text-sm text-red-600">
                                                    {{ editForm.errors.mapped_city_ids }}
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block mb-2 text-sm font-medium text-gray-700">
                                                    Select Companies
                                                </label>
                                                <div
                                                    class="grid grid-cols-2 gap-2 p-3 overflow-y-auto border border-gray-300 rounded-md max-h-40">
                                                    <label v-for="company in availableCompanies" :key="company.id"
                                                        class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                                        <input type="checkbox" :value="company.id"
                                                            v-model="editForm.company_ids"
                                                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" />
                                                        {{ company.name }}
                                                    </label>
                                                    <div v-if="availableCompanies.length === 0"
                                                        class="text-sm text-gray-400 col-span-2">
                                                        No companies available
                                                    </div>
                                                </div>
                                                <div class="mt-1 text-xs text-gray-500">
                                                    {{ editForm.company_ids.length > 0
                                                        ? `Selected: ${editForm.company_ids.length} companies`
                                                        : 'Leave empty to apply to all companies'
                                                    }}
                                                </div>
                                                <div v-if="editForm.errors.company_ids"
                                                    class="mt-1 text-sm text-red-600">
                                                    {{ editForm.errors.company_ids }}
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Start
                                                        Date</label>
                                                    <input v-model="editForm.start_date" type="datetime-local"
                                                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                                    <div v-if="editForm.errors.start_date"
                                                        class="mt-1 text-sm text-red-600">{{
                                                            editForm.errors.start_date }}</div>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">End
                                                        Date</label>
                                                    <input v-model="editForm.end_date" type="datetime-local"
                                                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                                    <div v-if="editForm.errors.end_date"
                                                        class="mt-1 text-sm text-red-600">{{
                                                            editForm.errors.end_date }}</div>
                                                </div>
                                            </div>

                                            <div class="flex items-center">
                                                <input v-model="editForm.is_active" type="checkbox"
                                                    class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" />
                                                <label class="block ml-2 text-sm text-gray-900">Active</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="px-4 py-3 bg-gray-50 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button type="submit" :disabled="editForm.processing"
                                    class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                                    {{ editForm.processing ? 'Updating...' : 'Update Discount' }}
                                </button>
                                <button type="button" @click="closeEditModal"
                                    class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </teleport>

        <!-- Delete Confirmation Modal -->
        <teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="closeDeleteModal">
                    </div>
                    <div
                        class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div
                                    class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-red-100 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="w-6 h-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900">Delete Discount</h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">
                                            Are you sure you want to delete <strong>{{ selectedDiscount?.name
                                            }}</strong>? This
                                            action can be restored later.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="px-4 py-3 bg-gray-50 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="button" @click="confirmDelete"
                                class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-red-600 border border-transparent rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Delete
                            </button>
                            <button type="button" @click="closeDeleteModal"
                                class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </teleport>
    </CompanyLayout>
</template>
