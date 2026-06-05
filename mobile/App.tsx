import { useCallback, useEffect, useState, useRef } from 'react';
import { StyleSheet, View } from 'react-native';
import { StatusBar } from 'expo-status-bar';
import * as SplashScreen from 'expo-splash-screen';
import * as Font from 'expo-font';
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
import { COLORS } from './src/constants/theme';

SplashScreen.preventAutoHideAsync();

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

export default function App() {
  const [appIsReady, setAppIsReady] = useState(false);
  const readyRef = useRef(false);

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
