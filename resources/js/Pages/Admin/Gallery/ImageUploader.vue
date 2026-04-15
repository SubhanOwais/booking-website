<template>
    <div>
        <div class="relative flex flex-col items-center justify-center w-full h-32 mb-2 border-2 border-dashed rounded-lg cursor-pointer hover:bg-gray-50"
            :class="borderClass" @click="triggerFileInput" @dragover.prevent="handleDragOver"
            @dragleave.prevent="handleDragLeave" @drop.prevent="handleDrop">
            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                <svg class="w-10 h-10 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <p class="mb-2 text-sm text-gray-500">
                    <span class="font-semibold">Click to upload</span> or drag and drop
                </p>
                <p class="text-xs text-gray-500">PNG, JPG, or WEBP (MAX. {{ maxFileSize }}MB)</p>
            </div>
            <input ref="fileInput" type="file" :multiple="multiple" accept="image/*" class="hidden"
                @change="handleFileSelect" />
        </div>

        <!-- Upload Progress -->
        <div v-if="uploadingFiles.length > 0" class="space-y-2">
            <div v-for="file in uploadingFiles" :key="file.id"
                class="flex p-3 bg-white border border-gray-200 rounded-lg shadow">
                <div class="flex items-center mr-3">
                    <img v-if="file.previewUrl" :src="file.previewUrl"
                        class="object-cover w-16 h-16 border border-gray-300 rounded" alt="Preview" />
                </div>
                <div class="flex flex-col flex-1">
                    <div class="flex items-center justify-between w-full mb-2">
                        <span class="text-sm font-medium text-gray-900 truncate max-w-[200px]">{{ file.name }}</span>
                        <div class="flex items-center justify-end space-x-2">
                            <span class="text-xs text-gray-500">
                                {{ formatFileSize(file.size) }}
                            </span>
                            <button @click.stop="cancelUpload(file.id)"
                                class="p-1 text-gray-500 transition-colors hover:text-red-500" type="button">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path
                                        d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="w-full h-2 overflow-hidden bg-gray-200 rounded-full">
                        <div class="h-2 transition-all duration-300 rounded-full"
                            :class="file.error ? 'bg-red-500' : 'bg-blue-500'" :style="{ width: `100%` }"></div>
                    </div>
                    <div v-if="file.error" class="flex items-center justify-between w-full mt-1">
                        <span class="text-xs text-red-500">{{ file.error }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, defineProps, defineEmits } from 'vue'

const props = defineProps({
    maxFiles: {
        type: Number,
        default: 50,
    },
    maxFileSize: {
        type: Number,
        default: 5,
    },
    multiple: {
        type: Boolean,
        default: true,
    },
})

const emit = defineEmits(['update:files'])
const uploadingFiles = ref([])
const fileInput = ref(null)
const isDragging = ref(false)

const borderClass = ref('border-gray-300')

const handleDragOver = () => {
    isDragging.value = true
    borderClass.value = 'border-blue-500 bg-blue-50'
}

const handleDragLeave = () => {
    isDragging.value = false
    borderClass.value = 'border-gray-300'
}

const handleDrop = (event) => {
    isDragging.value = false
    borderClass.value = 'border-gray-300'
    const files = Array.from(event.dataTransfer.files)
    processFiles(files)
}

const handleFileSelect = (event) => {
    const files = Array.from(event.target.files)
    processFiles(files)
    // Reset input
    event.target.value = ''
}

const processFiles = (files) => {
    const validFiles = files.filter((file) => {
        const fileSizeMB = file.size / 1024 / 1024
        if (fileSizeMB > props.maxFileSize) {
            alert(`File ${file.name} is too large! Max size is ${props.maxFileSize} MB.`)
            return false
        }
        if (!file.type.match('image.*')) {
            alert(`File ${file.name} is not an image!`)
            return false
        }
        return true
    })

    const processedFiles = validFiles.map((file, index) => ({
        id: Date.now() + index,
        name: file.name,
        size: file.size,
        progress: 100,
        previewUrl: URL.createObjectURL(file),
        error: null,
        file: file,
    }))

    if (!props.multiple) {
        uploadingFiles.value = processedFiles
    } else {
        uploadingFiles.value.push(...processedFiles)
    }

    emit(
        'update:files',
        uploadingFiles.value.map((f) => f.file)
    )
}

const formatFileSize = (size) => {
    const kb = size / 1024
    const mb = kb / 1024
    if (mb >= 1) {
        return `${mb.toFixed(2)} MB`
    }
    return `${kb.toFixed(2)} KB`
}

const cancelUpload = (fileId) => {
    const fileToRemove = uploadingFiles.value.find((file) => file.id === fileId)
    if (fileToRemove && fileToRemove.previewUrl) {
        URL.revokeObjectURL(fileToRemove.previewUrl)
    }

    uploadingFiles.value = uploadingFiles.value.filter((file) => file.id !== fileId)

    emit(
        'update:files',
        uploadingFiles.value.map((f) => f.file)
    )
}

const triggerFileInput = () => {
    fileInput.value.click()
}

// Clear files (can be called from parent)
const clearFiles = () => {
    uploadingFiles.value.forEach(file => {
        if (file.previewUrl) {
            URL.revokeObjectURL(file.previewUrl)
        }
    })
    uploadingFiles.value = []
    emit('update:files', [])
}

// Expose clearFiles method to parent
defineExpose({
    clearFiles
})
</script>
