export interface User {
  id: number;
  name: string;
  email: string;
  role: 'admin' | 'staff';
  staffId: number | null;
}

export interface Customer {
  id: number;
  name: string;
  address: string;
  area: string;
  phone: string;
  servicesFor: string[];
  location: { lat: number; lng: number } | null;
}

export interface Category {
  id: number;
  name: string;
  color: string;
}

export interface Staff {
  id: number;
  name: string;
  phone: string;
  avatar: string;
  activeTasks: number;
}

export interface Recurrence {
  value: number;
  unit: 'days' | 'weeks' | 'months' | 'years';
  repeatFrom: 'last-done' | 'fixed-schedule';
}

export interface Service {
  id: number;
  customerId: number;
  categoryId: number;
  serviceFor: string;
  title: string;
  problem: string;
  isRecurring: boolean;
  firstScheduledDate: string;
  assignedTo: number;
  notes: string;
  recurrence: Recurrence | null;
}

export type TaskStatus = 'pending' | 'completed' | 'missed';

export interface Task {
  id: number;
  serviceId: number;
  customerId: number;
  title: string;
  status: TaskStatus;
  scheduledDate: string;
  completedDate: string | null;
  assignedTo: number;
  notes: string;
  categoryId: number;
  completedBy: string | null;
  receivedName: string | null;
  receivedContact: string | null;
  signature: string | null;
  customerName?: string;
  assignedStaffName?: string;
  serviceProblem?: string;
}

export type OrderStatus = 'pending' | 'assigned' | 'completed' | 'cancelled';
export type OrderPriority = 'urgent' | 'normal';

export interface Order {
  id: number;
  customerId: number;
  customerName: string;
  serviceFor: string;
  problem: string;
  status: OrderStatus;
  priority: OrderPriority;
  assignedTo: number | null;
  assignedStaffName: string | null;
  scheduledDate: string | null;
  completedDate: string | null;
  notes: string;
  dispatchDate: string | null;
  dispatchBy: string | null;
  receivedName: string | null;
  receivedContact: string | null;
  signature: string | null;
}

export interface Notification {
  id: number;
  text: string;
  type: 'task_completed' | 'task_missed' | 'service_added' | 'customer_added' | 'order_created' | 'order_assigned' | 'order_completed';
  relatedId: number | null;
  isRead: boolean;
  createdAt: string;
}

export interface Locality {
  id: number;
  name: string;
}

export interface ServiceType {
  id: number;
  name: string;
}

export interface AuthResponse {
  token: string;
  refreshToken: string;
  user: User;
}

export interface PaginatedResponse<T> {
  success: boolean;
  data: T[];
  pagination: {
    page: number;
    perPage: number;
    total: number;
    totalPages: number;
  };
}

export interface ApiResponse<T> {
  success: boolean;
  data: T;
}
