import { todayISO } from './helpers';

const BASE_URL = 'https://recurlog.isoftro.com/api';

let authToken: string | null = null;

export function setAuthToken(token: string | null) {
  authToken = token;
}

async function request<T>(path: string, init?: RequestInit): Promise<T> {
  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    ...(init?.headers as Record<string, string> | undefined),
  };
  if (authToken) headers.Authorization = `Bearer ${authToken}`;

  const res = await fetch(`${BASE_URL}${path}`, { ...init, headers });
  const text = await res.text();
  let body: any = null;
  if (text) {
    try { body = JSON.parse(text); } catch { body = text; }
  }
  if (!res.ok) {
    const msg =
      (body && typeof body === 'object' && body.error) ||
      (typeof body === 'string' && body) ||
      `Request failed: ${res.status}`;
    throw new Error(msg);
  }
  return body as T;
}

function isoDate(s?: string | null): string {
  if (!s) return '';
  return s.includes('T') ? s : s.replace(' ', 'T');
}

function tomorrowISO(): string {
  const d = new Date();
  d.setDate(d.getDate() + 1);
  return d.toISOString().slice(0, 10);
}

interface RawCustomer {
  id: number; name: string; address: string | null; phone: string | null;
  services_for: string[] | string | null; location_lat: number | null;
  location_lng: number | null; area: string | null; created_at: string;
}

interface Customer {
  id: number; name: string; address: string; phone: string;
  servicesFor: string[]; area: string;
  locationLat: number | null; locationLng: number | null;
}

function mapCustomer(c: RawCustomer): Customer {
  let services: string[] = [];
  if (Array.isArray(c.services_for)) services = c.services_for;
  else if (typeof c.services_for === 'string' && c.services_for) {
    try { const parsed = JSON.parse(c.services_for); if (Array.isArray(parsed)) services = parsed; } catch {}
  }
  return {
    id: c.id,
    name: c.name,
    address: c.address ?? '',
    phone: c.phone ?? '',
    area: c.area ?? '',
    servicesFor: services,
    locationLat: c.location_lat,
    locationLng: c.location_lng,
  };
}

interface RawStaff {
  id: number; name: string; phone: string; avatar: string | null;
  active_tasks: number; total?: number; completed?: number; missed?: number;
  pending?: number; completionRate?: number;
}

interface StaffMember {
  id: number; name: string; phone: string; avatar: string;
  activeTasks: number; total: number; completed: number; missed: number;
  completionRate: number;
}

function mapStaff(s: RawStaff): StaffMember {
  const pending = s.pending ?? s.active_tasks ?? 0;
  return {
    id: s.id,
    name: s.name,
    phone: s.phone ?? '',
    avatar: s.avatar ?? '',
    activeTasks: pending,
    total: s.total ?? 0,
    completed: s.completed ?? 0,
    missed: s.missed ?? 0,
    completionRate: s.completionRate ?? 0,
  };
}

interface Category { id: number; name: string; color: string; }

interface RawService {
  id: number; customer_id: number; category_id: number | null;
  service_for: string | null; title: string | null; is_recurring: 0 | 1 | boolean;
  isRecurring?: boolean; first_scheduled_date: string | null;
  assigned_to: number | null; notes: string | null;
  recurrence_value: number | null; recurrence_unit: string | null;
  recurrence_repeat_from: string | null; created_at: string;
}

interface Service {
  id: number; customerId: number; categoryId: number | null;
  categoryName: string; serviceFor: string; title: string;
  isRecurring: boolean; firstScheduledDate: string;
  assignedTo: number | null; assignedStaff: string;
  notes: string; nextDue: string; status: string;
  recurrenceValue: number | null; recurrenceUnit: string | null;
  recurrenceRepeatFrom: string | null;
}

