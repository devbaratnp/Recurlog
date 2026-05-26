import { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  FlatList,
  TouchableOpacity,
  RefreshControl,
  ActivityIndicator,
  StyleSheet,
} from 'react-native';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { ScreenWrapper } from '../src/components/ScreenWrapper';
import { Card } from '../src/components/Card';
import { EmptyState } from '../src/components/EmptyState';
import { colors, borderRadius, typography } from '../src/theme';
import { formatRelative, getNotificationIcon, getNotificationColor } from '../src/lib/helpers';

interface Notification {
  id: number;
  type: string;
  text: string;
  createdAt: string;
  isRead: boolean;
}

export default function NotificationsScreen() {
  const router = useRouter();
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchNotifications = useCallback(async () => {
    try {
      const res = await fetch('https://api.recurlog.com/notifications');
      if (!res.ok) throw new Error('Failed');
      const json = await res.json();
      setNotifications(json);
    } catch {
      setNotifications([
        { id: 1, type: 'task_completed', text: 'RO service completed at Sharma Family', createdAt: new Date(Date.now() - 300000).toISOString(), isRead: false },
        { id: 2, type: 'service_added', text: 'New AC service added for Gupta Traders', createdAt: new Date(Date.now() - 1800000).toISOString(), isRead: false },
        { id: 3, type: 'customer_added', text: 'New customer registered: Mehta & Co', createdAt: new Date(Date.now() - 3600000).toISOString(), isRead: false },
        { id: 4, type: 'task_missed', text: 'TV installation missed at Patel Residence', createdAt: new Date(Date.now() - 7200000).toISOString(), isRead: true },
        { id: 5, type: 'task_completed', text: 'Refrigerator service completed at Thapa Family', createdAt: new Date(Date.now() - 14400000).toISOString(), isRead: true },
        { id: 6, type: 'service_added', text: 'New washing machine service for Kumar Electronics', createdAt: new Date(Date.now() - 28800000).toISOString(), isRead: true },
        { id: 7, type: 'customer_added', text: 'New customer: Verma Household', createdAt: new Date(Date.now() - 57600000).toISOString(), isRead: true },
        { id: 8, type: 'task_completed', text: 'AC repair completed at Gupta Traders', createdAt: new Date(Date.now() - 115200000).toISOString(), isRead: true },
      ]);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchNotifications();
  }, [fetchNotifications]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await fetchNotifications();
    setRefreshing(false);
  }, [fetchNotifications]);

  const handleMarkAllRead = () => {
    setNotifications((prev) => prev.map((n) => ({ ...n, isRead: true })));
  };

  const unreadCount = notifications.filter((n) => !n.isRead).length;

  return (
    <ScreenWrapper>
      <FlatList
        data={notifications}
        keyExtractor={(item) => item.id.toString()}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.brand} />}
        contentContainerStyle={styles.listContent}
        ListHeaderComponent={
          <View style={styles.header}>
            <View style={styles.headerLeft}>
              <TouchableOpacity onPress={() => router.back()} activeOpacity={0.7}>
                <Ionicons name="arrow-back" size={24} color={colors.gray[400]} />
              </TouchableOpacity>
              <Text style={styles.title}>Notifications</Text>
              {unreadCount > 0 && (
                <View style={styles.countBadge}>
                  <Text style={styles.countText}>{unreadCount}</Text>
                </View>
              )}
            </View>
            {unreadCount > 0 && (
              <TouchableOpacity onPress={handleMarkAllRead} style={styles.markAllBtn} activeOpacity={0.7}>
                <Ionicons name="checkmark-done" size={16} color={colors.brand} />
                <Text style={styles.markAllText}>Mark All Read</Text>
              </TouchableOpacity>
            )}
          </View>
        }
        ListEmptyComponent={
          loading ? (
            <ActivityIndicator size="large" color={colors.brand} style={{ marginTop: 48 }} />
          ) : (
            <EmptyState icon="notifications-off-outline" message="No notifications yet" />
          )
        }
        renderItem={({ item }) => {
          const iconName = getNotificationIcon(item.type);
          const iconColor = getNotificationColor(item.type);

          return (
            <View style={[styles.notifCard, !item.isRead && styles.notifUnread]}>
              <View style={[styles.notifIcon, { backgroundColor: iconColor + '15' }]}>
                <Ionicons name={iconName as any} size={20} color={iconColor} />
              </View>
              <View style={styles.notifContent}>
                <Text style={styles.notifText}>{item.text}</Text>
                <Text style={styles.notifTime}>{formatRelative(item.createdAt)}</Text>
              </View>
              {!item.isRead && <View style={styles.unreadDot} />}
            </View>
          );
        }}
      />
    </ScreenWrapper>
  );
}

const styles = StyleSheet.create({
  listContent: {
    padding: 16,
    paddingBottom: 32,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 20,
  },
  headerLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  title: {
    fontSize: typography.fontSize.xl,
    fontWeight: typography.fontWeight.bold,
    color: colors.navy,
  },
  countBadge: {
    backgroundColor: colors.brand,
    borderRadius: borderRadius.full,
    minWidth: 22,
    height: 22,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 6,
  },
  countText: {
    color: colors.white,
    fontSize: typography.fontSize.xs,
    fontWeight: typography.fontWeight.bold,
  },
  markAllBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    paddingVertical: 6,
    paddingHorizontal: 10,
  },
  markAllText: {
    fontSize: typography.fontSize.sm,
    fontWeight: typography.fontWeight.medium,
    color: colors.brand,
  },
  notifCard: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 12,
    backgroundColor: colors.white,
    borderRadius: borderRadius.lg,
    borderWidth: 1,
    borderColor: colors.gray[100],
    padding: 14,
    marginBottom: 8,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.03,
    shadowRadius: 2,
    elevation: 1,
  },
  notifUnread: {
    borderLeftWidth: 4,
    borderLeftColor: colors.brand,
  },
  notifIcon: {
    width: 36,
    height: 36,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
  },
  notifContent: {
    flex: 1,
  },
  notifText: {
    fontSize: typography.fontSize.sm,
    color: colors.gray[700],
    lineHeight: 20,
  },
  notifTime: {
    fontSize: typography.fontSize.xs,
    color: colors.gray[400],
    marginTop: 4,
  },
  unreadDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: colors.brand,
    marginTop: 6,
  },
});
