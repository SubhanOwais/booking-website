<template>
    <AdminLayout class="min-h-screen bg-gray-100">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Bus Gallery Management</h1>
            <button @click="openAddModal"
                class="flex items-center px-4 py-2 text-sm text-white transition bg-blue-600 rounded-lg hover:bg-blue-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add New Gallery
            </button>
        </div>

        <!-- Galleries Grid -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            <div v-for="gallery in galleries" :key="gallery.id"
                class="flex flex-col h-full p-4 overflow-hidden transition bg-white rounded-lg shadow-md hover:shadow-xl">
                <!-- Gallery Image -->
                <div class="relative h-48 -mx-4 -mt-4 overflow-hidden bg-gray-200">
                    <img v-if="gallery.MainImage" :src="`/storage/${gallery.MainImage}`" :alt="gallery.ServiceName"
                        class="object-cover w-full h-full" />
                    <div v-else class="flex items-center justify-center w-full h-full text-gray-400">
                        No Image
                    </div>

                    <!-- Active Badge -->
                    <div class="absolute px-3 py-1 text-xs font-semibold text-white rounded-full top-3 right-3"
                        :class="gallery.active ? 'bg-green-500' : 'bg-red-500'">
                        {{ gallery.active ? 'Active' : 'Inactive' }}
                    </div>
                </div>

                <!-- Gallery Info -->
                <div class="mt-3">
                    <h3 class="mb-2 text-xl font-bold text-gray-800">
                        {{ gallery.ServiceName }}
                    </h3>
                    <p class="mb-2 text-sm text-gray-600">
                        <strong>Type:</strong> {{ gallery.ServiceType }}
                    </p>
                    <p v-if="gallery.Type" class="mb-2 text-sm text-gray-600">
                        <strong>Category:</strong> {{ gallery.Type }}
                    </p>
                    <p v-if="gallery.Paragraph" class="mb-3 text-sm text-gray-600 line-clamp-2">
                        {{ gallery.Paragraph }}
                    </p>

                    <!-- Tags -->
                    <div v-if="gallery.Tags" class="flex flex-wrap gap-2 mb-3">
                        <span v-for="tag in gallery.Tags.split(',')" :key="tag"
                            class="px-2 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded-full">
                            {{ tag }}
                        </span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-2 mt-auto">
                    <button @click="editGallery(gallery)"
                        class="flex items-center justify-center flex-1 px-4 py-2 text-white transition bg-blue-500 rounded-lg hover:bg-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit
                    </button>
                    <button @click="confirmDelete(gallery.id)"
                        class="flex items-center justify-center flex-1 px-4 py-2 text-white transition bg-red-500 rounded-lg hover:bg-red-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete
                    </button>
                </div>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50">
            <div class="w-full max-w-6xl overflow-hidden bg-white rounded-lg shadow-xl max-h-[90vh]">
                <!-- Modal Header -->
                <div class="sticky top-0 z-10 flex items-center justify-between p-6 bg-white border-b">
                    <h2 class="text-2xl font-bold text-gray-800">
                        {{ editMode ? 'Edit Gallery' : 'Add New Gallery' }}
                    </h2>
                    <button @click="closeModal" class="text-gray-500 hover:text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
                    <form @submit.prevent="saveGallery">
                        <div class="flex flex-wrap -mx-2">
                            <!-- Service Type -->
                            <div class="w-full p-2 md:w-1/2">
                                <label class="block mb-2 text-sm font-bold text-gray-700">
                                    Service Type ID
                                    <span class="text-xs text-red-500">(Required)</span>
                                </label>
                                <input v-model="formData.ServiceType" type="text"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="e.g., AC Bus, Non-AC Bus"
                                    :class="errors.ServiceType ? 'border-red-500' : ''" />
                                <span v-if="errors.ServiceType" class="text-sm text-red-500">
                                    {{ errors.ServiceType }}
                                </span>
                            </div>

                            <!-- Service Name -->
                            <div class="w-full p-2 md:w-1/2">
                                <label class="block mb-2 text-sm font-bold text-gray-700">
                                    Service Name
                                    <span class="text-xs text-red-500">(Required)</span>
                                </label>
                                <input v-model="formData.ServiceName" type="text"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="e.g., Royal Movers Express"
                                    :class="errors.ServiceName ? 'border-red-500' : ''" />
                                <span v-if="errors.ServiceName" class="text-sm text-red-500">
                                    {{ errors.ServiceName }}
                                </span>
                            </div>

                            <!-- Type -->
                            <div class="w-full p-2 md:w-1/2">
                                <label class="block mb-2 text-sm font-bold text-gray-700">
                                    Type
                                </label>
                                <select v-model="formData.Type"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="" disabled>Select Type</option>
                                    <option value="Bus Gallery">Bus Gallery</option>
                                    <option value="Events">Events</option>
                                    <option value="Up Coming Buses">Up Coming Buses</option>
                                </select>
                            </div>

                            <!-- Active Status -->
                            <div class="flex items-center justify-between w-full p-2 md:w-1/2">
                                <div class="w-auto p-2 border border-gray-300 rounded-md select-none bg-gray-50">
                                    <div class="flex items-center justify-between">
                                        <h1 class="mr-20 font-bold text-gray-700">Active</h1>
                                        <label for="status"
                                            class="flex items-center cursor-pointer text-dark dark:text-white">
                                            <div class="relative">
                                                <input id="status" type="checkbox" v-model="formData.active"
                                                    class="sr-only peer toggleEight" />
                                                <div
                                                    class="h-5 transition bg-gray-300 rounded-full shadow-inner peer-checked:bg-gray-300 box w-14">
                                                </div>
                                                <div
                                                    class="absolute left-0 flex items-center justify-center transition bg-white rounded-full shadow-md -top-1 h-7 w-7 peer-checked:translate-x-full peer-checked:bg-blue-600 text-dark peer-checked:text-white">
                                                    <span
                                                        class="w-4 h-4 border border-current rounded-full bg-inherit active"></span>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Paragraph -->
                            <div class="w-full p-2">
                                <label class="block mb-2 text-sm font-bold text-gray-700">
                                    Description
                                </label>
                                <textarea v-model="formData.Paragraph" rows="4"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Enter bus service description..."></textarea>
                            </div>

                            <!-- Tags -->
                            <div class="w-full p-2">
                                <CustomTagInput v-model="formData.Tags" label="Tags" placeholder="Add tags..."
                                    :error="errors.Tags" :max-tags="50" :suggestions="[
                                        'AC',
                                        'Non-AC',
                                        'Luxury',
                                        'Reclining Seats',
                                        'WiFi',
                                        'USB Charging',
                                        'Entertainment System',
                                        'Washroom',
                                        'Sleeper',
                                        'Semi-Sleeper',
                                        'Volvo',
                                        'Mercedes',
                                        'Scania',
                                        'GPS Tracking',
                                        'CCTV',
                                        'First Aid',
                                    ]" />
                            </div>

                            <!-- Main Image and Gallery Images -->
                            <div class="flex flex-wrap w-full -mx-2">
                                <!-- Main Image -->
                                <div class="w-full p-2 lg:w-1/2">
                                    <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm">
                                        <h2 class="mb-4 text-xl font-semibold">
                                            Main Image
                                            <span class="text-xs text-red-500">(Required)</span>
                                        </h2>

                                        <!-- Upload Dropzone for Main Image -->
                                        <UploadDropzone ref="mainImageUploader" :max-files="1" :max-file-size="5"
                                            :multiple="false" @update:files="handleMainImages" />

                                        <span v-if="errors.mainImage" class="text-sm text-red-500">
                                            {{ errors.mainImage }}
                                        </span>

                                        <!-- Display existing main image -->
                                        <div v-if="galleryDetail.MainImage" class="mt-4">
                                            <p class="mb-2 text-sm font-semibold text-gray-700">Current Main Image:</p>
                                            <div class="relative overflow-hidden rounded-lg cursor-pointer group">
                                                <img :src="`/storage/${galleryDetail.MainImage}`"
                                                    class="w-full h-[200px] object-cover transition-transform duration-300 group-hover:scale-110"
                                                    alt="Main Image" />
                                                <div class="absolute top-3 right-3">
                                                    <button type="button"
                                                        @click="deleteMainImage(galleryDetail.MainImage)"
                                                        class="items-center inline-flex text-center align-bottom border-2 border-red-600 justify-center leading-[normal] p-1 rounded-full text-white bg-red-600 hover:bg-red-700 transition duration-200 ease-in-out cursor-pointer overflow-hidden select-none">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                            fill="currentColor" class="bi bi-x" viewBox="0 0 16 16">
                                                            <path
                                                                d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Gallery Images -->
                                <div class="w-full p-2 lg:w-1/2">
                                    <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm">
                                        <h2 class="mb-4 text-xl font-semibold">
                                            Gallery Images
                                        </h2>

                                        <!-- Upload Dropzone for Gallery -->
                                        <UploadDropzone ref="galleryImageUploader" :max-files="50" :max-file-size="5"
                                            :multiple="true" @update:files="handleGalleryImages" />

                                        <!-- Display existing gallery images -->
                                        <div v-if="galleryDetail.Images" class="mt-4">
                                            <p class="mb-2 text-sm font-semibold text-gray-700">Current Gallery Images:
                                            </p>
                                            <div
                                                class="grid grid-cols-2 gap-3 max-h-[325px] overflow-y-auto md:grid-cols-3 xl:grid-cols-4">
                                                <div v-for="(img, index) in galleryDetail.Images.split(', ')"
                                                    :key="index"
                                                    class="relative overflow-hidden rounded-lg cursor-pointer group">
                                                    <img :src="`/storage/${img.trim()}`"
                                                        class="w-full h-[100px] object-cover transition-transform duration-300 group-hover:scale-110"
                                                        :alt="img" />
                                                    <div class="absolute top-2 right-2">
                                                        <button type="button"
                                                            @click="deleteGalleryImage(img.trim(), index)"
                                                            class="items-center inline-flex text-center align-bottom border-2 border-red-600 justify-center leading-[normal] p-1 rounded-full text-white bg-red-600 hover:bg-red-700 transition duration-200 ease-in-out cursor-pointer overflow-hidden select-none">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16"
                                                                height="16" fill="currentColor" class="bi bi-x"
                                                                viewBox="0 0 16 16">
                                                                <path
                                                                    d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end gap-3 mt-6">
                            <button type="button" @click="closeModal"
                                class="px-6 py-3 text-gray-700 transition bg-gray-200 rounded-lg hover:bg-gray-300">
                                Cancel
                            </button>
                            <button type="submit" :disabled="loading"
                                class="px-6 py-3 text-white transition bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50">
                                <span v-if="loading">Saving...</span>
                                <span v-else>{{ editMode ? 'Update Gallery' : 'Create Gallery' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import UploadDropzone from './ImageUploader.vue'
import CustomTagInput from './CustomTagInput.vue'
import AdminLayout from "@/Layouts/AdminLayout.vue";

// Refs for uploaders
const mainImageUploader = ref(null)
const galleryImageUploader = ref(null)

// State
const galleries = ref([])
const showModal = ref(false)
const editMode = ref(false)
const loading = ref(false)
const errors = ref({})

// Form Data
const formData = ref({
    ServiceType: '',
    ServiceName: '',
    Type: '',
    Paragraph: '',
    active: true,
    Tags: [],
    mainImages: [],
    galleryImages: [],
})

const galleryDetail = ref({
    id: null,
    MainImage: null,
    Images: null,
})

// Fetch all galleries
const fetchGalleries = async () => {
    try {
        const response = await axios.get('/api/bus-gallery/get-galleries')
        if (response.data.status) {
            galleries.value = response.data.data
        }
    } catch (error) {
        console.error('Error fetching galleries:', error)
        alert('Failed to load galleries')
    }
}

// Open add modal
const openAddModal = () => {
    resetForm()
    editMode.value = false
    showModal.value = true
}

// Edit gallery
const editGallery = async (gallery) => {
    try {
        const response = await axios.get(`/api/bus-gallery/gallery-detail/${gallery.id}`)
        if (response.data.status) {
            const data = response.data.data

            formData.value = {
                ServiceType: data.ServiceType,
                ServiceName: data.ServiceName,
                Type: data.Type || '',
                Paragraph: data.Paragraph,
                active: data.active,
                Tags: data.Tags ? data.Tags.split(',') : [],
                mainImages: [],
                galleryImages: [],
            }

            galleryDetail.value = {
                id: data.id,
                MainImage: data.MainImage,
                Images: data.Images,
            }

            editMode.value = true
            showModal.value = true
        }
    } catch (error) {
        console.error('Error fetching gallery details:', error)
        alert('Failed to load gallery details')
    }
}

// Close modal
const closeModal = () => {
    showModal.value = false
    resetForm()
}

// Reset form
const resetForm = () => {
    formData.value = {
        ServiceType: '',
        ServiceName: '',
        Type: '',
        Paragraph: '',
        active: true,
        Tags: [],
        mainImages: [],
        galleryImages: [],
    }
    galleryDetail.value = {
        id: null,
        MainImage: null,
        Images: null,
    }
    errors.value = {}

    // Clear uploaders
    if (mainImageUploader.value) {
        mainImageUploader.value.clearFiles()
    }
    if (galleryImageUploader.value) {
        galleryImageUploader.value.clearFiles()
    }
}

// Handle main images
const handleMainImages = (files) => {
    formData.value.mainImages = files
    console.log('Main images updated:', files)
}

// Handle gallery images
const handleGalleryImages = (files) => {
    formData.value.galleryImages = files
    console.log('Gallery images updated:', files)
}

// Save gallery
const saveGallery = async () => {
    errors.value = {}

    // Validation
    if (!formData.value.ServiceType) {
        errors.value.ServiceType = 'Service Type is required'
    }
    if (!formData.value.ServiceName) {
        errors.value.ServiceName = 'Service Name is required'
    }
    if (!editMode.value && (!formData.value.mainImages || formData.value.mainImages.length === 0)) {
        errors.value.mainImage = 'Main Image is required'
    }

    if (Object.keys(errors.value).length > 0) {
        return
    }

    loading.value = true

    try {
        const formDataToSend = new FormData()

        // Add basic fields
        formDataToSend.append('ServiceType', formData.value.ServiceType)
        formDataToSend.append('ServiceName', formData.value.ServiceName)
        formDataToSend.append('Type', formData.value.Type || '')
        formDataToSend.append('Paragraph', formData.value.Paragraph || '')
        formDataToSend.append('active', formData.value.active ? 1 : 0)

        // Add tags
        if (formData.value.Tags && formData.value.Tags.length > 0) {
            formData.value.Tags.forEach((tag) => {
                formDataToSend.append('Tags[]', tag)
            })
        }

        // Add ID if editing
        if (editMode.value && galleryDetail.value.id) {
            formDataToSend.append('id', galleryDetail.value.id)
        }

        // Add main image
        if (formData.value.mainImages && formData.value.mainImages.length > 0) {
            formDataToSend.append('mainImage', formData.value.mainImages[0])
        }

        // Add gallery images
        if (formData.value.galleryImages && formData.value.galleryImages.length > 0) {
            formData.value.galleryImages.forEach((file) => {
                formDataToSend.append('Images[]', file)
            })
        }

        const response = await axios.post('/api/bus-gallery/add-gallery', formDataToSend, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        })

        if (response.data.status) {
            alert(response.data.message)
            closeModal()
            fetchGalleries()
        }
    } catch (error) {
        console.error('Error saving gallery:', error)
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors
        }
        alert('Failed to save gallery')
    } finally {
        loading.value = false
    }
}

