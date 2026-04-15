<!-- resources/js/Pages/Profile/Sections/ProfileSidebar.vue -->
<template>
    <div class="relative p-6 bg-white border border-gray-200 rounded-lg shadow-sm">
        <div class="flex flex-wrap items-center mb-6">
            <img :src="customer?.profile_picture_url ||
                customer?.profile_picture_url
                "
                class="flex items-center justify-center w-20 h-20 bg-gray-100 border border-gray-300 rounded-full shadow"
                alt="Profile" />
            <div class="p-3">
                <h2 class="text-xl font-bold text-gray-800">
                    {{ customer?.Full_Name || "Loading..." }}
                </h2>
                <p class="text-gray-600">
                    {{ customer?.Email || "loading..." }}
                </p>
            </div>

            <span v-if="customer.is_email_verified"
                class="absolute px-3 py-1 text-sm text-white rounded-full top-2 right-2 bg-primary">
                <i class="fas fa-check-circle"></i> Verified
            </span>
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                <span class="text-gray-600">Member Since</span>
                <span class="font-medium">
                    {{ formatDate(customer?.MemberSince) || "N/A" }}
                </span>
            </div>
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                <span class="text-gray-600">Phone</span>
                <span class="font-medium">{{
                    customer?.Phone_Number || "N/A"
                    }}</span>
            </div>
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                <span class="text-gray-600">Location</span>
                <span class="font-medium">
                    {{ customer?.Address || "N/A" }}
                </span>
            </div>
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                <span class="text-gray-600">CNIC</span>
                <span class="font-medium">
                    {{ customer?.CNIC || "Not provided" }}
                </span>
            </div>
        </div>

        <div class="flex flex-row gap-2 mt-4">
            <button @click="openEditModal"
                class="flex items-center justify-center w-full py-3 text-white transition rounded-lg bg-primary hover:bg-primary-dark"
                :disabled="!customer">
                <i class="mr-2 bi bi-pen-fill"></i> Edit Profile
            </button>
            <form @submit.prevent="logout" method="POST" class="w-[-webkit-fill-available]">
                <button type="submit"
                    class="flex items-center justify-center w-full py-3 text-white transition rounded-lg bg-secondary hover:bg-secondary-dark">
                    <i class="mr-2 bi bi-box-arrow-left"></i> Logout
                </button>
            </form>
        </div>

        <!-- Edit Profile Modal -->
        <div v-if="showEditModal"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto bg-black bg-opacity-50"
            @click.self="closeEditModal">
            <div class="relative w-full max-w-2xl p-6 mx-auto bg-white rounded-lg shadow-lg">
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-4 mb-4 border-b">
                    <h3 class="text-xl font-semibold text-gray-900">
                        Edit Profile
                    </h3>
                    <button @click="closeEditModal" class="text-gray-400 hover:text-gray-600" :disabled="processing">
                        <i class="text-2xl bi bi-x-circle"></i>
                    </button>
                </div>

                <!-- Success/Error Messages in Modal -->
                <div v-if="successMessage"
                    class="p-3 mb-4 text-green-800 bg-green-100 border border-green-200 rounded-lg">
                    <div class="flex items-center">
                        <i class="mr-2 bi bi-check-circle"></i>
                        {{ successMessage }}
                    </div>
                </div>

                <div v-if="errorMessage" class="p-3 mb-4 text-red-800 bg-red-100 border border-red-200 rounded-lg">
                    <div class="flex items-center">
                        <i class="mr-2 bi bi-exclamation-circle"></i>
                        {{ errorMessage }}
                    </div>
                </div>

                <!-- Modal Body -->
                <form @submit.prevent="submitForm" enctype="multipart/form-data" class="space-y-6">
                    <!-- First Row: Full Name & Email -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Full Name *</label>
                            <input type="text" v-model="form.Full_Name" :class="[
                                'w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent',
                                form.errors.Full_Name
                                    ? 'border-red-300'
                                    : 'border-gray-300',
                            ]" required />
                            <p v-if="form.errors.Full_Name" class="mt-1 text-sm text-red-600">
                                {{ form.errors.Full_Name }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email *</label>
                            <input type="email" v-model="form.Email" :class="[
                                'w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent',
                                form.errors.Email
                                    ? 'border-red-300'
                                    : 'border-gray-300',
                            ]" required />
                            <p v-if="form.errors.Email" class="mt-1 text-sm text-red-600">
                                {{ form.errors.Email }}
                            </p>
                        </div>
                    </div>

                    <!-- Second Row: Phone Number & Emergency Number -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                            <input type="tel" v-model="form.Phone_Number" :class="[
                                'w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent',
                                form.errors.Phone_Number
                                    ? 'border-red-300'
                                    : 'border-gray-300',
                            ]" placeholder="+92 300 1234567" />
                            <p v-if="form.errors.Phone_Number" class="mt-1 text-sm text-red-600">
                                {{ form.errors.Phone_Number }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Emergency Number</label>
                            <input type="tel" v-model="form.Emergency_Number" :class="[
                                'w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent',
                                form.errors.Emergency_Number
                                    ? 'border-red-300'
                                    : 'border-gray-300',
                            ]" placeholder="+92 300 1234567" />
                            <p v-if="form.errors.Emergency_Number" class="mt-1 text-sm text-red-600">
                                {{ form.errors.Emergency_Number }}
                            </p>
                        </div>
                    </div>

                    <!-- Third Row: CNIC -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">CNIC</label>

                        <input type="text" v-model="cnicModel" maxlength="15" placeholder="12345-6789012-3" :class="[
                            'w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent',
                            form.errors.CNIC ? 'border-red-300' : 'border-gray-300'
                        ]" />

                        <p v-if="form.errors.CNIC" class="mt-1 text-sm text-red-600">
                            {{ form.errors.CNIC }}
                        </p>
                    </div>

                    <!-- Address (Full Width) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Address</label>
                        <textarea v-model="form.Address" rows="3" :class="[
                            'w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent',
                            form.errors.Address
                                ? 'border-red-300'
                                : 'border-gray-300',
                        ]" placeholder="House #, Street, Area"></textarea>
                        <p v-if="form.errors.Address" class="mt-1 text-sm text-red-600">
                            {{ form.errors.Address }}
                        </p>
                    </div>

                    <!-- Profile Picture Upload (Full Width) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Profile Picture</label>
                        <div class="flex flex-col gap-4 md:flex-row md:items-center">
                            <div class="flex-1">
                                <input type="file" ref="fileInput" @change="handleFileUpload" :disabled="processing"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                    accept="image/*" />
                                <p class="mt-1 text-sm text-gray-500">
                                    Maximum file size: 2MB (JPEG, PNG, GIF)
                                </p>
                                <p v-if="form.errors.Profile_Picture" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.Profile_Picture }}
                                </p>
                            </div>
                            <div v-if="previewImage" class="flex-shrink-0">
                                <div class="relative">
                                    <img :src="previewImage" alt="Preview"
                                        class="w-20 h-20 border-4 border-white rounded-full shadow" />
                                    <button type="button" @click="removeProfilePicture"
                                        class="absolute top-0 right-0 p-1 text-white bg-red-500 rounded-full hover:bg-red-600"
                                        :disabled="processing">
                                        <i class="text-xs bi bi-x"></i>
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-center text-gray-500">
                                    Click X to remove
                                </p>
                            </div>
                        </div>
                        <div v-if="customer?.Profile_Picture && !removePicture">
                            <label class="flex items-center mt-2">
                                <input type="checkbox" v-model="removePicture" :disabled="processing" class="mr-2" />
                                <span class="text-sm text-gray-600">Remove current profile picture</span>
                            </label>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex justify-end pt-4 mt-6 border-t">
                        <button type="button" @click="closeEditModal"
                            class="px-6 py-2 mr-3 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                            :disabled="processing">
                            Cancel
                        </button>
                        <button type="submit" :disabled="processing || form.processing"
                            class="flex items-center px-6 py-2 text-white transition rounded-lg bg-primary hover:bg-primary-dark disabled:opacity-50 disabled:cursor-not-allowed">
                            <span v-if="processing || form.processing">
                                <i class="mr-2 animate-spin bi bi-arrow-clockwise"></i>
                                Saving...
                            </span>
                            <span v-else>
                                <i class="mr-2 bi bi-check-lg"></i> Save Changes
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, watch, nextTick, computed } from "vue";
import { useForm, router } from "@inertiajs/vue3";

