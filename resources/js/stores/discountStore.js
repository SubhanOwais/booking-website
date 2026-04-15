// stores/discountStore.js
import { defineStore } from "pinia";
import { ref, computed } from "vue";
import axios from "axios";

export const useDiscountStore = defineStore("discount", () => {
    // ── State ─────────────────────────────────────────
    const discounts = ref([]);
    const loading   = ref(false);
    const loaded    = ref(false);
    const error     = ref(null);

    // ── Computed ──────────────────────────────────────
    const activeDiscounts = computed(() =>
        discounts.value.filter((d) => d.status === "active")
    );

    const hasActiveDiscounts = computed(() =>
        activeDiscounts.value.length > 0
    );

    // ── Fetch Discounts ───────────────────────────────
    const fetchDiscounts = async (force = false) => {
        // Prevent duplicate calls
        if (loading.value) return;

        // Skip if already loaded (unless force)
        if (loaded.value && !force) return;

        loading.value = true;
        error.value   = null;

        try {
            console.log("Fetching discounts...");

            const response = await axios.get("/api/discounts/all");

            if (response.data.success) {
                discounts.value = response.data.discounts || [];
                loaded.value    = true;
            } else {
                discounts.value = [];
                error.value = response.data.message || "Failed to load discounts";
            }
        } catch (err) {
            console.error("Discount API error:", err);
            error.value = "Unable to load discounts";
            discounts.value = [];
        } finally {
            loading.value = false;
        }
    };

    // ── Get Discount by City ──────────────────────────
    const getDiscountForCity = (cityId) => {
        if (!cityId) return null;

        return activeDiscounts.value.find(
            (d) => String(d.main_city_id) === String(cityId)
        ) ?? null;
    };

    // ── Force Refresh ────────────────────────────────
    const refresh = () => fetchDiscounts(true);

    return {
        discounts,
        loading,
        loaded,
        error,

        activeDiscounts,
        hasActiveDiscounts,

        fetchDiscounts,
        getDiscountForCity,
        refresh,
    };
},  {
    persist: {
        key: 'discount-store',
        storage: localStorage,
    }
});