// Delete main image
const deleteMainImage = async (imageName) => {
    if (!confirm('Are you sure you want to delete this main image?')) {
        return
    }

    const encodedImageName = btoa(imageName)
    const galleryId = galleryDetail.value.id

    try {
        const response = await axios.delete(
            `/api/bus-gallery/delete-main-image/${encodedImageName}/${galleryId}`
        )

        if (response.data.status) {
            alert('Main image deleted successfully')
            editGallery({ id: galleryId })
        }
    } catch (error) {
        console.error('Error deleting main image:', error)
        alert('Failed to delete main image')
    }
}

// Delete gallery image
const deleteGalleryImage = async (imageName, index) => {
    if (!confirm('Are you sure you want to delete this image?')) {
        return
    }

    const encodedImageName = btoa(imageName)
    const galleryId = galleryDetail.value.id

    try {
        const response = await axios.delete(
            `/api/bus-gallery/delete-gallery-image/${encodedImageName}/${galleryId}`
        )

        if (response.data.status) {
            alert('Gallery image deleted successfully')
            editGallery({ id: galleryId })
        }
    } catch (error) {
        console.error('Error deleting gallery image:', error)
        alert('Failed to delete gallery image')
    }
}

// Confirm delete gallery
const confirmDelete = async (id) => {
    if (!confirm('Are you sure you want to delete this gallery? All images will be deleted.')) {
        return
    }

    try {
        const response = await axios.delete(`/api/bus-gallery/delete-gallery/${id}`)

        if (response.data.status) {
            alert('Gallery deleted successfully')
            fetchGalleries()
        }
    } catch (error) {
        console.error('Error deleting gallery:', error)
        alert('Failed to delete gallery')
    }
}

// Mount
onMounted(() => {
    fetchGalleries()
})
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
