import axios from 'axios';

const token = document
    .querySelector('meta[name="csrf-token"]')
    .getAttribute('content');

axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
