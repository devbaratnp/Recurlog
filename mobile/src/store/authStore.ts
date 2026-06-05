import { create } from 'zustand';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { authApi } from '../api/client';
import { TOKEN_KEY, REFRESH_TOKEN_KEY, USER_KEY } from '../constants/config';
import type { User } from '../types';

interface AuthState {
  user: User | null;
  token: string | null;
  isLoading: boolean;
  isInitialized: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  restoreSession: () => Promise<void>;
}

export const useAuthStore = create<AuthState>((set, get) => ({
  user: null,
  token: null,
  isLoading: false,
  isInitialized: false,

  login: async (email: string, password: string) => {
    set({ isLoading: true });
    try {
      const { data } = await authApi.login(email, password);
      const { token, refreshToken, user } = data.data;
      await AsyncStorage.multiSet([
        [TOKEN_KEY, token],
        [REFRESH_TOKEN_KEY, refreshToken],
        [USER_KEY, JSON.stringify(user)],
      ]);
      set({ user, token, isLoading: false });
    } catch (error) {
      set({ isLoading: false });
      throw error;
    }
  },

  logout: async () => {
    await AsyncStorage.multiRemove([TOKEN_KEY, REFRESH_TOKEN_KEY, USER_KEY]);
    set({ user: null, token: null });
  },

  restoreSession: async () => {
    try {
      const [[, token], [, userStr]] = await AsyncStorage.multiGet([TOKEN_KEY, USER_KEY]);
      if (token && userStr) {
        const user = JSON.parse(userStr) as User;
        set({ user, token, isInitialized: true });
        try {
          const { data } = await authApi.me();
          const freshUser = data.data;
          await AsyncStorage.setItem(USER_KEY, JSON.stringify(freshUser));
          set({ user: freshUser, isInitialized: true });
        } catch (error: any) {
          if (error?.response?.status === 401) {
            set({ user: null, token: null, isInitialized: true });
            await AsyncStorage.multiRemove([TOKEN_KEY, REFRESH_TOKEN_KEY, USER_KEY]);
          } else {
            set({ isInitialized: true });
          }
        }
      } else {
        set({ isInitialized: true });
      }
    } catch {
      set({ isInitialized: true });
    }
  },
}));
