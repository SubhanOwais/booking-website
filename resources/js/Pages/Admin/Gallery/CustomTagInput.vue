<template>
    <div class="relative w-full">
        <label class="block mb-1 text-xs font-semibold tracking-wide text-gray-700 md:text-base">
            {{ label }} <span class="text-xs text-red-500">(Required)</span>
        </label>

        <div
            class="min-h-[50px] p-2 border-2 rounded-lg bg-white shadow-sm transition-all duration-200 focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100"
            :class="[error ? 'border-red-300' : 'border-gray-200']"
        >
            <div class="flex flex-wrap gap-2">
                <!-- Existing Tags -->
                <div
                    v-for="tag in modelValue"
                    :key="tag"
                    class="flex items-center gap-1 px-3 py-1 text-sm font-medium text-blue-700 transition-colors duration-200 border border-blue-200 rounded-full group bg-blue-50 hover:bg-blue-100"
                >
                    <span>{{ tag }}</span>
                    <button
                        @click="removeTag(tag)"
                        class="inline-flex items-center justify-center w-4 h-4 transition-colors duration-200 rounded-full hover:bg-blue-200 hover:text-blue-800"
                        type="button"
                    >
                        ×
                    </button>
                </div>

                <!-- Input Field -->
                <input
                    ref="inputRef"
                    v-model="inputValue"
                    type="text"
                    @keydown.enter.prevent="addTag"
                    @keydown="handleKeyDown"
                    @keydown.backspace="handleBackspace"
                    @keydown.down="handleArrowDown"
                    @keydown.up="handleArrowUp"
                    @focus="showSuggestions = true"
                    :placeholder="placeholder"
                    class="flex-grow min-w-[120px] outline-none text-gray-700 text-sm py-1"
                />
            </div>
        </div>

        <!-- Suggestions Dropdown -->
        <div
            v-show="showSuggestions && filteredSuggestions.length > 0"
            class="absolute z-50 w-full p-2 pl-1 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg"
        >
            <div class="p-1 overflow-y-auto max-h-60">
                <button
                    v-for="(suggestion, index) in filteredSuggestions"
                    :key="suggestion"
                    @click="selectSuggestion(suggestion)"
                    type="button"
                    class="w-full px-3 py-2 text-sm text-left text-gray-700 transition-colors duration-150 rounded-md hover:bg-blue-50"
                    :class="{ 'bg-blue-50': selectedIndex === index }"
                >
                    {{ suggestion }}
                </button>
            </div>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="mt-1 text-sm text-red-500">
            {{ error }}
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    label: {
        type: String,
        default: 'Tags',
    },
    placeholder: {
        type: String,
        default: 'Add a tag...',
    },
    suggestions: {
        type: Array,
        default: () => [
            'Umrah Visa',
            'Visa Included',
            'Tourist Visa',
            'Visiting best places',
            'Flight',
            'Restaurant & Dining',
            'Bus Transport',
            '1 Star Hotel',
            '2 Star Hotel',
            '3 Star Hotel',
            '4 Star Hotel',
            '5 Star Hotel',
            'Airport Transfer',
            'Private Tour',
            'Private Guide',
            'Room Service',
            'Transportation',
        ],
    },
    error: {
        type: String,
        default: '',
    },
    maxTags: {
        type: Number,
        default: Infinity,
    },
})

const emit = defineEmits(['update:modelValue'])
const inputRef = ref(null)
const inputValue = ref('')
const showSuggestions = ref(false)
const selectedIndex = ref(-1)

const filteredSuggestions = computed(() => {
    const searchTerm = inputValue.value.toLowerCase()
    return props.suggestions.filter(
        (suggestion) =>
            suggestion.toLowerCase().includes(searchTerm) && !props.modelValue.includes(suggestion)
    )
})

// Modified to prevent individual letter splitting
const addTag = (event) => {
    // Prevent default form submission
    if (event) {
        event.preventDefault()
    }

    // If a suggestion is selected, use that
    if (selectedIndex.value >= 0 && filteredSuggestions.value[selectedIndex.value]) {
        const suggestion = filteredSuggestions.value[selectedIndex.value]
        if (!props.modelValue.includes(suggestion) && props.modelValue.length < props.maxTags) {
            emit('update:modelValue', [...props.modelValue, suggestion])
        }
    } else {
        // Handle manual input
        const value = inputValue.value.trim()
        if (value && !props.modelValue.includes(value) && props.modelValue.length < props.maxTags) {
            emit('update:modelValue', [...props.modelValue, value])
        }
    }

    inputValue.value = ''
    selectedIndex.value = -1
    showSuggestions.value = false
}

const removeTag = (tagToRemove) => {
    emit(
        'update:modelValue',
        props.modelValue.filter((tag) => tag !== tagToRemove)
    )
}

const handleBackspace = () => {
    if (inputValue.value === '' && props.modelValue.length > 0) {
        removeTag(props.modelValue[props.modelValue.length - 1])
    }
}

const handleArrowDown = () => {
    if (!showSuggestions.value) {
        showSuggestions.value = true
        return
    }
    selectedIndex.value = Math.min(selectedIndex.value + 1, filteredSuggestions.value.length - 1)
}

const handleArrowUp = () => {
    selectedIndex.value = Math.max(selectedIndex.value - 1, -1)
}

const selectSuggestion = (suggestion) => {
    if (!props.modelValue.includes(suggestion) && props.modelValue.length < props.maxTags) {
        emit('update:modelValue', [...props.modelValue, suggestion])
        inputValue.value = ''
        selectedIndex.value = -1
        showSuggestions.value = false
    }
}

const handleClickOutside = (event) => {
    if (inputRef.value && !inputRef.value.contains(event.target)) {
        showSuggestions.value = false
    }
}

// Add handler for comma input
const handleKeyDown = (event) => {
    if (event.key === ',') {
        event.preventDefault()
        addTag()
    }
}

watch(inputValue, (val) => {
    showSuggestions.value = val.length > 0
    selectedIndex.value = -1
})

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>