const props = defineProps({
    customer: {
        type: Object,
        default: () => ({}),
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const cnicModel = computed({
    get() {
        return formatCnic(form.CNIC)
    },
    set(value) {
        form.CNIC = unformatCnic(value)
    }
})

function formatCnic(value) {
    if (!value) return ""

    const digits = value.replace(/\D/g, "").slice(0, 13)

    if (digits.length <= 5) return digits
    if (digits.length <= 12) return `${digits.slice(0, 5)}-${digits.slice(5)}`
    return `${digits.slice(0, 5)}-${digits.slice(5, 12)}-${digits.slice(12)}`
}

function unformatCnic(value) {
    return value.replace(/\D/g, "")
}

const showEditModal = ref(false);
const processing = ref(false);
const previewImage = ref(null);
const removePicture = ref(false);
const fileInput = ref(null);
const successMessage = ref("");
const errorMessage = ref("");

// Initialize form using Inertia's useForm with ALL fields
const form = useForm({
    Full_Name: props.customer?.Full_Name || "",
    Email: props.customer?.Email || "",
    Phone_Number: props.customer?.Phone_Number || "",
    CNIC: props.customer?.CNIC || "",
    Emergency_Number: props.customer?.Emergency_Number || "",
    Address: props.customer?.Address || "",
    Profile_Picture: null,
    remove_profile_picture: false,
});

const resetForm = () => {
    // Don't use form.reset() as it clears all data
    // Instead, reinitialize with current customer data
    form.Full_Name = props.customer?.Full_Name || "";
    form.Email = props.customer?.Email || "";
    form.Phone_Number = props.customer?.Phone_Number || "";
    form.CNIC = props.customer?.CNIC || "";
    form.Emergency_Number = props.customer?.Emergency_Number || "";
    form.Address = props.customer?.Address || "";
    form.Profile_Picture = null;
    form.remove_profile_picture = false;

    processing.value = false;
    removePicture.value = false;
    successMessage.value = "";
    errorMessage.value = "";
};

// Watch for customer prop changes
watch(
    () => props.customer,
    (newCustomer) => {
        if (newCustomer) {
            // Update form with current customer data
            form.Full_Name = newCustomer.Full_Name || "";
            form.Email = newCustomer.Email || "";
            form.Phone_Number = newCustomer.Phone_Number || "";
            form.CNIC = newCustomer.CNIC || "";
            form.Emergency_Number = newCustomer.Emergency_Number || "";
            form.Address = newCustomer.Address || "";
            form.Profile_Picture = null;
            form.remove_profile_picture = false;

            processing.value = false;
            removePicture.value = false;
            previewImage.value =
                newCustomer.profile_picture_url ||
                newCustomer.Profile_Picture ||
                newCustomer.Avatar ||
                "/images/logo.jpg";
        }
    },
    { immediate: true, deep: true }
);

const openEditModal = () => {
    showEditModal.value = true;
    successMessage.value = "";
    errorMessage.value = "";

    // Reset form with current customer data
    form.Full_Name = props.customer?.Full_Name || "";
    form.Email = props.customer?.Email || "";
    form.Phone_Number = props.customer?.Phone_Number || "";
    form.CNIC = props.customer?.CNIC || "";
    form.Emergency_Number = props.customer?.Emergency_Number || "";
    form.Address = props.customer?.Address || "";
    form.Profile_Picture = null;
    form.remove_profile_picture = false;

    previewImage.value =
        props.customer?.profile_picture_url ||
        props.customer?.Profile_Picture ||
        props.customer?.Avatar ||
        "/images/logo.jpg";

    // Reset file input
    if (fileInput.value) {
        fileInput.value.value = "";
    }
};

const closeEditModal = () => {
    if (processing.value || form.processing) return;

    showEditModal.value = false;

    // Don't reset form here, just clear modal state
    processing.value = false;
    removePicture.value = false;
    successMessage.value = "";
    errorMessage.value = "";
};

const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        // Validate file size (2MB)
        if (file.size > 2 * 1024 * 1024) {
            errorMessage.value = "File size must be less than 2MB";
            event.target.value = "";
            return;
        }

        // Validate file type
        const validTypes = [
            "image/jpeg",
            "image/png",
            "image/gif",
            "image/jpg",
        ];
        if (!validTypes.includes(file.type)) {
            errorMessage.value =
                "Please select a valid image file (JPEG, PNG, GIF)";
            event.target.value = "";
            return;
        }

        form.Profile_Picture = file;
        removePicture.value = false;

        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            previewImage.value = e.target.result;
        };
        reader.readAsDataURL(file);

        // Clear any previous error
        errorMessage.value = "";
    }
};

