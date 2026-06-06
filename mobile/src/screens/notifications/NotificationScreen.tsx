import { useEffect } from 'react';
import { View, Text, FlatList, TouchableOpacity, StyleSheet } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import { ArrowLeft, CheckCheck } from 'lucide-react-native';
import { useNotificationStore } from '../../store/notificationStore';
import { EmptyState } from '../../components/EmptyState';
import { COLORS, RADIUS, SPACING, FONT_SIZES, SHADOWS } from '../../constants/theme';
import { formatRelativeTime } from '../../utils/date';
import type { Notification } from '../../types';

function notifConfig(type: string): { icon: string; color: string } {
  const map: Record<string, { icon: string; color: string }> = {
    task_completed: { icon: 'check-circle', color: '#1DB954' },
    task_missed: { icon: 'alert-circle', color: '#EF4444' },
    service_added: { icon: 'plus-circle', color: '#0EA5E9' },
    customer_added: { icon: 'user-plus', color: '#1DB954' },
    order_created: { icon: 'clipboard-list', color: '#3B82F6' },
    order_assigned: { icon: 'user-check', color: '#8B5CF6' },
    order_completed: { icon: 'check-circle', color: '#22C55E' },
  };
  return map[type] || { icon: 'info', color: '#6B7280' };
}

export function NotificationScreen() {
  const navigation = useNavigation<any>();
  const insets = useSafeAreaInsets();
  const { notifications, unreadCount, fetchNotifications, markAsRead, markAllRead } = useNotificationStore();

  useEffect(() => { fetchNotifications(); }, []);

  const renderNotif = ({ item }: { item: Notification }) => {
    const cfg = notifConfig(item.type);
    return (
      <TouchableOpacity
        style={[styles.card, !item.isRead && styles.unreadCard]}
        onPress={() => { if (!item.isRead) markAsRead(item.id); }}
      >
        <View style={[styles.iconBox, { backgroundColor: cfg.color + '15' }]}>
          <View style={[styles.iconDot, { backgroundColor: cfg.color }]} />
        </View>
        <View style={styles.content}>
          <Text style={styles.text}>{item.text}</Text>
          <Text style={styles.time}>{formatRelativeTime(item.createdAt)}</Text>
        </View>
        {!item.isRead && <View style={styles.unreadDot} />}
      </TouchableOpacity>
    );
  };

  return (
    <View style={styles.container}>
      <View style={[styles.header, { paddingTop: insets.top, minHeight: 56 + insets.top }]}>
        <View style={styles.headerLeft}>
          <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
            <ArrowLeft size={20} color={COLORS.neutral600} />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Notifications</Text>
          {unreadCount > 0 && (
            <View style={styles.countBadge}>
              <Text style={styles.countText}>{unreadCount}</Text>
            </View>
          )}
        </View>
        {unreadCount > 0 && (
          <TouchableOpacity onPress={markAllRead} style={styles.markAllBtn}>
            <CheckCheck size={16} color={COLORS.primary} />
            <Text style={styles.markAllText}>Mark All Read</Text>
          </TouchableOpacity>
        )}
      </View>

      <FlatList
        data={notifications}
        keyExtractor={(item) => String(item.id)}
        renderItem={renderNotif}
        contentContainerStyle={styles.list}
        ListEmptyComponent={<EmptyState title="No notifications yet" />}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: COLORS.neutral50 },
  header: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: SPACING[4], backgroundColor: COLORS.white,
    borderBottomWidth: 1, borderBottomColor: COLORS.neutral200,
  },
  headerLeft: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  backBtn: { padding: 8, minWidth: 44, minHeight: 44, justifyContent: 'center' },
  headerTitle: { fontSize: FONT_SIZES.lg, fontWeight: '700', color: COLORS.navy },
  countBadge: {
    backgroundColor: COLORS.primary, borderRadius: RADIUS.full,
    paddingHorizontal: 6, paddingVertical: 2,
  },
  countText: { color: COLORS.white, fontSize: FONT_SIZES.xs, fontWeight: '700' },
  markAllBtn: { flexDirection: 'row', alignItems: 'center', gap: 4, padding: 8, minHeight: 44 },
  markAllText: { fontSize: FONT_SIZES.xs, fontWeight: '500', color: COLORS.primary },
  list: { padding: SPACING[4], paddingBottom: 80 },
  card: {
    backgroundColor: COLORS.white, borderRadius: RADIUS.lg, padding: SPACING[4],
    flexDirection: 'row', gap: 12, borderWidth: 1, borderColor: COLORS.neutral100,
    marginBottom: SPACING[2], ...SHADOWS.sm,
  },
  unreadCard: { borderLeftWidth: 4, borderLeftColor: COLORS.primary },
  iconBox: { width: 36, height: 36, borderRadius: 18, alignItems: 'center', justifyContent: 'center' },
  iconDot: { width: 20, height: 20, borderRadius: 10 },
  content: { flex: 1 },
  text: { fontSize: FONT_SIZES.sm, color: COLORS.neutral700, lineHeight: 20 },
  time: { fontSize: FONT_SIZES.xs, color: COLORS.neutral400, marginTop: 4 },
  unreadDot: { width: 8, height: 8, borderRadius: 4, backgroundColor: COLORS.primary, marginTop: 6 },
});
