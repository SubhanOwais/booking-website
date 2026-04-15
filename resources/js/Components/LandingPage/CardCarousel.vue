<script setup>
import { computed, onMounted, ref, watchEffect } from 'vue';

const props = defineProps({
    ariaLabel: {
        type: String,
        default: 'carousel',
    },
    total: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(['prev', 'next']);

const index = ref(0);

const displayIndex = computed(() => index.value + 1);

function clamp() {
    index.value = Math.max(0, Math.min(index.value, props.total - 1));
}

function prev() {
    index.value -= 1;
    clamp();
    emit('prev', index.value);
}

function next() {
    index.value += 1;
    clamp();
    emit('next', index.value);
}

// Expose index so the parent can update it if needed.
defineExpose({
    index,
    setIndex(i) {
        index.value = i;
        clamp();
    },
});

const track = ref(null);
const stepPx = ref(0);

function measure() {
    if (!track.value) return;

    const first = track.value.querySelector('[data-card]');
    if (!first) return;

    const style = getComputedStyle(track.value);
    const gap = parseFloat(style.gap || style.columnGap || '16');

    stepPx.value = first.getBoundingClientRect().width + gap;
}

function applyTransform() {
    if (!track.value) return;

    track.value.style.transform = `translateX(${-index.value * stepPx.value}px)`;
}

onMounted(() => {
    measure();
    applyTransform();

    const ro = new ResizeObserver(() => {
        measure();
        applyTransform();
    });

    if (track.value) ro.observe(track.value);
});

// Update transform when index changes.
watchEffect(() => {
    // eslint-disable-next-line no-unused-expressions
    index.value;
    applyTransform();
});
</script>

<template>
    <div>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <slot name="heading" />
            </div>

            <div class="flex items-center gap-2 mt-4 sm:mt-0">
                <button type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-900/10 bg-white shadow-soft transition hover:-translate-y-0.5 hover:shadow-card"
                    :aria-label="`Previous ${ariaLabel}`" @click="prev">
                    <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M12.707 15.707a1 1 0 01-1.414 0l-5-5a1 1 0 010-1.414l5-5a1 1 0 011.414 1.414L8.414 10l4.293 4.293a1 1 0 010 1.414z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
                <button type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-900/10 bg-white shadow-soft transition hover:-translate-y-0.5 hover:shadow-card"
                    :aria-label="`Next ${ariaLabel}`" @click="next">
                    <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M7.293 4.293a1 1 0 011.414 0l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414-1.414L11.586 10 7.293 5.707a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="mt-4 overflow-hidden rounded-2xl">
            <div ref="track" class="flex gap-4 p-1 transition-transform duration-300 ease-out"
                style="will-change: transform">
                <slot />
            </div>
        </div>

        <div class="flex items-center justify-between mt-3 text-sm text-slate-500">
            <slot name="hint">
                <p>Swipe/scroll on mobile, or use arrows.</p>
            </slot>
            <p>{{ displayIndex }} / {{ total }}</p>
        </div>
    </div>
</template>
