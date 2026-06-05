import { View, Text, StyleSheet } from 'react-native';
import { RADIUS, FONT_SIZES } from '../constants/theme';

interface PriorityBadgeProps {
  priority: 'urgent' | 'normal';
}

export function PriorityBadge({ priority }: PriorityBadgeProps) {
  const isUrgent = priority === 'urgent';
  return (
    <View style={[styles.badge, { backgroundColor: isUrgent ? '#FEE2E2' : '#F3F4F6' }]}>
      <Text style={[styles.text, { color: isUrgent ? '#991B1B' : '#6B7280' }]}>
        {isUrgent ? 'Urgent' : 'Normal'}
      </Text>
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
