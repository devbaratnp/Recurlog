import axios, { AxiosError, InternalAxiosRequestConfig } from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { API_URL, TOKEN_KEY, REFRESH_TOKEN_KEY } from '../constants/config';
import type { AuthResponse, ApiResponse, PaginatedResponse, Customer, Service, Task, Staff, Category, Order, Notification, Locality, ServiceType } from '../types';

interface FailedRequest {
  resolve: (token: string) => void;
  reject: (error: unknown) => void;
}

let isRefreshing = false;
let failedQueue: FailedRequest[] = [];

const processQueue = (error: unknown, token: string | null) => {
  failedQueue.forEach((prom) => {
    if (error) prom.reject(error);
    else if (token) prom.resolve(token);
  });
  failedQueue = [];
};

const api = axios.create({
  baseURL: API_URL,
  timeout: 15000,
  headers: { 'Content-Type': 'application/json' },
});

api.interceptors.request.use(async (config: InternalAxiosRequestConfig) => {
  const token = await AsyncStorage.getItem(TOKEN_KEY);
  if (token && config.headers) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const originalRequest = error.config as InternalAxiosRequestConfig & { _retry?: boolean };
    if (originalRequest.url?.includes('action=login')) {
      return Promise.reject(error);
    }
    if (error.response?.status === 401 && !originalRequest._retry) {
      if (isRefreshing) {
        return new Promise((resolve, reject) => {
          failedQueue.push({
            resolve: (token: string) => {
              if (originalRequest.headers) {
                originalRequest.headers.Authorization = `Bearer ${token}`;
              }
              resolve(api(originalRequest));
            },
            reject,
          });
        });
      }
      originalRequest._retry = true;
      isRefreshing = true;
      try {
        const refreshToken = await AsyncStorage.getItem(REFRESH_TOKEN_KEY);
        if (!refreshToken) throw new Error('No refresh token');
        const { data } = await axios.post<ApiResponse<AuthResponse>>(
          `${API_URL}/auth.php?action=refresh`,
          { refreshToken }
        );
        const { token, refreshToken: newRefresh } = data.data;
        await AsyncStorage.setItem(TOKEN_KEY, token);
        await AsyncStorage.setItem(REFRESH_TOKEN_KEY, newRefresh);
        processQueue(null, token);
        if (originalRequest.headers) {
          originalRequest.headers.Authorization = `Bearer ${token}`;
        }
        return api(originalRequest);
      } catch (refreshError) {
        processQueue(refreshError, null);
        await AsyncStorage.multiRemove([TOKEN_KEY, REFRESH_TOKEN_KEY, 'recurlog_user']);
        return Promise.reject(refreshError);
      } finally {
        isRefreshing = false;
      }
    }
    return Promise.reject(error);
  }
);

// ========== AUTH ==========
export const authApi = {
  login: (email: string, password: string) =>
    api.post<ApiResponse<AuthResponse>>('/auth.php?action=login', { email, password }),

  check: () => api.get<ApiResponse<{ authed: boolean; user: any }>>('/auth.php?action=check'),

  me: () => api.get<ApiResponse<any>>('/auth.php?action=me'),

  refresh: (refreshToken: string) =>
    api.post<ApiResponse<AuthResponse>>('/auth.php?action=refresh', { refreshToken }),
};

// ========== CUSTOMERS ==========
export const customersApi = {
  list: (params?: { page?: number; per_page?: number; search?: string }) =>
    api.get<ApiResponse<Customer[]> | PaginatedResponse<Customer>>('/customers.php', { params }),
  get: (id: number) => api.get<ApiResponse<Customer>>(`/customers.php?id=${id}`),
  create: (data: Partial<Customer>) => api.post<ApiResponse<Customer>>('/customers.php', data),
  update: (id: number, data: Partial<Customer>) => api.put<ApiResponse<Customer>>(`/customers.php?id=${id}`, data),
  delete: (id: number) => api.delete(`/customers.php?id=${id}`),
};

// ========== SERVICES ==========
export const servicesApi = {
  list: (params?: { customer_id?: number; category_id?: number; is_recurring?: number }) =>
    api.get<ApiResponse<Service[]>>('/services.php', { params }),
  get: (id: number) => api.get<ApiResponse<Service>>(`/services.php?id=${id}`),
  create: (data: Partial<Service>) => api.post<ApiResponse<Service>>('/services.php', data),
  update: (id: number, data: Partial<Service>) => api.put<ApiResponse<Service>>(`/services.php?id=${id}`, data),
  delete: (id: number) => api.delete(`/services.php?id=${id}`),
};

