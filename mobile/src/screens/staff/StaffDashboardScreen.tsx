import { useEffect, useState, useCallback, useMemo } from 'react';
import {
  View, Text, ScrollView, RefreshControl, StyleSheet, Alert,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { useAuthStore } from '../../store/authStore';
import { useNotificationStore } from '../../store/notificationStore';
import { tasksApi, ordersApi } from '../../api/client';
import { StaffHeader } from '../../components/StaffHeader';
import { StaffKpiCard } from '../../components/StaffKpiCard';
import { ProgressSection } from '../../components/ProgressSection';
import { PriorityTaskCard, PriorityTaskEmpty } from '../../components/PriorityTaskCard';
import { QuickActions } from '../../components/QuickActions';
import { UpcomingTimeline } from '../../components/UpcomingTimeline';
import { StaffTaskCompleteModal } from '../../components/StaffTaskCompleteModal';
import { StaffDashboardSkeleton } from '../../components/LoadingSkeleton';
import { COLORS, SPACING } from '../../constants/theme';
import { todayISO } from '../../utils/date';
import type { Task, Order } from '../../types';

interface TimelineSection {
  title: string;
  data: Task[];
}

export function StaffDashboardScreen() {
  const navigation = useNavigation<any>();
  const user = useAuthStore((s) => s.user);
  const { unreadCount } = useNotificationStore();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [todayTasks, setTodayTasks] = useState<Task[]>([]);
  const [upcomingTasks, setUpcomingTasks] = useState<Task[]>([]);
  const [allTasks, setAllTasks] = useState<Task[]>([]);
  const [assignedOrders, setAssignedOrders] = useState<Order[]>([]);
  const [completeTask, setCompleteTask] = useState<Task | null>(null);
  const [showCompleteModal, setShowCompleteModal] = useState(false);

  const fetchData = useCallback(async () => {
    try {
      const staffId = user?.staffId;
      if (!staffId) { setLoading(false); return; }

      const today = todayISO();
      const [todayRes, upcomingRes, allRes, ordersRes] = await Promise.all([
        tasksApi.list({ assigned_to: staffId, scheduled_date: today, status: 'pending' }),
        tasksApi.list({ assigned_to: staffId, start_date: today, status: 'pending' }),
        tasksApi.list({ assigned_to: staffId }),
        ordersApi.list({ assigned_to: staffId?.toString() }),
      ]);

      const allToday = Array.isArray(todayRes.data?.data) ? todayRes.data.data as Task[] : [];
      const allUpcoming = Array.isArray(upcomingRes.data?.data) ? upcomingRes.data.data as Task[] : [];
      const allRecent = Array.isArray(allRes.data?.data) ? allRes.data.data as Task[] : [];
      const allOrders = Array.isArray(ordersRes.data?.data) ? ordersRes.data.data as Order[] : [];

      setTodayTasks(allToday);
      setUpcomingTasks(allUpcoming.filter((t) => t.scheduledDate > today && t.status === 'pending').slice(0, 30));
      setAllTasks(allRecent);
      setAssignedOrders(allOrders.filter((o) => o.status === 'pending' || o.status === 'assigned'));
    } catch { Alert.alert('Error', 'Failed to load dashboard'); } finally { setLoading(false); }
  }, [user?.staffId]);

  useEffect(() => { fetchData(); }, [fetchData]);

  const onRefresh = async () => { setRefreshing(true); await fetchData(); setRefreshing(false); };

  const openComplete = useCallback((task: Task) => {
    setCompleteTask(task);
    setShowCompleteModal(true);
  }, []);

  const handleComplete = useCallback(() => {
    setShowCompleteModal(false);
    setCompleteTask(null);
    fetchData();
  }, [fetchData]);

  const stats = useMemo(() => {
    const today = todayISO();
    const completedToday = allTasks.filter((t) => t.status === 'completed' && t.completedDate === today).length;
    const pending = allTasks.filter((t) => t.status === 'pending').length;
    const missed = allTasks.filter((t) => t.status === 'missed').length;
    const totalToday = todayTasks.length + completedToday;
    return { completedToday, pending, missed, totalToday };
  }, [allTasks, todayTasks]);

  const priorityTask = useMemo(() => {
    if (todayTasks.length === 0) return null;
    return todayTasks.sort((a, b) => a.scheduledDate.localeCompare(b.scheduledDate))[0];
  }, [todayTasks]);

  const timelineSections = useMemo(() => {
    if (upcomingTasks.length === 0) return [];
    const map = new Map<string, Task[]>();
    for (const t of upcomingTasks) {
      const key = t.scheduledDate;
      if (!map.has(key)) map.set(key, []);
      map.get(key)!.push(t);
    }
    const sections: TimelineSection[] = [];
    for (const [date, tasks] of map) {
      sections.push({ title: date, data: tasks });
    }
    sections.sort((a, b) => a.title.localeCompare(b.title));
    return sections;
  }, [upcomingTasks]);

  const handleTaskPress = useCallback(
    (task: Task) => navigation.navigate('DashboardTab', { screen: 'TaskDetail', params: { id: task.id } }),
    [navigation]
  );

  if (loading) {
    return (
      <View style={styles.container}>
        <StaffHeader
          name={user?.name || 'Staff'}
          unreadCount={unreadCount}
          onNotificationPress={() => navigation.navigate('Notifications')}
        />
        <StaffDashboardSkeleton />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <StaffHeader
        name={user?.name || 'Staff'}
        unreadCount={unreadCount}
        onNotificationPress={() => navigation.navigate('Notifications')}
      />

      <ScrollView
        contentContainerStyle={styles.scroll}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={COLORS.primary} />
        }
        showsVerticalScrollIndicator={false}
      >
        {/* KPI Row */}
        <View style={styles.kpiRow}>
          <StaffKpiCard type="completed" value={stats.completedToday} />
          <StaffKpiCard type="pending" value={stats.pending} />
          <StaffKpiCard type="missed" value={stats.missed} />
        </View>

        {/* Progress Bar */}
        <View style={styles.sectionPad}>
          <ProgressSection completed={stats.completedToday} total={stats.pending + stats.completedToday + stats.missed} />
        </View>

        {/* Priority Task Hero */}
        <View style={styles.sectionPad}>
          {priorityTask ? (
            <PriorityTaskCard task={priorityTask} onComplete={openComplete} />
          ) : (
            <PriorityTaskEmpty />
          )}
        </View>

        {/* Quick Actions */}
        <View style={styles.sectionPad}>
          <QuickActions
            onOrders={() => navigation.navigate('OrderList')}
            onCustomers={() => navigation.navigate('CustomerList')}
            onDaybook={() => navigation.navigate('Daybook')}
          />
        </View>

        {/* Upcoming Timeline */}
        {timelineSections.length > 0 && (
          <View style={styles.sectionPad}>
            <UpcomingTimeline sections={timelineSections} onTaskPress={handleTaskPress} />
          </View>
        )}

        {/* Assigned Orders */}
        {assignedOrders.length > 0 && (
          <View style={styles.sectionPad}>
            <View style={styles.ordersSection}>
              {assignedOrders.map((order) => (
                <View key={order.id} style={styles.orderRow}>
                  <View style={styles.orderInfo}>
                    <Text style={styles.orderTitle}>{order.problem}</Text>
                    <Text style={styles.orderMeta}>{order.customerName || `Customer #${order.customerId}`}</Text>
                  </View>
                  <View style={[styles.orderBadge, { backgroundColor: order.status === 'assigned' ? 'rgba(59,130,246,0.1)' : 'rgba(245,158,11,0.1)' }]}>
                    <Text style={[styles.orderBadgeText, { color: order.status === 'assigned' ? '#3B82F6' : COLORS.amber }]}>
                      {order.status === 'assigned' ? 'Assigned' : 'Pending'}
                    </Text>
                  </View>
                </View>
              ))}
            </View>
          </View>
        )}

        <View style={{ height: 80 }} />
      </ScrollView>

      <StaffTaskCompleteModal
        visible={showCompleteModal}
        task={completeTask}
        onClose={() => { setShowCompleteModal(false); setCompleteTask(null); }}
        onComplete={handleComplete}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: COLORS.neutral50 },
  scroll: { paddingTop: SPACING[4], paddingBottom: SPACING[4] },
  kpiRow: { flexDirection: 'row', gap: SPACING[2], paddingHorizontal: SPACING[4] },
  sectionPad: { paddingHorizontal: SPACING[4], marginTop: SPACING[4] },
  ordersSection: {
    backgroundColor: COLORS.white,
    borderRadius: 16,
    overflow: 'hidden',
  },
  orderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: SPACING[4],
    paddingVertical: SPACING[3],
    borderBottomWidth: 1,
    borderBottomColor: COLORS.neutral100,
  },
  orderInfo: { flex: 1, marginRight: SPACING[3] },
  orderTitle: { fontSize: 14, fontWeight: '500', color: COLORS.neutral900 },
  orderMeta: { fontSize: 12, color: COLORS.neutral500, marginTop: 2 },
  orderBadge: { paddingHorizontal: 8, paddingVertical: 3, borderRadius: 999 },
  orderBadgeText: { fontSize: 11, fontWeight: '600' },
});
