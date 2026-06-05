import { View, Text, StyleSheet } from 'react-native';
import { COLORS, RADIUS, FONT_SIZES } from '../constants/theme';
import type { TaskStatus, OrderStatus } from '../types';

interface StatusBadgeProps {
  status: TaskStatus | OrderStatus | string;
}

const configs: Record<string, { bg: string; text: string; label: string }> = {
  pending: { bg: '#FEF3C7', text: '#92400E', label: 'Pending' },
  completed: { bg: '#DCFCE7', text: '#166534', label: 'Completed' },
  missed: { bg: '#FEE2E2', text: '#991B1B', label: 'Missed' },
  assigned: { bg: '#EDE9FE', text: '#6D28D9', label: 'Assigned' },
  cancelled: { bg: '#F3F4F6', text: '#6B7280', label: 'Cancelled' },
};

export function StatusBadge({ status }: StatusBadgeProps) {
  const cfg = configs[status] || configs.pending;
  return (
    <View style={[styles.badge, { backgroundColor: cfg.bg }]}>
      <Text style={[styles.text, { color: cfg.text }]}>{cfg.label}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  badge: {
    paddingHorizontal: 10,
    paddingVertical: 3,
    borderRadius: RADIUS.full,
    alignSelf: 'flex-start',
  },
  text: {
    fontSize: FONT_SIZES.xs,
    fontWeight: '500',
  },
});