// ========== TASKS ==========
export const tasksApi = {
  list: (params?: {
    status?: string;
    customer_id?: number;
    assigned_to?: number;
    service_id?: number;
    scheduled_date?: string;
    start_date?: string;
    end_date?: string;
  }) => api.get<ApiResponse<Task[]> | PaginatedResponse<Task>>('/tasks.php', { params }),
  get: (id: number) => api.get<ApiResponse<Task>>(`/tasks.php?id=${id}`),
  create: (data: Partial<Task>) => api.post<ApiResponse<Task>>('/tasks.php', data),
  update: (id: number, data: Partial<Task>) => api.put<ApiResponse<Task>>(`/tasks.php?id=${id}`, data),
  delete: (id: number) => api.delete(`/tasks.php?id=${id}`),
  complete: (id: number, date: string, notes: string) =>
    api.put<ApiResponse<Task>>(`/tasks.php?id=${id}`, { status: 'completed', completedDate: date, notes }),
};

// ========== STAFF ==========
export const staffApi = {
  list: () => api.get<ApiResponse<Staff[]>>('/staff.php'),
  get: (id: number) => api.get<ApiResponse<Staff>>(`/staff.php?id=${id}`),
  create: (data: Partial<Staff>) => api.post<ApiResponse<Staff>>('/staff.php', data),
  update: (id: number, data: Partial<Staff>) => api.put<ApiResponse<Staff>>(`/staff.php?id=${id}`, data),
  delete: (id: number) => api.delete(`/staff.php?id=${id}`),
};

// ========== CATEGORIES ==========
export const categoriesApi = {
  list: () => api.get<ApiResponse<Category[]>>('/categories.php'),
  get: (id: number) => api.get<ApiResponse<Category>>(`/categories.php?id=${id}`),
};

// ========== ORDERS ==========
export const   ordersApi = {
  list: (params?: { status?: string; customer_id?: number; priority?: string; assigned_to?: string }) =>
    api.get<ApiResponse<Order[]> | PaginatedResponse<Order>>('/orders.php', { params }),
  get: (id: number) => api.get<ApiResponse<Order>>(`/orders.php?id=${id}`),
  create: (data: Partial<Order>) => api.post<ApiResponse<Order>>('/orders.php', data),
  update: (id: number, data: Partial<Order>) => api.put<ApiResponse<Order>>(`/orders.php?id=${id}`, data),
  delete: (id: number) => api.delete(`/orders.php?id=${id}`),
};

// ========== NOTIFICATIONS ==========
export const notificationsApi = {
  list: (params?: { is_read?: number }) =>
    api.get<ApiResponse<Notification[]>>('/notifications.php', { params }),
  get: (id: number) => api.get<ApiResponse<Notification>>(`/notifications.php?id=${id}`),
  create: (data: Partial<Notification>) => api.post<ApiResponse<Notification>>('/notifications.php', data),
  markRead: (id: number) => api.put<ApiResponse<Notification>>(`/notifications.php?id=${id}`, { isRead: 1 }),
  markAllRead: () => api.put<ApiResponse<any>>('/notifications.php?action=mark_all_read', {}),
};

// ========== LOCALITIES ==========
export const localitiesApi = {
  list: () => api.get<ApiResponse<Locality[]>>('/localities.php'),
};

// ========== SERVICE TYPES ==========
export const serviceTypesApi = {
  list: () => api.get<ApiResponse<ServiceType[]>>('/service_types.php'),
};

// ========== PUSH TOKENS ==========
export const pushTokenApi = {
  register: (data: { platform: 'android' | 'ios'; expoToken: string; deviceName?: string; appVersion?: string }) =>
    api.post<ApiResponse<any>>('/push_register.php', data),
  unregister: (expoToken: string) =>
    api.delete<ApiResponse<any>>('/push_register.php', { data: { expoToken } }),
  unregisterAll: () =>
    api.delete<ApiResponse<any>>('/push_register.php', { data: { all: true } }),
  updatePreferences: (notificationsEnabled: boolean, tokenId?: number) =>
    api.put<ApiResponse<any>>('/push_register.php', { notificationsEnabled, tokenId }),
  listDevices: () =>
    api.get<ApiResponse<any[]>>('/push_register.php'),
};

export default api;