function mapService(
  s: RawService,
  categoryMap: Map<number, Category>,
  staffMap: Map<number, StaffMember>,
  nextDueByService: Map<number, string>,
): Service {
  const cat = s.category_id != null ? categoryMap.get(s.category_id) : undefined;
  const staff = s.assigned_to != null ? staffMap.get(s.assigned_to) : undefined;
  const nextDue = nextDueByService.get(s.id) ?? s.first_scheduled_date ?? '';
  return {
    id: s.id,
    customerId: s.customer_id,
    categoryId: s.category_id,
    categoryName: cat?.name ?? '',
    serviceFor: s.service_for ?? '',
    title: s.title || (cat?.name ?? 'Service'),
    isRecurring: Boolean(s.isRecurring ?? s.is_recurring),
    firstScheduledDate: s.first_scheduled_date ?? '',
    assignedTo: s.assigned_to,
    assignedStaff: staff?.name ?? '',
    notes: s.notes ?? '',
    nextDue,
    status: Boolean(s.isRecurring ?? s.is_recurring) ? 'active' : 'one-time',
    recurrenceValue: s.recurrence_value,
    recurrenceUnit: s.recurrence_unit,
    recurrenceRepeatFrom: s.recurrence_repeat_from,
  };
}

interface RawTask {
  id: number; service_id: number | null; customer_id: number | null;
  title: string; status: string; scheduled_date: string | null;
  completed_date: string | null; assigned_to: number | null;
  notes: string | null; category_id: number | null; created_at: string;
  customer_name?: string; staff_name?: string;
}

interface Task {
  id: number; serviceId: number | null; customerId: number | null;
  title: string; status: string; scheduledDate: string;
  completedDate: string; assignedTo: number | null;
  staffName: string; customerName: string; notes: string;
  categoryId: number | null;
}

function mapTask(
  t: RawTask,
  customerMap: Map<number, Customer>,
  staffMap: Map<number, StaffMember>,
): Task {
  const customerName =
    t.customer_name ||
    (t.customer_id != null ? customerMap.get(t.customer_id)?.name : undefined) ||
    '';
  const staffName =
    t.staff_name ||
    (t.assigned_to != null ? staffMap.get(t.assigned_to)?.name : undefined) ||
    '';
  return {
    id: t.id,
    serviceId: t.service_id,
    customerId: t.customer_id,
    title: t.title,
    status: t.status,
    scheduledDate: t.scheduled_date ?? '',
    completedDate: t.completed_date ?? '',
    assignedTo: t.assigned_to,
    staffName,
    customerName,
    notes: t.notes ?? '',
    categoryId: t.category_id,
  };
}

interface RawNotification {
  id: number; text: string; type: string; related_id: number | null;
  is_read: 0 | 1 | boolean; isRead?: boolean; created_at: string;
}

interface Notification {
  id: number; text: string; type: string; relatedId: number | null;
  isRead: boolean; createdAt: string;
}

function mapNotification(n: RawNotification): Notification {
  return {
    id: n.id,
    text: n.text,
    type: n.type,
    relatedId: n.related_id,
    isRead: Boolean(n.isRead ?? n.is_read),
    createdAt: isoDate(n.created_at),
  };
}

interface DashboardData {
  totalCustomers: number;
  tasksToday: number;
  missedTasks: number;
  activeStaff: number;
  todayTasks: Task[];
  recentActivity: Notification[];
}

interface CustomerDetailBundle {
  customer: Customer;
  services: Service[];
  tasks: Task[];
}

interface StaffDetailBundle {
  staff: StaffMember;
  stats: { total: number; completed: number; missed: number; completionRate: number };
  tasks: Task[];
}

interface LoginResponse {
  user: { id: number; name: string; email: string; avatar?: string };
  token: string;
}

interface CompleteTaskResponse {
  task: Task | null;
  nextTask: Task | null;
}

