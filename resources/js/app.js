import axios from 'axios';

// Add your JavaScript code here
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';