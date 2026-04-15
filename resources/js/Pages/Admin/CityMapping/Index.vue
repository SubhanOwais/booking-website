<script setup>
import { Head, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { ref, reactive, onMounted, computed, onUnmounted } from 'vue';

const props = defineProps({
    mappings: Array,
    cities: Array,
});

// ========== FILTER STATE ==========
const searchQuery = ref('');
const statusFilter = ref('all');
const showFilterDropdown = ref(false);
const filterDropdownRef = ref(null);

// ========== FILTERED MAPPINGS ==========
const filteredMappings = computed(() => {
    let filtered = props.mappings || [];

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(map => {
            const cityName = map.city?.City_Name?.toLowerCase() || '';
            const mappedNames = getMappedCityNames(map.City_Mapping).join(' ').toLowerCase();
            return cityName.includes(query) || mappedNames.includes(query);
        });
    }

    if (statusFilter.value !== 'all') {
        const isActive = statusFilter.value === 'active';
        filtered = filtered.filter(map => map.Active === isActive);
    }

    return filtered;
});

// ========== CLEAR FILTERS ==========
const clearFilters = () => {
    searchQuery.value = '';
    statusFilter.value = 'all';
    showFilterDropdown.value = false;
};

// ========== CLICK OUTSIDE HANDLER ==========
const handleClickOutside = (event) => {
    if (filterDropdownRef.value && !filterDropdownRef.value.contains(event.target)) {
        showFilterDropdown.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    initializeAvailableCities(props.cities);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

// ========== MODAL STATES ==========
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const editingMapping = ref(null);
const deletingId = ref(null);

// ========== FORM STATE (shared between create/edit) ==========
const form = reactive({
    city_id: "",
    city_mapping: [],
    active: true,
});

// ========== MULTI-SELECTION STATE ==========
const selectedCities = ref([]);        // Array of city IDs (integers)
const availableCities = ref([]);       // Array of { value: id, label: name }

// ========== MODAL DROPDOWN STATES ==========
const showCreateMainDropdown = ref(false);
const showCreateMappedDropdown = ref(false);
const showEditMappedDropdown = ref(false);

// ========== HELPER: Initialize available cities ==========
const initializeAvailableCities = (allCities, currentMappingIds = []) => {
    availableCities.value = allCities
        .filter((city) => !currentMappingIds.includes(parseInt(city.id)))
        .map((city) => ({
            value: parseInt(city.id),
            label: city.City_Name,
        }));
};

// ========== HELPER: Get city name by ID ==========
// const getCityName = (cityId) => {
//     const city = props.cities.find((c) => parseInt(c.id) === parseInt(cityId));
//     return city ? city.City_Name : "";
// };
const getCityName = (cityId) => {
    if (!cityId) return "";
    const numericId = parseInt(cityId);
    const city = props.cities.find((c) => parseInt(c.id) === numericId);
    return city ? city.City_Name : `Unknown (${numericId})`;
};

// ========== HELPER: Get mapped city names for table display ==========
const getMappedCityNames = (cityMapping) => {
    if (!cityMapping) return [];

    let ids = typeof cityMapping === "string" ? JSON.parse(cityMapping) : cityMapping;
    if (!Array.isArray(ids)) return [];

    return ids.map((id) => {
        const city = props.cities.find((c) => parseInt(c.id) === parseInt(id));
        return city ? city.City_Name : `ID: ${id}`;
    });
};

// ========== ADD / REMOVE CITIES (for both modals) ==========
const addCity = (cityId) => {
    const numericId = parseInt(cityId);
    if (numericId && !selectedCities.value.includes(numericId)) {
        selectedCities.value.push(numericId);
        // Remove from available
        availableCities.value = availableCities.value.filter(
            (c) => parseInt(c.value) !== numericId
        );
    }
};

const removeCity = (cityId) => {
    const numericId = parseInt(cityId);
    selectedCities.value = selectedCities.value.filter((id) => parseInt(id) !== numericId);
    // Add back to available
    const city = props.cities.find((c) => parseInt(c.id) === numericId);
    if (city) {
        availableCities.value.push({
            value: parseInt(city.id),
            label: city.City_Name,
        });
    }
};

// ========== RESET FORM ==========
const resetForm = () => {
    form.city_id = "";
    form.city_mapping = [];
    form.active = true;
    selectedCities.value = [];
    editingMapping.value = null;
    // Close any open dropdowns
    showCreateMainDropdown.value = false;
    showCreateMappedDropdown.value = false;
    showEditMappedDropdown.value = false;
    initializeAvailableCities(props.cities);
};

// ========== CREATE MAPPING ==========
const handleCreate = () => {
    // Send city IDs as integers
    form.city_mapping = selectedCities.value.map((id) => parseInt(id));

    router.post(route("admin.city-mapping.store"), form, {
        onSuccess: () => {
            showCreateModal.value = false;
            resetForm();
        },
        preserveScroll: true,
    });
};

// ========== EDIT MAPPING ==========
const editMapping = async (id) => {
    try {
        const response = await fetch(route("admin.city-mapping.edit", id));
        const data = await response.json();
        const mapping = data.mapping;

        console.log("Fetched mapping:", mapping);

        editingMapping.value = mapping;

        // ✅ Use correct property names from server: City_Id, City_Mapping, Active
        form.city_id = mapping.City_Id;          // Capital C, capital I
        form.active = mapping.Active === 1;      // Active is 1 or 0

        // Parse city_mapping – it's a JSON string in City_Mapping
        let mappingCityIds = mapping.City_Mapping;
        if (typeof mappingCityIds === "string") {
            try {
                mappingCityIds = JSON.parse(mappingCityIds);
            } catch (e) {
                mappingCityIds = [];
            }
        }
        if (!Array.isArray(mappingCityIds)) mappingCityIds = [];

        // Convert to integers
        const parsedIds = mappingCityIds.map(id => parseInt(id));
        selectedCities.value = [...parsedIds];

        console.log("Selected cities IDs:", selectedCities.value);
        console.log("Main city ID:", form.city_id);

        // Build available cities (exclude main city and already selected)
        const mainCityId = parseInt(mapping.City_Id);
        availableCities.value = props.cities
            .filter((city) => {
                const cityId = parseInt(city.id);
                return cityId !== mainCityId && !selectedCities.value.includes(cityId);
            })
            .map((city) => ({
                value: parseInt(city.id),
                label: city.City_Name,
            }));

        // Open modal
        showEditModal.value = true;
    } catch (error) {
        console.error("Error fetching mapping:", error);
    }
};

const handleEdit = () => {
    if (!form.city_id) {
        console.error("No city_id set – cannot update");
        return;
    }

    const updateData = {
        city_id: form.city_id,
        city_mapping: selectedCities.value.map((id) => parseInt(id)),
        active: form.active
    };

    console.log("Updating with data:", updateData);

    router.put(route("admin.city-mapping.update", editingMapping.value.id), updateData, {
        onSuccess: () => {
            showEditModal.value = false;
            resetForm();
        },
        onError: (errors) => {
            console.error("Update error:", errors);
        },
        preserveScroll: true,
    });
};

// ========== DELETE MAPPING ==========
const deleteMapping = (id) => {
    deletingId.value = id;
    showDeleteModal.value = true;
};

const confirmDelete = () => {
    if (!deletingId.value) return;
    router.delete(route("admin.city-mapping.destroy", deletingId.value), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            deletingId.value = null;
        },
    });
};

