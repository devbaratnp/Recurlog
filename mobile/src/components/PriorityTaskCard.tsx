import { memo, useCallback, useRef } from 'react';
import { View, Text, TouchableOpacity, Animated, StyleSheet } from 'react-native';
import { MapPin, Clock, Play, CheckCircle } from 'lucide-react-native';
import { COLORS, RADIUS, SPACING, FONT_SIZES } from '../constants/theme';
import type { Task } from '../types';

interface PriorityTaskCardProps {
  task: Task;
  onComplete: (task: Task) => void;
}

export const PriorityTaskCard = memo(function PriorityTaskCard({ task, onComplete }: PriorityTaskCardProps) {
  const scaleAnim = useRef(new Animated.Value(1)).current;

  const onPressIn = () => {
    Animated.spring(scaleAnim, { toValue: 0.97, useNativeDriver: true }).start();
  };
  const onPressOut = () => {
    Animated.spring(scaleAnim, { toValue: 1, useNativeDriver: true }).start();
  };
  const handlePress = useCallback(() => onComplete(task), [task, onComplete]);

  return (
    <View style={styles.card}>
      <View style={styles.header}>
        <View style={styles.badgeRow}>
          <View style={styles.urgentBadge}>
            <Text style={styles.urgentBadgeText}>Priority</Text>
          </View>
          <View style={styles.pendingBadge}>
            <Text style={styles.pendingBadgeText}>Pending</Text>
          </View>
        </View>
      </View>

      <Text style={styles.title}>{task.title}</Text>

      <View style={styles.metaRow}>
        <MapPin size={14} color={COLORS.neutral400} />
        <Text style={styles.metaText}>{task.customerName || (task.customerId ? `Customer #${task.customerId}` : 'Unknown')}</Text>
      </View>

      <View style={styles.metaRow}>
        <Clock size={14} color={COLORS.neutral400} />
        <Text style={styles.metaText}>Due: {task.scheduledDate}</Text>
      </View>

      <Animated.View style={{ transform: [{ scale: scaleAnim }] }}>
        <TouchableOpacity
          style={styles.cta}
          onPress={handlePress}
          onPressIn={onPressIn}
          onPressOut={onPressOut}
          activeOpacity={0.9}
          accessibilityLabel={`Start visit for ${task.title}`}
          accessibilityRole="button"
        >
          <Play size={18} color={COLORS.white} fill={COLORS.white} />
          <Text style={styles.ctaText}>Start Visit</Text>
        </TouchableOpacity>
      </Animated.View>
    </View>
  );
});

export const PriorityTaskEmpty = memo(function PriorityTaskEmpty() {
  return (
    <View style={styles.card}>
      <View style={styles.empty}>
        <CheckCircle size={40} color={COLORS.primary} />
        <Text style={styles.emptyTitle}>All tasks completed!</Text>
        <Text style={styles.emptySub}>Great work today</Text>
      </View>
    </View>
  );
});

const styles = StyleSheet.create({
  card: {
    backgroundColor: COLORS.white,
    borderRadius: RADIUS['2xl'],
    padding: SPACING[5],
    shadowColor: COLORS.black,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.1,
    shadowRadius: 12,
    elevation: 4,
  },
  header: { marginBottom: SPACING[3] },
  badgeRow: { flexDirection: 'row', gap: 8 },
  urgentBadge: {
    backgroundColor: 'rgba(239,68,68,0.1)',
    paddingHorizontal: 10,
    paddingVertical: 3,
    borderRadius: RADIUS.full,
  },
  urgentBadgeText: { fontSize: 11, fontWeight: '600', color: COLORS.danger },
  pendingBadge: {
    backgroundColor: 'rgba(245,158,11,0.1)',
    paddingHorizontal: 10,
    paddingVertical: 3,
    borderRadius: RADIUS.full,
  },
  pendingBadgeText: { fontSize: 11, fontWeight: '600', color: COLORS.amber },
  title: { fontSize: FONT_SIZES.lg, fontWeight: '700', color: COLORS.neutral900, marginBottom: SPACING[3] },
  metaRow: { flexDirection: 'row', alignItems: 'center', gap: 8, marginBottom: 6 },
  metaText: { fontSize: FONT_SIZES.sm, color: COLORS.neutral500 },
  cta: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    height: 50,
    backgroundColor: COLORS.primary,
    borderRadius: RADIUS.xl,
    marginTop: SPACING[4],
  },
  ctaText: { color: COLORS.white, fontSize: FONT_SIZES.base, fontWeight: '700' },
  empty: { alignItems: 'center', paddingVertical: SPACING[4], gap: 6 },
  emptyTitle: { fontSize: FONT_SIZES.base, fontWeight: '600', color: COLORS.neutral900 },
  emptySub: { fontSize: FONT_SIZES.sm, color: COLORS.neutral500 },
});
