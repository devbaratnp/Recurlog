import { memo, useCallback } from 'react';
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';
import { Clock } from 'lucide-react-native';
import { COLORS, RADIUS, SPACING, FONT_SIZES } from '../constants/theme';
import type { Task } from '../types';

interface TimelineSection {
  title: string;
  data: Task[];
}

interface UpcomingTimelineProps {
  sections: TimelineSection[];
  onTaskPress: (task: Task) => void;
}

function formatDateLabel(dateStr: string): string {
  const d = new Date(dateStr + 'T00:00:00');
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
  return `${days[d.getDay()]}, ${months[d.getMonth()]} ${d.getDate()}`;
}

const TimelineRow = memo(function TimelineRow({ task, onPress }: { task: Task; onPress: (t: Task) => void }) {
  return (
    <TouchableOpacity
      style={styles.row}
      onPress={() => onPress(task)}
      activeOpacity={0.6}
      accessibilityLabel={`Task: ${task.title}`}
      accessibilityRole="button"
    >
      <View style={styles.timelineLeft}>
        <View style={styles.dot} />
        <View style={styles.line} />
      </View>
      <View style={styles.rowContent}>
        <Text style={styles.rowTitle} numberOfLines={1}>{task.title}</Text>
        <Text style={styles.rowMeta} numberOfLines={1}>{task.customerName || (task.customerId ? `Customer #${task.customerId}` : 'Unknown')}</Text>
      </View>
      <Clock size={14} color={COLORS.neutral300} />
    </TouchableOpacity>
  );
});

const SectionHeader = memo(function SectionHeader({ title }: { title: string }) {
  return (
    <View style={styles.sectionHeader}>
      <Text style={styles.sectionHeaderText}>{formatDateLabel(title)}</Text>
    </View>
  );
});

export const UpcomingTimeline = memo(function UpcomingTimeline({ sections, onTaskPress }: UpcomingTimelineProps) {
  const handlePress = useCallback((task: Task) => onTaskPress(task), [onTaskPress]);

  if (sections.length === 0) {
    return (
      <View style={styles.empty}>
        <Text style={styles.emptyText}>No upcoming tasks</Text>
      </View>
    );
  }

  return (
    <View style={styles.card}>
      <Text style={styles.cardTitle}>Upcoming Tasks</Text>
      {sections.map((section) => (
        <View key={section.title}>
          <SectionHeader title={section.title} />
          {section.data.map((task) => (
            <TimelineRow key={task.id} task={task} onPress={handlePress} />
          ))}
        </View>
      ))}
    </View>
  );
});

const styles = StyleSheet.create({
  card: {
    backgroundColor: COLORS.white,
    borderRadius: RADIUS.xl,
    overflow: 'hidden',
  },
  cardTitle: {
    fontSize: FONT_SIZES.sm,
    fontWeight: '600',
    color: COLORS.neutral900,
    paddingHorizontal: SPACING[4],
    paddingTop: SPACING[4],
    paddingBottom: SPACING[2],
  },
  sectionHeader: {
    paddingVertical: SPACING[1],
    paddingHorizontal: SPACING[4],
    paddingTop: SPACING[3],
  },
  sectionHeaderText: {
    fontSize: 12,
    fontWeight: '700',
    color: COLORS.neutral500,
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 10,
    paddingHorizontal: SPACING[4],
    gap: 10,
  },
  timelineLeft: { alignItems: 'center', width: 12 },
  dot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: COLORS.primary,
  },
  line: {
    width: 2,
    flex: 1,
    backgroundColor: COLORS.neutral200,
    marginTop: 2,
  },
  rowContent: { flex: 1 },
  rowTitle: { fontSize: FONT_SIZES.sm, fontWeight: '500', color: COLORS.neutral900 },
  rowMeta: { fontSize: 12, color: COLORS.neutral500, marginTop: 1 },
  empty: { alignItems: 'center', paddingVertical: SPACING[8] },
  emptyText: { fontSize: FONT_SIZES.sm, color: COLORS.neutral400 },
});
