<script setup>
import { Head, useForm, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";

// Props
const props = defineProps({
    cities: Array,
});

// Refs
const searchName = ref("");
const statusFilter = ref("");
const showModal = ref(false);
const modalType = ref("create");
const editCity = ref(null);

// Local reactive copy
const allCities = ref([...props.cities]);

// Form
const cityForm = useForm({
    City_Name: "",
    Active: true,
});

// Filtered cities (client-side)
const filteredCities = computed(() => {
    return allCities.value.filter((city) => {
        const matchesName = searchName.value
            ? city.City_Name.toLowerCase().includes(searchName.value.toLowerCase())
            : true;

        const matchesStatus = statusFilter.value
            ? statusFilter.value === "active"
                ? city.Active
                : !city.Active
            : true;

        return matchesName && matchesStatus;
    });
});

// Check if filters are active
const hasActiveFilters = computed(() => searchName.value || statusFilter.value);

// Clear individual filter
const clearFilter = (type) => {
    if (type === "name") searchName.value = "";
    if (type === "status") statusFilter.value = "";
};

// Clear all filters
const clearFilters = () => {
    searchName.value = "";
    statusFilter.value = "";
};

// Open create modal
const openCreateModal = () => {
    cityForm.reset();
    modalType.value = "create";
    showModal.value = true;
};

// Open edit modal
const openEditModal = (city) => {
    editCity.value = city;
    cityForm.City_Name = city.City_Name;
    cityForm.Active = city.Active;
    modalType.value = "edit";
    showModal.value = true;
};

// Delete city
const openDeleteModal = (city) => {
    if (confirm(`Are you sure you want to delete "${city.City_Name}"?`)) {
        allCities.value = allCities.value.filter((c) => c.id !== city.id);
        router.delete(route("admin.cities.destroy", city.id), {
            preserveScroll: true,
        });
    }
};

// Submit create/edit form
const submitForm = () => {
    if (modalType.value === "create") {
        cityForm.post(route("admin.cities.store"), {
            onSuccess: () => {
                showModal.value = false;
                cityForm.reset();
                router.reload({ only: ["cities"] });
            },
        });
    } else {
        cityForm.put(route("admin.cities.update", editCity.value.id), {
            onSuccess: () => {
                showModal.value = false;

                // Optimistic update
                const index = allCities.value.findIndex(
                    (c) => c.id === editCity.value.id
                );
                if (index !== -1) {
                    allCities.value[index] = {
                        ...allCities.value[index],
                        City_Name: cityForm.City_Name,
                        Active: cityForm.Active,
                    };
                }

                editCity.value = null;
            },
        });
    }
};

// Toggle city status
const toggleStatus = (city) => {
    city.Active = !city.Active; // Optimistic update
    router.post(
        route("admin.cities.toggle-status", city.id),
        {},
        { preserveScroll: true }
    );
};
</script>

<template>

    <Head title="City Management" />

    <AdminLayout title="City Management">
        <div>
            <!-- Header -->
            <div class="flex flex-col justify-between gap-4 mb-6 md:flex-row md:items-center">
                <h1 class="text-2xl font-bold text-gray-900">City Management</h1>
                <button @click="openCreateModal"
                    class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                    + Add New City
                </button>
            </div>

            <!-- Filters -->
            <div class="p-4 mb-6 bg-white rounded-lg shadow">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <!-- Name Filter -->
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">
                            City Name
                        </label>
                        <input v-model="searchName" type="text" placeholder="Search by city name..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">
                            Status
                        </label>
                        <div class="flex gap-2">
                            <select v-model="statusFilter"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <button v-if="hasActiveFilters" @click="clearFilters"
                                class="px-4 py-2 text-sm text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200">
                                Clear All
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Active Filter Tags -->
                <div v-if="hasActiveFilters" class="flex flex-wrap items-center gap-2 mt-3">
                    <span class="text-sm text-gray-600">Active filters:</span>
                    <span v-if="searchName"
                        class="inline-flex items-center px-3 py-1 text-sm font-medium text-blue-700 bg-blue-100 rounded-full">
                        Name: "{{ searchName }}"
                        <button @click="clearFilter('name')" class="ml-1.5 text-blue-500 hover:text-blue-700">×</button>
                    </span>
                    <span v-if="statusFilter"
                        class="inline-flex items-center px-3 py-1 text-sm font-medium text-green-700 bg-green-100 rounded-full">
                        Status: {{ statusFilter === "active" ? "Active" : "Inactive" }}
                        <button @click="clearFilter('status')"
                            class="ml-1.5 text-green-500 hover:text-green-700">×</button>
                    </span>
                </div>

                <div class="mt-2 text-sm text-gray-600">
                    Showing {{ filteredCities.length }} of {{ allCities.length }} cities
                </div>
            </div>

            <!-- Cities Table -->
            <div class="overflow-x-auto bg-white rounded-lg shadow">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                #
                            </th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                City Name
                            </th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                Status
                            </th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                Created By
                            </th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-if="filteredCities.length === 0">
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                No cities found.
                                <button v-if="hasActiveFilters" @click="clearFilters"
                                    class="ml-2 text-indigo-600 hover:text-indigo-800">
                                    Clear filters
                                </button>
                            </td>
                        </tr>
                        <tr v-for="city in filteredCities" :key="city.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                {{ city.id }}
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">
                                {{ city.City_Name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span @click="toggleStatus(city)" :class="[
                                    'inline-flex px-2 py-1 text-xs font-semibold rounded-full cursor-pointer',
                                    city.Active
                                        ? 'bg-green-100 text-green-800 hover:bg-green-200'
                                        : 'bg-red-100 text-red-800 hover:bg-red-200',
                                ]">
                                    {{ city.Active ? "Active" : "Inactive" }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                {{ city.created_by }}
                            </td>
                            <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                <button @click="openEditModal(city)" class="mr-4 text-indigo-600 hover:text-indigo-900">
                                    Edit
                                </button>
                                <button @click="openDeleteModal(city)" class="text-red-600 hover:text-red-900">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create / Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div
                    class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-md sm:w-full sm:p-6">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">
                        {{ modalType === "create" ? "Add New City" : "Edit City" }}
                    </h3>

                    <form @submit.prevent="submitForm">
                        <div class="space-y-4">
                            <!-- City Name -->
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">
                                    City Name <span class="text-red-500">*</span>
                                </label>
                                <input v-model="cityForm.City_Name" type="text" required
                                    placeholder="Enter city name..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    :class="{ 'border-red-300': cityForm.errors.City_Name }" />
                                <p v-if="cityForm.errors.City_Name" class="mt-1 text-sm text-red-600">
                                    {{ cityForm.errors.City_Name }}
                                </p>
                            </div>

                            <!-- Active Checkbox -->
                            <div class="flex items-center">
                                <input v-model="cityForm.Active" type="checkbox" id="active"
                                    class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" />
                                <label for="active" class="block ml-2 text-sm text-gray-900">
                                    Active
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end mt-6 space-x-3">
                            <button type="button" @click="showModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" :disabled="cityForm.processing"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 disabled:opacity-50">
                                {{ cityForm.processing ? "Saving..." : "Save City" }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
