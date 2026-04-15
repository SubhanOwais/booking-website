// stores/cityStore.js
import { defineStore } from "pinia";
import { ref } from "vue";
import axios from "axios";

export const useCityStore = defineStore("city", () => {
    const cities = ref([]);
    const loading = ref(false);
    const error = ref(null);

    const fetchCities = async () => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get("/api/cities");

            // ✅ API now returns a plain array
            cities.value = Array.isArray(response.data) ? response.data : [];

            // console.log("Fetched cities:", cities.value);
        } catch (err) {
            console.error("Failed to fetch cities:", err);
            error.value = "Failed to load cities";
            cities.value = [];
        } finally {
            loading.value = false;
        }
    };

    const initCities = () => {
        if (cities.value.length === 0) {
            fetchCities();
        }
    };

    return {
        cities,
        loading,
        error,
        fetchCities,
        initCities,
    };
});
