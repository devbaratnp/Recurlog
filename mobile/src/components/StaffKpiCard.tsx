import { memo, useEffect, useRef, useState } from 'react';
import { View, Text, Animated, StyleSheet } from 'react-native';
import { CheckCircle, Clock, XCircle } from 'lucide-react-native';
import { COLORS, RADIUS, SPACING, FONT_SIZES } from '../constants/theme';

interface StaffKpiCardProps {
  type: 'completed' | 'pending' | 'missed';
  value: number;
}

const CONFIG = {
  completed: {
    icon: CheckCircle,
    label: 'Completed',
    color: COLORS.kpiGreen,
    bg: COLORS.kpiGreenBg,
  },
  pending: {
    icon: Clock,
    label: 'Pending',
    color: COLORS.kpiAmber,
    bg: COLORS.kpiAmberBg,
  },
  missed: {
    icon: XCircle,
    label: 'Missed',
    color: COLORS.kpiRed,
    bg: COLORS.kpiRedBg,
  },
};

export const StaffKpiCard = memo(function StaffKpiCard({ type, value }: StaffKpiCardProps) {
  const cfg = CONFIG[type];
  const Icon = cfg.icon;
  const [display, setDisplay] = useState(0);
  const animValue = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    animValue.setValue(0);
    const listener = animValue.addListener(({ value: v }) => {
      setDisplay(Math.round(v));
    });
    Animated.timing(animValue, {
      toValue: value,
      duration: 800,
      useNativeDriver: false,
    }).start();
    return () => animValue.removeListener(listener);
  }, [value]);

  return (
    <View style={[styles.card, { backgroundColor: cfg.bg }]}>
      <Icon size={18} color={cfg.color} />
      <Text style={[styles.value, { color: cfg.color }]}>{display}</Text>
      <Text style={styles.label}>{cfg.label}</Text>
    </View>
  );
});

const styles = StyleSheet.create({
  card: {
    flex: 1,
    borderRadius: RADIUS.xl,
    padding: SPACING[3],
    alignItems: 'center',
    gap: 2,
  },
  value: {
    fontSize: FONT_SIZES['2xl'],
    fontWeight: '800',
  },
  label: {
    fontSize: 11,
    color: COLORS.neutral500,
    fontWeight: '500',
  },
});
