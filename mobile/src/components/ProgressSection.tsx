import { memo, useEffect, useRef } from 'react';
import { View, Text, Animated, StyleSheet } from 'react-native';
import { COLORS, RADIUS, SPACING, FONT_SIZES } from '../constants/theme';

interface ProgressSectionProps {
  completed: number;
  total: number;
}

export const ProgressSection = memo(function ProgressSection({ completed, total }: ProgressSectionProps) {
  const pct = total > 0 ? Math.round((completed / total) * 100) : 0;
  const widthAnim = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    widthAnim.setValue(0);
    Animated.timing(widthAnim, {
      toValue: pct,
      duration: 600,
      useNativeDriver: false,
    }).start();
  }, [pct]);

  const barWidth = widthAnim.interpolate({
    inputRange: [0, 100],
    outputRange: ['0%', '100%'],
  });

  return (
    <View style={styles.card}>
      <View style={styles.header}>
        <Text style={styles.title}>Today's Progress</Text>
        <Text style={styles.pct}>{pct}%</Text>
      </View>
      <View style={styles.barBg}>
        <Animated.View style={[styles.barFill, { width: barWidth }]} />
      </View>
      <Text style={styles.sub}>{completed} of {total} tasks completed</Text>
    </View>
  );
});

const styles = StyleSheet.create({
  card: {
    backgroundColor: COLORS.white,
    borderRadius: RADIUS.xl,
    padding: SPACING[4],
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: SPACING[3],
  },
  title: { fontSize: FONT_SIZES.sm, fontWeight: '600', color: COLORS.neutral900 },
  pct: { fontSize: FONT_SIZES.sm, fontWeight: '700', color: COLORS.primary },
  barBg: {
    height: 10,
    backgroundColor: COLORS.neutral100,
    borderRadius: 5,
    overflow: 'hidden',
  },
  barFill: {
    height: '100%',
    backgroundColor: COLORS.primary,
    borderRadius: 5,
  },
  sub: {
    fontSize: 12,
    color: COLORS.neutral500,
    marginTop: SPACING[2],
    fontWeight: '500',
  },
});
