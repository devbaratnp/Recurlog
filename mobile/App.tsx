import { useCallback, useEffect, useState, useRef } from 'react';
import { StyleSheet, View, Platform } from 'react-native';
import { StatusBar } from 'expo-status-bar';
import * as SplashScreen from 'expo-splash-screen';
import * as Font from 'expo-font';
import * as Notifications from 'expo-notifications';
import {
  Poppins_400Regular,
  Poppins_500Medium,
  Poppins_600SemiBold,
  Poppins_700Bold,
  Poppins_800ExtraBold,
} from '@expo-google-fonts/poppins';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { AppNavigator } from './src/navigation/AppNavigator';
import Toast from './src/components/Toast';
import { COLORS } from './src/constants/theme';
import { pushTokenApi } from './src/api/client';
import { useAuthStore } from './src/store/authStore';

SplashScreen.preventAutoHideAsync();

// Configure notification handler — controls how notifications are shown
Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldShowAlert: true,
    shouldPlaySound: true,
    shouldSetBadge: true,
    shouldShowBanner: true,
    shouldShowList: true,
  }),
});

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 2,
      staleTime: 1000 * 60 * 2,
    },
  },
});

async function loadFonts() {
  await Font.loadAsync({
    Poppins_400Regular,
    Poppins_500Medium,
    Poppins_600SemiBold,
    Poppins_700Bold,
    Poppins_800ExtraBold,
  });
}

async function registerForPushNotifications() {
  const { status: existingStatus } = await Notifications.getPermissionsAsync();
  let finalStatus = existingStatus;

  if (existingStatus !== 'granted') {
    const { status } = await Notifications.requestPermissionsAsync();
    finalStatus = status;
  }

  if (finalStatus !== 'granted') return;

  const tokenData = await Notifications.getExpoPushTokenAsync();
  const token = tokenData.data;

  const { user } = useAuthStore.getState();
  if (!token || !user) return;

  try {
    await pushTokenApi.register({
      platform: Platform.OS === 'ios' ? 'ios' : 'android',
      expoToken: token,
      deviceName: Platform.OS === 'ios' ? 'iOS' : 'Android',
    });
  } catch {}

  // Android notification channel with sound
  if (Platform.OS === 'android') {
    await Notifications.setNotificationChannelAsync('recurlog-default', {
      name: 'Default Notifications',
      importance: Notifications.AndroidImportance.HIGH,
      vibrationPattern: [0, 250, 250, 250],
      lightColor: '#1DB954',
      sound: 'notification.wav',
    });
  }
}

export default function App() {
  const [appIsReady, setAppIsReady] = useState(false);
  const readyRef = useRef(false);
  const notificationResponseListener = useRef<Notifications.Subscription | null>(null);

  useEffect(() => {
    async function prepare() {
      try {
        await Promise.race([
          loadFonts(),
          new Promise((_, reject) =>
            setTimeout(() => reject(new Error('Font load timeout')), 10000)
          ),
        ]);
      } catch {
      } finally {
        if (!readyRef.current) {
          readyRef.current = true;
          setAppIsReady(true);
          try { await SplashScreen.hideAsync(); } catch {}
        }
      }
    }
    prepare();

    // Register for push notifications after fonts load
    registerForPushNotifications();

    // Handle notification taps — navigate based on type
    notificationResponseListener.current =
      Notifications.addNotificationResponseReceivedListener((response) => {
        const data = response.notification.request.content.data;
        // Navigation handled by MainNavigator via event
      });

    return () => {
      if (notificationResponseListener.current) {
        Notifications.removeNotificationSubscription(notificationResponseListener.current);
      }
    };
  }, []);

  if (!appIsReady) {
    return (
      <View style={styles.loading}>
        <StatusBar style="light" />
      </View>
    );
  }

  return (
    <GestureHandlerRootView style={styles.root}>
      <SafeAreaProvider>
        <QueryClientProvider client={queryClient}>
          <StatusBar style="light" />
          <AppNavigator />
          <Toast />
        </QueryClientProvider>
      </SafeAreaProvider>
    </GestureHandlerRootView>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
    backgroundColor: COLORS.navy,
  },
  loading: {
    flex: 1,
    backgroundColor: COLORS.navy,
  },
});
