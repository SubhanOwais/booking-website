import axios from 'axios';
window.axios = axios;

window.axios = axios
window.axios.defaults.withCredentials = true;  // ✅ sends session cookie with API requests
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.baseURL = '/';
