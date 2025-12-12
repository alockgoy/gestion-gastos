import axios from 'axios';

// Detectar automáticamente la URL base según el hostname
const getApiUrl = () => {
  if (import.meta.env.VITE_API_URL) {
    return import.meta.env.VITE_API_URL;
  }
  
  // Usar ruta relativa para que nginx del frontend haga de proxy
  return '/api';
};

const API_URL = getApiUrl();

const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Interceptor para agregar token a las peticiones
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Interceptor para manejar errores
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

// Auth
export const authAPI = {
  register: (data) => api.post('/auth/register', data),
  login: (data) => api.post('/auth/login', data),
  verify2FA: (data) => api.post('/auth/verify-2fa', data),
  requestPasswordReset: (data) => api.post('/auth/request-password-reset', data),
  resetPassword: (data) => api.post('/auth/reset-password', data),
  validateSession: () => api.get('/auth/validate'),
  logout: () => api.post('/auth/logout'),
};

// User
export const userAPI = {
  getProfile: () => api.get('/user/profile'),
  updateProfile: (data) => api.put('/user/profile', data),
  changePassword: (data) => api.put('/user/change-password', data),
  uploadProfilePhoto: (formData) => api.post('/user/profile-photo', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  }),
  toggle2FA: (data) => api.put('/user/toggle-2fa', data),
  requestAdmin: () => api.post('/user/request-admin'),
  deleteAccount: (data) => api.delete('/user/account', { data }),
  getSessions: () => api.get('/user/sessions'),
  generateAPIToken: (data) => api.post('/user/api-tokens', data),
  listAPITokens: () => api.get('/user/api-tokens'),
  deleteAPIToken: (data) => api.delete('/user/api-tokens', { data }),
};

// Accounts
export const accountsAPI = {
  create: (data) => api.post('/accounts', data),
  getAll: (params) => api.get('/accounts', { params }),
  getOne: (id) => api.get(`/accounts/${id}`),
  update: (id, data) => api.put(`/accounts/${id}`, data),
  delete: (id) => api.delete(`/accounts/${id}`),
  getSummary: () => api.get('/accounts/summary'),
  search: (query) => api.get('/accounts/search', { params: { q: query } }),
};

// Movements
export const movementsAPI = {
  create: (formData) => api.post('/movements', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  }),
  getAll: (params) => api.get('/movements', { params }),
  getOne: (id) => api.get(`/movements/${id}`),
  update: (id, formData) => api.put(`/movements/${id}`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  }),
  delete: (id) => api.delete(`/movements/${id}`),
  deleteAttachment: (id) => api.delete(`/movements/${id}/attachment`),
  getStats: (params) => api.get('/movements/stats', { params }),
  exportCSV: (params) => api.get('/movements/export/csv', { 
    params,
    responseType: 'blob' 
  }),
  exportJSON: (params) => api.get('/movements/export/json', { 
    params,
    responseType: 'blob' 
  }),
  import: (formData) => api.post('/movements/import', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  }),
    importCSV: (formData) => api.post('/movements/import/csv', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  }),
};

// Tags
export const tagsAPI = {
  getAll: () => api.get('/tags'),
};

// Admin
export const adminAPI = {
  getUsers: (params) => api.get('/admin/users', { params }),
  changeUserRole: (data) => api.put('/admin/users/role', data),
  updateUser: (data) => api.put('/admin/users/update', data),
  deleteUser: (data) => api.delete('/admin/users', { data }),
  deleteUserMovement: (data) => api.delete('/admin/movements', { data }),
  getActivityLog: (params) => api.get('/admin/activity-log', { params }),
  getStats: () => api.get('/admin/stats'),
  createTag: (data) => api.post('/admin/tags', data),
  updateTag: (id, data) => api.put(`/admin/tags/${id}`, data),
  deleteTag: (id) => api.delete(`/admin/tags/${id}`),
};

export default api;