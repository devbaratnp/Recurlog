import { create } from 'zustand';
import { notificationsApi } from '../api/client';
import type { Notification } from '../types';

interface NotificationState {
  notifications: Notification[];
  unreadCount: number;
  isLoading: boolean;
  pollingInterval: ReturnType<typeof setInterval> | null;
  fetchNotifications: () => Promise<void>;
  startPolling: (ms?: number) => void;
  stopPolling: () => void;
  markAsRead: (id: number) => Promise<void>;
  markAllRead: () => Promise<void>;
}

export const useNotificationStore = create<NotificationState>((set, get) => ({
  notifications: [],
  unreadCount: 0,
  isLoading: false,
  pollingInterval: null,

  fetchNotifications: async () => {
    set({ isLoading: true });
    try {
      const { data } = await notificationsApi.list();
      const list = Array.isArray(data.data) ? data.data : [];
      const unread = list.filter((n) => !n.isRead).length;
      set({ notifications: list, unreadCount: unread, isLoading: false });
    } catch {
      set({ isLoading: false });
    }
  },

  startPolling: (ms = 30000) => {
    const existing = get().pollingInterval;
    if (existing) return;
    const id = setInterval(() => { get().fetchNotifications(); }, ms);
    set({ pollingInterval: id });
  },

  stopPolling: () => {
    const existing = get().pollingInterval;
    if (existing) clearInterval(existing);
    set({ pollingInterval: null });
  },

  markAsRead: async (id: number) => {
    try {
      await notificationsApi.markRead(id);
      const notifications = get().notifications.map((n) =>
        n.id === id ? { ...n, isRead: true } : n
      );
      const unreadCount = notifications.filter((n) => !n.isRead).length;
      set({ notifications, unreadCount });
    } catch (e) { console.error('Failed to mark notification as read', e); }
  },

  markAllRead: async () => {
    try {
      await notificationsApi.markAllRead();
      const notifications = get().notifications.map((n) => ({ ...n, isRead: true }));
      set({ notifications, unreadCount: 0 });
    } catch (e) { console.error('Failed to mark all notifications as read', e); }
  },
}));
