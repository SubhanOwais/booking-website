import { reactive } from 'vue'

const state = reactive({
    toasts: [],
})

let id = 0

const add = ({ severity = 'info', summary = '', detail = '', life = 3000 }) => {
    const toast = {
        id: ++id,
        severity,
        summary,
        detail,
        visible: true,
    }

    state.toasts.push(toast)
    setTimeout(() => remove(toast.id), life)
}

const remove = (toastId) => {
    const index = state.toasts.findIndex(t => t.id === toastId)
    if (index !== -1) {
        state.toasts[index].visible = false
        setTimeout(() => state.toasts.splice(index, 1), 300)
    }
}

// ✅ Shortcuts
const success = (summary, detail, life) =>
    add({ severity: 'success', summary, detail, life })

const error = (summary, detail, life) =>
    add({ severity: 'error', summary, detail, life })

const info = (summary, detail, life) =>
    add({ severity: 'info', summary, detail, life })

const warning = (summary, detail, life) =>
    add({ severity: 'warning', summary, detail, life })

export default {
    state,
    add,
    remove,
    success,
    error,
    info,
    warning,
}