// ========== LIFECYCLE ==========
onMounted(() => {
    initializeAvailableCities(props.cities);
});

</script>

<template>

    <Head title="City Mappings" />
    <AdminLayout title="Royal Movies">
        <div>
            <!-- Header with Filter & Create buttons -->
            <div class="flex flex-col justify-between gap-4 mb-6 md:flex-row md:items-center">
                <div>
                    <h1 class="text-lg font-bold text-gray-800 md:text-2xl">City Mappings</h1>
                    <p class="mt-1 text-sm text-gray-500">Map main cities to multiple cities for search functionality
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Filter Button -->
                    <div class="relative" ref="filterDropdownRef">
                        <button @click="showFilterDropdown = !showFilterDropdown"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Filter
                            <span v-if="searchQuery || statusFilter !== 'all'"
                                class="ml-1 px-1.5 py-0.5 text-xs font-bold text-white bg-indigo-500 rounded-full">
                                {{ (searchQuery ? 1 : 0) + (statusFilter !== 'all' ? 1 : 0) }}
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
                                        placeholder="Search by city name or mapped cities..."
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500" />
                                </div>
                                <!-- Status Filter -->
                                <div>
                                    <label
                                        class="block mb-1 text-xs font-semibold text-gray-500 uppercase">Status</label>
                                    <select v-model="statusFilter"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                        <option value="all">All Status</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <!-- Reset Button -->
                                <button v-if="searchQuery || statusFilter !== 'all'" @click="clearFilters"
                                    class="w-full px-3 py-2 text-sm text-red-600 bg-red-200 rounded-lg hover:bg-red-300">
                                    Clear all filters
                                </button>
                                <p class="text-xs text-gray-400 text-center pt-1 border-t border-gray-100 mt-2">
                                    Showing {{ filteredMappings.length }} of {{ props.mappings?.length ?? 0 }} mappings
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Create Mapping Button -->
                    <button @click="showCreateModal = true, resetForm()"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Create Mapping
                    </button>
                </div>
            </div>

            <!-- Mappings Table -->
            <div class="overflow-hidden bg-white rounded-lg shadow">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                City</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                Mapped Cities</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">
                                Status</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="map in mappings" :key="map.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ map.city?.City_Name }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="(cityName, i) in getMappedCityNames(map.City_Mapping)" :key="i"
                                        class="inline-flex items-center px-3 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded-full">
                                        {{ cityName }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex justify-center">
                                    <span :class="[
                                        'inline-flex items-center px-3 py-1 rounded md:rounded-full text-xs font-medium',
                                        map.Active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800',
                                    ]">
                                        {{ map.Active ? "Active" : "Inactive" }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-center whitespace-nowrap">
                                <button @click="editMapping(map.id)"
                                    class="mr-3 text-blue-600 hover:text-blue-900">Edit</button>
                                <button @click="deleteMapping(map.id)"
                                    class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="mappings.length === 0">
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                No city mappings found. Create your first mapping!
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- ========== CREATE MODAL (inline) ========== -->
            <Transition enter-active-class="transition-opacity duration-300" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="transition-opacity duration-300"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showCreateModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50"
                    @click="showCreateModal = false">
                    <div class="w-full max-w-2xl bg-white rounded-lg shadow-xl" @click.stop>
                        <!-- Header -->
                        <div class="flex items-center justify-between p-6 border-b">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-800">Create City Mapping</h2>
                                <p class="mt-1 text-sm text-gray-500">Map a main city to multiple cities</p>
                            </div>
                            <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="p-6 space-y-6">
                            <!-- Main City Dropdown -->
                            <div class="relative">
                                <label class="block mb-2 text-sm font-medium text-gray-700">
                                    Main City <span class="text-red-500">*</span>
                                </label>
                                <div @click="showCreateMainDropdown = !showCreateMainDropdown"
                                    class="flex items-center justify-between w-full px-3 py-2 border border-gray-300 rounded-md cursor-pointer">
                                    <span>{{ getCityName(form.city_id) || "Select a city" }}</span>
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                                <ul v-if="showCreateMainDropdown"
                                    class="absolute z-50 w-full mt-1 overflow-auto bg-white border border-gray-300 rounded-md shadow-lg max-h-60">
                                    <li v-for="city in cities" :key="city.id"
                                        @click="form.city_id = city.id; showCreateMainDropdown = false"
                                        class="px-3 py-2 cursor-pointer hover:bg-blue-100"
                                        :class="{ 'bg-blue-50': parseInt(city.id) === parseInt(form.city_id) }">
                                        {{ city.City_Name }}
                                    </li>
                                </ul>
                            </div>

                            <!-- Mapped Cities (Multi-select) -->
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">
                                    Mapped Cities <span class="text-red-500">*</span>
                                    <span class="text-xs text-gray-500">(Select multiple)</span>
                                </label>
                                <div class="relative mb-3">
                                    <div @click="showCreateMappedDropdown = !showCreateMappedDropdown"
                                        class="flex items-center justify-between w-full px-3 py-2 border border-gray-300 rounded-md cursor-pointer">
                                        <span class="text-gray-600">Select cities to add...</span>
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                    <ul v-if="showCreateMappedDropdown"
                                        class="absolute z-50 w-full mt-1 overflow-auto bg-white border border-gray-300 rounded-md shadow-lg max-h-60">
                                        <li v-for="city in availableCities" :key="city.value"
                                            @click="addCity(city.value); showCreateMappedDropdown = false"
                                            class="px-3 py-2 cursor-pointer hover:bg-blue-100">
                                            {{ city.label }}
                                        </li>
                                        <li v-if="availableCities.length === 0"
                                            class="px-3 py-2 text-center text-gray-500">
                                            No cities available
                                        </li>
                                    </ul>
                                </div>
                                <div class="flex flex-wrap gap-2 p-3 border border-gray-300 rounded-md min-h-[60px]">
                                    <div v-for="cityId in selectedCities" :key="cityId"
                                        class="inline-flex items-center gap-2 px-3 py-1 bg-blue-100 rounded-lg">
                                        <span class="text-sm font-medium text-blue-800">{{ getCityName(cityId) }}</span>
                                        <button @click="removeCity(cityId)" type="button"
                                            class="text-blue-600 hover:text-blue-900">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div v-if="selectedCities.length === 0"
                                        class="flex items-center justify-center w-full text-gray-500">
                                        No cities selected.
                                    </div>
                                </div>
                            </div>

                            <!-- Active Checkbox -->
                            <div class="flex items-center">
                                <input type="checkbox" v-model="form.active" id="active-create"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded" />
                                <label for="active-create" class="ml-2 text-sm text-gray-700">Active</label>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="flex justify-end gap-3 p-6 border-t">
                            <button @click="showCreateModal = false; resetForm()" type="button"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                Cancel
                            </button>
                            <button @click="handleCreate" :disabled="!form.city_id || selectedCities.length === 0"
                                :class="[
                                    'px-4 py-2 text-sm font-medium text-white rounded-md',
                                    !form.city_id || selectedCities.length === 0
                                        ? 'bg-blue-400 cursor-not-allowed'
                                        : 'bg-blue-600 hover:bg-blue-700',
                                ]">
                                Create Mapping
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- ========== EDIT MODAL (inline) ========== -->
            <Transition enter-active-class="transition-opacity duration-300" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="transition-opacity duration-300"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showEditModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50">
                    <div class="w-full max-w-2xl bg-white rounded-lg shadow-xl" @click.stop>
                        <!-- Header -->
                        <div class="flex items-center justify-between p-6 border-b">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-800">Edit City Mapping</h2>
                                <p v-if="editingMapping" class="mt-1 text-sm text-gray-500">
                                    Editing mapping for: {{ editingMapping.city?.City_Name }}
                                </p>
                            </div>
                            <button @click="showEditModal = false, resetForm()"
                                class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="p-6 space-y-6">
                            <!-- Main City (readonly) -->
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">Main City</label>
                                <select v-model="form.city_id"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100" disabled>
                                    <option :value="form?.city_id">{{ editingMapping?.city?.City_Name }}
                                    </option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Main city cannot be changed after creation.</p>
                            </div>

                            <!-- Mapped Cities (Multi-select) -->
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">
                                    Mapped Cities <span class="text-red-500">*</span>
                                    <span class="text-xs text-gray-500">(Select multiple)</span>
                                </label>
                                <div class="relative mb-3">
                                    <div @click="showEditMappedDropdown = !showEditMappedDropdown"
                                        class="flex items-center justify-between w-full px-3 py-2 border border-gray-300 rounded-md cursor-pointer">
                                        <span class="text-gray-600">Select cities to add...</span>
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                    <ul v-if="showEditMappedDropdown"
                                        class="absolute z-50 w-full mt-1 overflow-auto bg-white border border-gray-300 rounded-md shadow-lg max-h-60">
                                        <li v-for="city in availableCities" :key="city.value"
                                            @click="addCity(city.value); showEditMappedDropdown = false"
                                            class="px-3 py-2 cursor-pointer hover:bg-blue-100">
                                            {{ city.label }}
                                        </li>
                                        <li v-if="availableCities.length === 0"
                                            class="px-3 py-2 text-center text-gray-500">
                                            No cities available
                                        </li>
                                    </ul>
                                </div>
                                <div class="flex flex-wrap gap-2 p-3 border border-gray-300 rounded-md min-h-[80px]">
                                    <div v-for="(cityId, idx) in selectedCities" :key="idx"
                                        class="inline-flex items-center gap-2 px-3 py-1 bg-blue-100 rounded-full">
                                        <span class="text-sm font-medium text-blue-800">{{ getCityName(cityId) }}</span>
                                        <button @click="removeCity(cityId)" type="button"
                                            class="text-blue-600 hover:text-blue-900">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div v-if="selectedCities.length === 0"
                                        class="flex items-center justify-center w-full text-gray-500">
                                        No cities selected.
                                    </div>
                                </div>
                            </div>

                            <!-- Active Checkbox -->
                            <div class="flex items-center">
                                <input type="checkbox" v-model="form.active" id="active-edit"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded" />
                                <label for="active-edit" class="ml-2 text-sm text-gray-700">Active</label>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="flex justify-end gap-3 p-6 border-t">
                            <button @click="showEditModal = false; resetForm()" type="button"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                Cancel
                            </button>
                            <button @click="handleEdit" :disabled="selectedCities.length === 0" :class="[
                                'px-4 py-2 text-sm font-medium text-white rounded-md',
                                selectedCities.length === 0
                                    ? 'bg-blue-400 cursor-not-allowed'
                                    : 'bg-blue-600 hover:bg-blue-700',
                            ]">
                                Update Mapping
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- ========== DELETE CONFIRMATION MODAL ========== -->
            <Transition enter-active-class="transition-opacity duration-300" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="transition-opacity duration-300"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showDeleteModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50">
                    <div class="w-full max-w-md bg-white rounded-lg shadow-xl">
                        <div class="p-6 border-b">
                            <h2 class="text-lg font-semibold text-gray-800">Delete City Mapping</h2>
                            <p class="mt-2 text-sm text-gray-500">
                                Are you sure you want to delete this mapping? This action cannot be undone.
                            </p>
                        </div>
                        <div class="flex justify-end gap-3 p-6">
                            <button @click="showDeleteModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                Cancel
                            </button>
                            <button @click="confirmDelete"
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </AdminLayout>
</template>
