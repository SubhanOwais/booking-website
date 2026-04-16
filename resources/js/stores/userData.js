import { defineStore } from 'pinia'
import { ref, computed } from "vue";
import axios from "axios";

export const useUserStore = defineStore('userData', () => {
    const user = ref(null)
    const loaded = ref(false)
    const loading = ref(false)

    // ── Computed Permissions ──────────────────────────────────────────────────
    const isLoggedIn     = computed(() => !!user.value)
    const isSuperAdmin   = computed(() => !!user.value?.IsSuperAdmin)
    const isCompanyOwner = computed(() => {
        return user.value?.roles?.includes('Company Owner') || user.value?.User_Type === 'CompanyOwner';
    });
    const isCompanyUser  = computed(() => user.value?.User_Type === 'CompanyUser')
    const isWebCustomer  = computed(() => user.value?.User_Type === 'WebCustomer')
    const isCompanyMember = computed(() =>
        isCompanyOwner.value || isCompanyUser.value
    )
    const companyId = computed(() => user.value?.Company_Id ?? null)

    // ── Fetch once on app boot ────────────────────────────────────────────────
    async function fetchUser() {
        if (loaded.value || loading.value) return // ✅ only fetch once

        loading.value = true
        try {
            const { data } = await axios.get('/auth/me')
            if (data.success) {
                user.value = data.user
            } else {
                user.value = null
            }
        } catch {
            user.value = null
        } finally {
            loaded.value = true
            loading.value = false
        }
    }

    function clearUser() {
        user.value  = null
        loaded.value = false
    }

    return {
        user,
        loaded,
        loading,
        isLoggedIn,
        isSuperAdmin,
        isCompanyOwner,
        isCompanyUser,
        isWebCustomer,
        isCompanyMember,
        companyId,
        fetchUser,
        clearUser,
    }
})