const removeProfilePicture = () => {
    form.Profile_Picture = null;
    removePicture.value = true;
    previewImage.value = "/images/logo.jpg";

    // Clear file input
    if (fileInput.value) {
        fileInput.value.value = "";
    }

    // Clear any previous error
    errorMessage.value = "";
};

const submitForm = () => {
    if (processing.value || form.processing) return;

    processing.value = true;
    successMessage.value = "";
    errorMessage.value = "";

    // Set remove_profile_picture flag if checkbox is checked
    form.remove_profile_picture = removePicture.value;

    // Create FormData to properly send file
    const formData = new FormData();

    // Append ALL form fields
    formData.append('Full_Name', form.Full_Name);
    formData.append('Email', form.Email);
    formData.append('Phone_Number', form.Phone_Number);
    formData.append('CNIC', form.CNIC);
    formData.append('Emergency_Number', form.Emergency_Number);
    formData.append('Address', form.Address);
    formData.append('remove_profile_picture', form.remove_profile_picture);

    // Append file if exists
    if (form.Profile_Picture instanceof File) {
        formData.append('Profile_Picture', form.Profile_Picture);
    }

    // Use axios to send FormData
    axios.post(route('profile.update'), formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
        .then(response => {
            // Check for success message from backend
            if (response.data.success) {
                successMessage.value = response.data.success;

                // Update local customer data with returned data
                if (response.data.customer) {
                    Object.assign(props.customer, response.data.customer);
                }

                // Close modal after 1.5 seconds
                setTimeout(() => {
                    if (successMessage.value) {
                        closeEditModal();
                        // Emit event to refresh parent component
                        emit("profile-updated");
                        // Reload page to refresh data
                        window.location.reload();
                    }
                }, 1500);
            }

            processing.value = false;
        })
        .catch(error => {
            console.error("Update failed:", error);

            // Set error message from backend or generic message
            if (error.response?.data?.errors) {
                // Update form errors
                Object.assign(form.errors, error.response.data.errors);
                errorMessage.value = "Please fix the validation errors above.";
            } else if (error.response?.data?.message) {
                errorMessage.value = error.response.data.message;
            } else {
                errorMessage.value = "Failed to update profile. Please try again.";
            }

            processing.value = false;
        })
        .finally(() => {
            // Ensure processing is stopped
            setTimeout(() => {
                processing.value = false;
            }, 500);
        });
};

const logout = () => {
    router.post(route("logout"));
};

const formatDate = (dateString) => {
    if (!dateString) return "N/A";
    const date = new Date(dateString);
    return date.toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
    });
};

const emit = defineEmits(["profile-updated"]);
</script>
