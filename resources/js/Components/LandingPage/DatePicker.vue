<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";

const props = defineProps({
    modelValue: {
        type: String,
        default: "",
    },
    label: {
        type: String,
        default: "Select Date",
    },
    placeholder: {
        type: String,
        default: "Choose a date",
    },
});

const emit = defineEmits(["update:modelValue"]);

const showDatePicker = ref(false);
const calendarMonth = ref(new Date().getMonth());
const calendarYear = ref(new Date().getFullYear());
const pickerRef = ref(null);

const currentMonthName = computed(() => {
    const months = [
        "January",
        "February",
        "March",
        "April",
        "May",
        "June",
        "July",
        "August",
        "September",
        "October",
        "November",
        "December",
    ];
    return months[calendarMonth.value];
});

const currentYear = computed(() => calendarYear.value);

const canGoPreviousMonth = computed(() => {
    const today = new Date();
    const currentMonth = new Date(calendarYear.value, calendarMonth.value, 1);
    const todayMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    return currentMonth > todayMonth;
});

const calendarDays = computed(() => {
    const days = [];
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const firstDay = new Date(calendarYear.value, calendarMonth.value, 1);
    const lastDay = new Date(calendarYear.value, calendarMonth.value + 1, 0);
    const prevLastDay = new Date(calendarYear.value, calendarMonth.value, 0);

    const firstDayOfWeek = firstDay.getDay();
    const lastDateOfMonth = lastDay.getDate();
    const prevLastDate = prevLastDay.getDate();
    const lastDayOfWeek = lastDay.getDay();

    // Previous month days
    for (let i = firstDayOfWeek; i > 0; i--) {
        const date = prevLastDate - i + 1;
        const fullDate = new Date(
            calendarYear.value,
            calendarMonth.value - 1,
            date
        );
        days.push({
            date,
            isCurrentMonth: false,
            disabled: true,
            isToday: false,
            selected: false,
            key: `prev-${date}`,
        });
    }

    // Current month days
    for (let date = 1; date <= lastDateOfMonth; date++) {
        const fullDate = new Date(
            calendarYear.value,
            calendarMonth.value,
            date
        );
        const isPast = fullDate < today;
        const isToday = fullDate.getTime() === today.getTime();
        const dateStr = formatDateISO(fullDate);

        days.push({
            date,
            fullDate,
            dateStr,
            isCurrentMonth: true,
            disabled: isPast,
            isToday,
            selected: props.modelValue === dateStr,
            key: `curr-${date}`,
        });
    }

    // Next month days
    const remainingDays = 7 - (lastDayOfWeek + 1);
    for (let date = 1; date <= remainingDays; date++) {
        const fullDate = new Date(
            calendarYear.value,
            calendarMonth.value + 1,
            date
        );
        const dateStr = formatDateISO(fullDate);

        days.push({
            date,
            fullDate,
            dateStr,
            isCurrentMonth: false,
            disabled: false,
            isToday: false,
            selected: props.modelValue === dateStr,
            key: `next-${date}`,
        });
    }

    return days;
});

function formatDateISO(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
}

function formatDisplayDate(dateStr) {
    if (!dateStr) return "";
    const date = new Date(dateStr + "T00:00:00");

    const months = [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "May",
        "Jun",
        "Jul",
        "Aug",
        "Sep",
        "Oct",
        "Nov",
        "Dec",
    ];
    const days = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

    return `${days[date.getDay()]}, ${months[date.getMonth()]
        } ${date.getDate()}, ${date.getFullYear()}`;
}

function selectDate(day) {
    if (day.disabled || !day.isCurrentMonth) return;
    emit("update:modelValue", day.dateStr);
    showDatePicker.value = false;
}

function selectToday() {
    const today = new Date();
    emit("update:modelValue", formatDateISO(today));
    showDatePicker.value = false;
}

function selectTomorrow() {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    emit("update:modelValue", formatDateISO(tomorrow));
    showDatePicker.value = false;
}

function previousMonth() {
    if (!canGoPreviousMonth.value) return;
    if (calendarMonth.value === 0) {
        calendarMonth.value = 11;
        calendarYear.value--;
    } else {
        calendarMonth.value--;
    }
}

function nextMonth() {
    if (calendarMonth.value === 11) {
        calendarMonth.value = 0;
        calendarYear.value++;
    } else {
        calendarMonth.value++;
    }
}

function handleClickOutside(event) {
    if (pickerRef.value && !pickerRef.value.contains(event.target)) {
        showDatePicker.value = false;
    }
}

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener("click", handleClickOutside);
});

// Watch for external changes to modelValue and update calendar view
watch(
    () => props.modelValue,
    (newValue) => {
        if (newValue) {
            const date = new Date(newValue + "T00:00:00");
            calendarMonth.value = date.getMonth();
            calendarYear.value = date.getFullYear();
        }
    }
);
</script>

<template>
    <div ref="pickerRef" class="relative">
        <!-- <label v-if="label" class="block text-sm font-semibold text-slate-700">
            {{ label }}
        </label> -->

        <!-- INPUT BUTTON -->
        <button type="button" @click="showDatePicker = !showDatePicker"
            class="w-full border border-gray-200 rounded-lg px-4 py-3 text-left focus:ring-2 focus:ring-green-500 outline-none bg-white">
            <span v-if="modelValue" class="text-gray-800 font-medium">
                {{ formatDisplayDate(modelValue) }}
            </span>
            <span v-else class="text-gray-400">
                {{ placeholder }}
            </span>
        </button>

        <!-- DATE PICKER -->
        <div v-if="showDatePicker"
            class="absolute z-50 mt-2 w-full bg-white border border-gray-200 rounded-xl shadow-lg p-3">

            <!-- HEADER -->
            <div class="flex items-center justify-between mb-3">
                <button @click="previousMonth" :disabled="!canGoPreviousMonth"
                    class="px-2 py-1 text-sm rounded hover:bg-gray-100 disabled:opacity-30">
                    ←
                </button>

                <div class="text-sm font-semibold text-gray-700">
                    {{ currentMonthName }} {{ currentYear }}
                </div>

                <button @click="nextMonth" class="px-2 py-1 text-sm rounded hover:bg-gray-100">
                    →
                </button>
            </div>

            <!-- DAYS -->
            <div class="grid grid-cols-7 text-xs text-center text-gray-400 mb-1">
                <div>Su</div>
                <div>Mo</div>
                <div>Tu</div>
                <div>We</div>
                <div>Th</div>
                <div>Fr</div>
                <div>Sa</div>
            </div>

            <!-- CALENDAR -->
            <div class="grid grid-cols-7 gap-1">
                <button v-for="day in calendarDays" :key="day.key" @click="selectDate(day)" :disabled="day.disabled"
                    :class="[
                        'h-8 rounded text-sm flex items-center justify-center',
                        day.selected
                            ? 'bg-green-600 text-white'
                            : day.disabled
                                ? 'text-gray-300 cursor-not-allowed'
                                : day.isCurrentMonth
                                    ? 'hover:bg-green-100 text-gray-700'
                                    : 'text-gray-400'
                    ]">
                    {{ day.date }}
                </button>
            </div>

            <!-- QUICK BUTTONS -->
            <div class="flex gap-2 mt-3">
                <button @click="selectToday" class="flex-1 text-xs py-2 border bg-primary text-white rounded-lg">
                    Today
                </button>
                <button @click="selectTomorrow" class="flex-1 text-xs py-2 border bg-primary text-white rounded-lg">
                    Tomorrow
                </button>
            </div>
        </div>
    </div>
</template>
