import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

export const useCompanyDataStore = defineStore('companyData', () => {
    // State
    const company = ref(null)
    const owner = ref(null)
    const users = ref([])
    const loading = ref(false)
    const error = ref(null)

    // Getters
    const allUsers = computed(() => {
        return [...(owner.value ? [owner.value] : []), ...users.value]
    })

    const companyName = computed(() => company.value?.company_name || '')
    const isCompanyActive = computed(() => company.value?.is_active || false)

    // Actions
    async function fetchCompanyData() {
        loading.value = true
        error.value = null

        try {
            // Option 1: Use Inertia to fetch data (requires a dedicated route)
            // This will navigate to the page, but you might want to just fetch data.
            // Instead, we can use axios or fetch to call an API endpoint.

            // For this example, we'll assume you have an API endpoint:
            // GET /company/company-data
            const response = await axios.get('/company/company-data')
            const data = response.data
            company.value = data.company
            owner.value = data.owner
            users.value = data.users
        } catch (err) {
            error.value = err.message || 'Failed to load company data'
            console.error('Error fetching company data:', err)
        } finally {
            loading.value = false
        }
    }

    // Option: Initialize with data from Inertia props (if you already have them on page load)
    function setInitialData({ company: comp, owner: own, users: usrs }) {
        company.value = comp
        owner.value = own
        users.value = usrs
    }

    // Actions for modifying data (example: toggle user active status)
    async function toggleUserStatus(userId, newStatus) {
        try {
            await axios.patch(`/api/company/users/${userId}/toggle-status`, { is_active: newStatus })
            // Update local state
            const userToUpdate = [...users.value, owner.value].find(u => u.id === userId)
            if (userToUpdate) userToUpdate.Is_Active = newStatus
        } catch (err) {
            console.error('Failed to update user status:', err)
            throw err
        }
    }

    // Add user, update user, delete user actions can be added similarly

    return {
        // state
        company,
        owner,
        users,
        loading,
        error,
        // getters
        allUsers,
        companyName,
        isCompanyActive,
        // actions
        fetchCompanyData,
        setInitialData,
        toggleUserStatus,
        // ... other actions
    }
})
