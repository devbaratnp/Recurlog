import { create } from 'zustand';
import { Audio } from 'expo-av';
import { notificationsApi, pushTokenApi } from '../api/client';
import type { Notification } from '../types';

let soundObject: Audio.Sound | null = null;

async function playNotificationSound() {
  try {
    if (soundObject) {
      await soundObject.unloadAsync();
      soundObject = null;
    }
    const { sound } = await Audio.Sound.createAsync(
      require('../assets/notification.wav'),
      { shouldPlay: true, volume: 0.8 }
    );
    soundObject = sound;
    sound.setOnPlaybackStatusUpdate((status) => {
      if (status && 'didJustFinish' in status && status.didJustFinish) {
        sound.unloadAsync();
        soundObject = null;
      }
    });
  } catch {
    // Sound file not available yet — play silently
  }
}

interface NotificationState {
  notifications: Notification[];
  unreadCount: number;
  isLoading: boolean;
  notificationsEnabled: boolean;
  pushToken: string | null;
  pollingInterval: ReturnType<typeof setInterval> | null;
  fetchNotifications: () => Promise<void>;
  startPolling: (ms?: number) => void;
  stopPolling: () => void;
  markAsRead: (id: number) => Promise<void>;
  markAllRead: () => Promise<void>;
  setNotificationsEnabled: (enabled: boolean) => Promise<void>;
  setPushToken: (token: string | null) => void;
  playSound: () => void;
}

export const useNotificationStore = create<NotificationState>((set, get) => ({
  notifications: [],
  unreadCount: 0,
  isLoading: false,
  notificationsEnabled: true,
  pushToken: null,
  pollingInterval: null,

  fetchNotifications: async () => {
    set({ isLoading: true });
    try {
      const { data } = await notificationsApi.list();
      const list = Array.isArray(data.data) ? data.data : [];
      const prevUnread = get().unreadCount;
      const unread = list.filter((n) => !n.isRead).length;
      set({ notifications: list, unreadCount: unread, isLoading: false });

      // Play sound if new unread notifications arrived
      if (unread > prevUnread && get().notificationsEnabled) {
        playNotificationSound();
      }
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

  setNotificationsEnabled: async (enabled: boolean) => {
    set({ notificationsEnabled: enabled });
    try {
      await pushTokenApi.updatePreferences(enabled);
    } catch {}
  },

  setPushToken: (token: string | null) => {
    set({ pushToken: token });
  },

  playSound: () => {
    if (get().notificationsEnabled) {
      playNotificationSound();
    }
  },
}));
