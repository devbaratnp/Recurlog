import { useEffect, useRef } from 'react';
import { Animated, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useToastStore, type ToastType } from '../store/toastStore';
import { COLORS, FONTS, RADIUS } from '../constants/theme';

const CONFIG: Record<ToastType, { bg: string; icon: string }> = {
  success: { bg: '#16A34A', icon: '✓' },
  error: { bg: '#EF4444', icon: '✕' },
  info: { bg: '#3B82F6', icon: 'ℹ' },
  warning: { bg: '#F59E0B', icon: '⚠' },
};

export default function Toast() {
  const { visible, message, type, hide } = useToastStore();
  const insets = useSafeAreaInsets();
  const opacity = useRef(new Animated.Value(0)).current;
  const translateY = useRef(new Animated.Value(-20)).current;
  const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    if (visible) {
      if (timerRef.current) clearTimeout(timerRef.current);
      Animated.parallel([
        Animated.timing(opacity, { toValue: 1, duration: 250, useNativeDriver: true }),
        Animated.timing(translateY, { toValue: 0, duration: 250, useNativeDriver: true }),
      ]).start();

      timerRef.current = setTimeout(() => {
        Animated.parallel([
          Animated.timing(opacity, { toValue: 0, duration: 200, useNativeDriver: true }),
          Animated.timing(translateY, { toValue: -20, duration: 200, useNativeDriver: true }),
        ]).start(() => hide());
      }, 2800);
    }
    return () => {
      if (timerRef.current) clearTimeout(timerRef.current);
    };
  }, [visible]);

  if (!visible) return null;

  const cfg = CONFIG[type] || CONFIG.info;

  return (
    <Animated.View
      style={[
        styles.container,
        {
          backgroundColor: cfg.bg,
          top: insets.top + 8,
          opacity,
          transform: [{ translateY }],
        },
      ]}
    >
      <Text style={styles.icon}>{cfg.icon}</Text>
      <Text style={styles.message} numberOfLines={2}>
        {message}
      </Text>
    </Animated.View>
  );
}

const styles = StyleSheet.create({
  container: {
    position: 'absolute',
    left: 16,
    right: 16,
    zIndex: 9999,
    flexDirection: 'row',
    alignItems: 'center',
    padding: 14,
    borderRadius: RADIUS.lg,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.2,
    shadowRadius: 8,
    elevation: 10,
  },
  icon: {
    fontSize: 18,
    color: '#fff',
    fontWeight: '700',
    marginRight: 10,
    width: 24,
    textAlign: 'center',
  },
  message: {
    flex: 1,
    color: '#fff',
    fontFamily: FONTS.medium,
    fontSize: 14,
    lineHeight: 20,
  },
});