export const api = {
  login: (email: string, password: string) =>
    request<LoginResponse>('/auth.php', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    }),

  getDashboard: async (): Promise<DashboardData> => {
    const [raw, customers, staff] = await Promise.all([
      request<any>('/dashboard.php'),
      api.getCustomersRaw().catch(() => [] as Customer[]),
      api.getStaff().catch(() => [] as StaffMember[]),
    ]);
    const customerMap = new Map(customers.map((c) => [c.id, c]));
    const staffMap = new Map(staff.map((s) => [s.id, s]));
    return {
      totalCustomers: raw.totalCustomers ?? 0,
      tasksToday: raw.todayTasks ?? 0,
      missedTasks: raw.missedTasks ?? 0,
      activeStaff: raw.totalStaff ?? 0,
      todayTasks: (raw.todaysSchedule ?? []).map((t: RawTask) =>
        mapTask(t, customerMap, staffMap),
      ),
      recentActivity: (raw.recentNotifications ?? []).map(mapNotification),
    };
  },

  getCustomers: async (): Promise<Customer[]> => {
    const raw = await request<RawCustomer[]>('/customers.php');
    return raw.map(mapCustomer);
  },

  getCustomersRaw: async (): Promise<Customer[]> => {
    return api.getCustomers();
  },

  getCustomer: async (id: number | string): Promise<CustomerDetailBundle> => {
    const [customer, services, tasks, staff, categories] = await Promise.all([
      request<RawCustomer>(`/customers.php?id=${id}`),
      request<RawService[]>(`/services.php?customerId=${id}`),
      request<RawTask[]>(`/tasks.php?customerId=${id}`),
      api.getStaff(),
      api.getCategories(),
    ]);
    const staffMap = new Map(staff.map((s) => [s.id, s]));
    const categoryMap = new Map(categories.map((c) => [c.id, c]));
    const customerMap = new Map<number, Customer>([[customer.id, mapCustomer(customer)]]);

    const nextDueByService = new Map<number, string>();
    for (const t of tasks) {
      if (t.status === 'pending' && t.service_id != null && t.scheduled_date) {
        const cur = nextDueByService.get(t.service_id);
        if (!cur || t.scheduled_date < cur) {
          nextDueByService.set(t.service_id, t.scheduled_date);
        }
      }
    }

    return {
      customer: mapCustomer(customer),
      services: services.map((s) => mapService(s, categoryMap, staffMap, nextDueByService)),
      tasks: tasks.map((t) => mapTask(t, customerMap, staffMap)),
    };
  },

  createCustomer: async (data: {
    name: string; address: string; phone: string; area?: string;
    servicesFor: string[]; locationLat?: number | null; locationLng?: number | null;
  }): Promise<Customer> => {
    const body = {
      name: data.name,
      address: data.address,
      phone: data.phone,
      area: data.area ?? '',
      services_for: data.servicesFor,
      location: {
        lat: data.locationLat ?? null,
        lng: data.locationLng ?? null,
      },
    };
    const raw = await request<RawCustomer>('/customers.php', {
      method: 'POST',
      body: JSON.stringify(body),
    });
    return mapCustomer(raw);
  },

  getServices: async (customerId?: number): Promise<Service[]> => {
    const qs = customerId ? `?customerId=${customerId}` : '';
    const [raw, categories, staff, tasks] = await Promise.all([
      request<RawService[]>(`/services.php${qs}`),
      api.getCategories(),
      api.getStaff(),
      customerId
        ? request<RawTask[]>(`/tasks.php?customerId=${customerId}`)
        : request<RawTask[]>('/tasks.php'),
    ]);
    const categoryMap = new Map(categories.map((c) => [c.id, c]));
    const staffMap = new Map(staff.map((s) => [s.id, s]));
    const nextDueByService = new Map<number, string>();
    for (const t of tasks) {
      if (t.status === 'pending' && t.service_id != null && t.scheduled_date) {
        const cur = nextDueByService.get(t.service_id);
        if (!cur || t.scheduled_date < cur) nextDueByService.set(t.service_id, t.scheduled_date);
      }
    }
    return raw.map((s) => mapService(s, categoryMap, staffMap, nextDueByService));
  },

  createService: async (data: {
    customerId: number; categoryId: number | null; serviceFor: string;
    title?: string; isRecurring: boolean; firstScheduledDate: string;
    assignedTo: number | null; notes?: string;
    recurrence?: { value: number; unit: string; repeatFrom: string };
  }): Promise<Service> => {
    const body: any = {
      customer_id: data.customerId,
      category_id: data.categoryId,
      service_for: data.serviceFor,
      title: data.title ?? '',
      is_recurring: data.isRecurring,
      first_scheduled_date: data.firstScheduledDate,
      assigned_to: data.assignedTo,
      notes: data.notes ?? '',
    };
    if (data.isRecurring && data.recurrence) {
      body.recurrence = {
        value: data.recurrence.value,
        unit: data.recurrence.unit,
        repeat_from: data.recurrence.repeatFrom,
      };
    }
    const [raw, categories, staff] = await Promise.all([
      request<RawService>('/services.php', { method: 'POST', body: JSON.stringify(body) }),
      api.getCategories(),
      api.getStaff(),
    ]);
    const categoryMap = new Map(categories.map((c) => [c.id, c]));
    const staffMap = new Map(staff.map((s) => [s.id, s]));
    return mapService(raw, categoryMap, staffMap, new Map());
  },

  getTasks: async (params?: {
    tab?: 'today' | 'upcoming' | 'missed';
    customerId?: number;
    staffId?: number;
    serviceId?: number;
    status?: string;
  }): Promise<Task[]> => {
    const sp = new URLSearchParams();
    if (params?.customerId) sp.set('customerId', String(params.customerId));
    if (params?.staffId) sp.set('staffId', String(params.staffId));
    if (params?.serviceId) sp.set('serviceId', String(params.serviceId));
    if (params?.status) sp.set('status', params.status);
    if (params?.tab === 'today') sp.set('date', todayISO());
    else if (params?.tab === 'upcoming') {
      sp.set('status', 'pending');
      sp.set('startDate', tomorrowISO());
    } else if (params?.tab === 'missed') {
      sp.set('status', 'missed');
    }
    const qs = sp.toString();
    const [tasks, customers, staff] = await Promise.all([
      request<RawTask[]>(`/tasks.php${qs ? '?' + qs : ''}`),
      api.getCustomers(),
      api.getStaff(),
    ]);
    const customerMap = new Map(customers.map((c) => [c.id, c]));
    const staffMap = new Map(staff.map((s) => [s.id, s]));
    return tasks.map((t) => mapTask(t, customerMap, staffMap));
  },

  completeTask: async (id: number, completedDate: string, notes: string): Promise<CompleteTaskResponse> => {
    const raw = await request<{ task: RawTask | null; nextTask: RawTask | null }>(
      '/tasks.php?action=complete',
      {
        method: 'PUT',
        body: JSON.stringify({ id, completed_date: completedDate, notes }),
      },
    );
    const empty = new Map<number, any>();
    return {
      task: raw.task ? mapTask(raw.task, empty, empty) : null,
      nextTask: raw.nextTask ? mapTask(raw.nextTask, empty, empty) : null,
    };
  },

  getStaff: async (): Promise<StaffMember[]> => {
    const raw = await request<RawStaff[]>('/staff.php');
    return raw.map(mapStaff);
  },

  getStaffMember: async (id: number | string): Promise<StaffDetailBundle> => {
    const [staffRaw, tasks, customers] = await Promise.all([
      request<RawStaff>(`/staff.php?id=${id}`),
      request<RawTask[]>(`/tasks.php?staffId=${id}`),
      api.getCustomers(),
    ]);
    const staff = mapStaff(staffRaw);
    const customerMap = new Map(customers.map((c) => [c.id, c]));
    const staffMap = new Map<number, StaffMember>([[staff.id, staff]]);
    return {
      staff,
      stats: {
        total: staff.total,
        completed: staff.completed,
        missed: staff.missed,
        completionRate: staff.completionRate,
      },
      tasks: tasks.map((t) => mapTask(t, customerMap, staffMap)),
    };
  },

  getCategories: async (): Promise<Category[]> => {
    return request<Category[]>('/categories.php');
  },

  getNotifications: async (): Promise<{ notifications: Notification[]; unreadCount: number }> => {
    const raw = await request<{ notifications: RawNotification[]; unreadCount: number }>(
      '/notifications.php',
    );
    return {
      notifications: (raw.notifications ?? []).map(mapNotification),
      unreadCount: raw.unreadCount ?? 0,
    };
  },

  markAllRead: () =>
    request<{ success: boolean }>('/notifications.php?action=markAllRead', { method: 'PUT' }),
};

export type {
  Customer, Service, Task, StaffMember, Category, Notification,
  DashboardData, CustomerDetailBundle, StaffDetailBundle,
};
