import { useEffect, useState, useCallback } from 'react';
import {
  View, Text, ScrollView, RefreshControl, StyleSheet,
  TouchableOpacity, ActivityIndicator, Dimensions,
} from 'react-native';
import { useNavigation } from '@react-navigation/native';
import {
  CalendarCheck, Calendar, Briefcase, ClipboardList, Clock,
  CheckCircle, LogOut, Wrench,
} from 'lucide-react-native';
import { useAuthStore } from '../../store/authStore';
import { useNotificationStore } from '../../store/notificationStore';
import { tasksApi, ordersApi } from '../../api/client';
import { StaffTaskCompleteModal } from '../../components/StaffTaskCompleteModal';
import { COLORS, RADIUS, SPACING, FONT_SIZES, SHADOWS } from '../../constants/theme';
import { todayISO } from '../../utils/date';
import type { Task, Order } from '../../types';

export function StaffDashboardScreen() {
  const navigation = useNavigation<any>();
  const user = useAuthStore((s) => s.user);
  const { unreadCount } = useNotificationStore();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [todayTasks, setTodayTasks] = useState<Task[]>([]);
  const [upcomingTasks, setUpcomingTasks] = useState<Task[]>([]);
  const [recentTasks, setRecentTasks] = useState<Task[]>([]);
  const [assignedOrders, setAssignedOrders] = useState<Order[]>([]);
  const [stats, setStats] = useState({ completedToday: 0, pending: 0, missed: 0 });
  const [completeTask, setCompleteTask] = useState<Task | null>(null);
  const [showCompleteModal, setShowCompleteModal] = useState(false);

  const fetchData = useCallback(async () => {
    try {
      const staffId = user?.staffId;
      if (!staffId) { setLoading(false); return; }

      const today = todayISO();
      const [todayRes, upcomingRes, recentRes, ordersRes] = await Promise.all([
        tasksApi.list({ assigned_to: staffId, scheduled_date: today, status: 'pending' }),
        tasksApi.list({ assigned_to: staffId, start_date: today, status: 'pending' }),
        tasksApi.list({ assigned_to: staffId }),
        ordersApi.list({ assigned_to: staffId?.toString() }),
      ]);

      const allToday = Array.isArray(todayRes.data?.data) ? todayRes.data.data as Task[] : [];
      const allUpcoming = Array.isArray(upcomingRes.data?.data) ? upcomingRes.data.data as Task[] : [];
      const allRecent = Array.isArray(recentRes.data?.data) ? recentRes.data.data as Task[] : [];
      const allOrders = Array.isArray(ordersRes.data?.data) ? ordersRes.data.data as Order[] : [];

      setTodayTasks(allToday);
      setUpcomingTasks(allUpcoming.filter((t) => t.scheduledDate > today && t.status === 'pending').slice(0, 20));
      setRecentTasks(allRecent.filter((t) => t.status === 'completed' || t.status === 'missed').slice(0, 10));
      setAssignedOrders(allOrders.filter((o) => o.status === 'pending' || o.status === 'assigned'));

      setStats({
        completedToday: allRecent.filter((t) => t.status === 'completed' && t.completedDate === today).length,
        pending: allRecent.filter((t) => t.status === 'pending').length + allToday.filter((t) => t.status === 'pending').length,
        missed: allRecent.filter((t) => t.status === 'missed').length,
      });
    } catch {} finally { setLoading(false); }
  }, [user?.staffId]);

  useEffect(() => { fetchData(); }, [fetchData]);

  const onRefresh = async () => { setRefreshing(true); await fetchData(); setRefreshing(false); };

  const openComplete = (task: Task) => {
    setCompleteTask(task);
    setShowCompleteModal(true);
  };

  const handleComplete = () => {
    setShowCompleteModal(false);
    setCompleteTask(null);
    fetchData();
  };

  const formatShortDate = (dateStr: string) => {
    const d = new Date(dateStr + 'T00:00:00');
    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return `${months[d.getMonth()]} ${d.getDate()}`;
  };

  const getDayName = (dateStr: string) => {
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('en', { weekday: 'short' });
  };

  const getInitials = (name: string) => {
    const parts = name.split(' ');
    return parts.map((p) => p[0]?.toUpperCase()).join('').slice(0, 2);
  };

  if (loading) {
    return (
      <View style={styles.container}>
        <View style={styles.header}>
          <View style={styles.headerLeft}>
            <View style={styles.logoSmall}>
              <Wrench size={16} color={COLORS.white} />
            </View>
            <Text style={styles.headerTitle}>Staff Dashboard</Text>
          </View>
        </View>
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={COLORS.primary} />
        </View>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <View style={styles.headerLeft}>
          <View style={styles.avatarSmall}>
            <Text style={styles.avatarSmallText}>{user ? getInitials(user.name) : 'S'}</Text>
          </View>
          <View>
            <Text style={styles.headerTitle}>{user?.name || 'Staff'}</Text>
            <Text style={styles.headerSub}>Field Staff</Text>
          </View>
        </View>
        <TouchableOpacity style={styles.bellBtn} onPress={() => navigation.navigate('Notifications')}>
          <Clock size={20} color={COLORS.neutral500} />
          {unreadCount > 0 && (
            <View style={styles.bellBadge}>
              <Text style={styles.bellBadgeText}>{unreadCount > 9 ? '9+' : unreadCount}</Text>
            </View>
          )}
        </TouchableOpacity>
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={COLORS.primary} />}
      >
        {/* Stats */}
        <View style={styles.statsGrid}>
          <View style={[styles.statCard, SHADOWS.sm]}>
            <Text style={[styles.statValue, { color: COLORS.primary }]}>{stats.completedToday}</Text>
            <Text style={styles.statLabel}>Completed Today</Text>
          </View>
          <View style={[styles.statCard, SHADOWS.sm]}>
            <Text style={[styles.statValue, { color: COLORS.amber }]}>{stats.pending}</Text>
            <Text style={styles.statLabel}>Pending</Text>
          </View>
          <View style={[styles.statCard, SHADOWS.sm]}>
            <Text style={[styles.statValue, { color: COLORS.danger }]}>{stats.missed}</Text>
            <Text style={styles.statLabel}>Missed</Text>
          </View>
        </View>

        {/* Today's Tasks */}
        <View style={[styles.sectionCard, SHADOWS.sm]}>
          <View style={styles.sectionHeader}>
            <View style={styles.sectionHeaderLeft}>
              <CalendarCheck size={16} color={COLORS.primary} />
              <Text style={styles.sectionTitle}>Today's Tasks</Text>
            </View>
            <View style={styles.sectionBadge}>
              <Text style={styles.sectionBadgeText}>{todayTasks.length}</Text>
            </View>
          </View>
          {todayTasks.length === 0 ? (
            <View style={styles.emptySection}>
              <CheckCircle size={32} color={COLORS.neutral200} />
              <Text style={styles.emptyText}>No tasks scheduled for today</Text>
            </View>
          ) : (
            todayTasks.map((task) => (
              <View key={task.id} style={styles.taskRow}>
                <TouchableOpacity style={styles.taskInfo} onPress={() => navigation.navigate('TaskDetail', { id: task.id })}>
                  <Text style={styles.taskTitle}>{task.title}</Text>
                  <Text style={styles.taskMeta}>{task.customerName || `Customer #${task.customerId}`}</Text>
                </TouchableOpacity>
                <TouchableOpacity style={styles.completeBtn} onPress={() => openComplete(task)}>
                  <CheckCircle size={14} color={COLORS.white} />
                  <Text style={styles.completeBtnText}>Complete</Text>
                </TouchableOpacity>
              </View>
            ))
          )}
        </View>

        {/* Upcoming Tasks */}
        <View style={[styles.sectionCard, SHADOWS.sm]}>
          <View style={styles.sectionHeader}>
            <View style={styles.sectionHeaderLeft}>
              <Calendar size={16} color={COLORS.amber} />
              <Text style={styles.sectionTitle}>Upcoming</Text>
            </View>
            <View style={[styles.sectionBadge, { backgroundColor: 'rgba(245,158,11,0.1)' }]}>
              <Text style={[styles.sectionBadgeText, { color: COLORS.amber }]}>{upcomingTasks.length}</Text>
            </View>
          </View>
          {upcomingTasks.length === 0 ? (
            <View style={styles.emptySection}>
              <Calendar size={32} color={COLORS.neutral200} />
              <Text style={styles.emptyText}>No upcoming tasks</Text>
            </View>
          ) : (
            upcomingTasks.map((task) => (
              <TouchableOpacity key={task.id} style={styles.taskRow} onPress={() => navigation.navigate('TaskDetail', { id: task.id })}>
                <View style={styles.taskInfo}>
                  <Text style={styles.taskTitle}>{task.title}</Text>
                  <Text style={styles.taskMeta}>
                    {task.customerName || `Customer #${task.customerId}`} · <Text style={{ color: COLORS.amber, fontWeight: '600' }}>{formatShortDate(task.scheduledDate)}</Text>
                  </Text>
                </View>
                <Text style={styles.dayLabel}>{getDayName(task.scheduledDate)}</Text>
              </TouchableOpacity>
            ))
          )}
        </View>

        {/* Assigned Orders */}
        {assignedOrders.length > 0 && (
          <View style={[styles.sectionCard, SHADOWS.sm]}>
            <View style={styles.sectionHeader}>
              <View style={styles.sectionHeaderLeft}>
                <ClipboardList size={16} color="#8B5CF6" />
                <Text style={styles.sectionTitle}>Assigned Orders</Text>
              </View>
              <View style={[styles.sectionBadge, { backgroundColor: 'rgba(139,92,246,0.1)' }]}>
                <Text style={[styles.sectionBadgeText, { color: '#8B5CF6' }]}>{assignedOrders.length}</Text>
              </View>
            </View>
            {assignedOrders.map((order) => (
              <View key={order.id} style={styles.taskRow}>
                <View style={styles.taskInfo}>
                  <Text style={styles.taskTitle}>{order.problem}</Text>
                  <Text style={styles.taskMeta}>{order.customerName || `Customer #${order.customerId}`}</Text>
                </View>
                <View style={[styles.statusBadge, { backgroundColor: order.status === 'assigned' ? 'rgba(59,130,246,0.1)' : 'rgba(245,158,11,0.1)' }]}>
                  <Text style={[styles.statusBadgeText, { color: order.status === 'assigned' ? '#3B82F6' : COLORS.amber }]}>
                    {order.status === 'assigned' ? 'Assigned' : 'Pending'}
                  </Text>
                </View>
              </View>
            ))}
          </View>
        )}

        {/* Recent Activity */}
        <View style={[styles.sectionCard, SHADOWS.sm]}>
          <View style={styles.sectionHeader}>
            <View style={styles.sectionHeaderLeft}>
              <Clock size={16} color={COLORS.neutral400} />
              <Text style={styles.sectionTitle}>Recent Activity</Text>
            </View>
          </View>
          {recentTasks.length === 0 ? (
            <View style={styles.emptySection}>
              <Text style={styles.emptyText}>No recent activity</Text>
            </View>
          ) : (
            recentTasks.map((task) => (
              <TouchableOpacity key={task.id} style={styles.activityRow} onPress={() => navigation.navigate('TaskDetail', { id: task.id })}>
                <View style={[styles.activityDot, { backgroundColor: task.status === 'completed' ? COLORS.primary : COLORS.danger }]} />
                <View style={styles.activityInfo}>
                  <Text style={styles.activityTitle}>{task.title}</Text>
                  <Text style={styles.activityMeta}>
                    {task.customerName || ''} · {formatShortDate(task.scheduledDate)}
                  </Text>
                </View>
                <Text style={[styles.activityStatus, { color: task.status === 'completed' ? COLORS.primary : COLORS.danger }]}>
                  {task.status === 'completed' ? 'Completed' : 'Missed'}
                </Text>
              </TouchableOpacity>
            ))
          )}
        </View>

        <View style={{ height: 80 }} />
      </ScrollView>

      {/* Nav to tasks */}
      <View style={styles.bottomNav}>
        <TouchableOpacity style={styles.bottomNavBtn} onPress={() => navigation.navigate('TaskList')}>
          <Briefcase size={18} color={COLORS.neutral600} />
          <Text style={styles.bottomNavText}>All Tasks</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.bottomNavBtn} onPress={() => navigation.navigate('OrderList')}>
          <ClipboardList size={18} color={COLORS.neutral600} />
          <Text style={styles.bottomNavText}>Orders</Text>
        </TouchableOpacity>
        <TouchableOpacity style={[styles.bottomNavBtn, { flexDirection: 'row', gap: 6 }]} onPress={() => useAuthStore.getState().logout()}>
          <LogOut size={18} color={COLORS.neutral600} />
          <Text style={styles.bottomNavText}>Logout</Text>
        </TouchableOpacity>
      </View>

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
  loadingContainer: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  header: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: SPACING[4], height: 56,
    backgroundColor: COLORS.navy, borderBottomWidth: 0,
  },
  headerLeft: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  avatarSmall: {
    width: 32, height: 32, borderRadius: 16,
    backgroundColor: COLORS.primary, alignItems: 'center', justifyContent: 'center',
    borderWidth: 2, borderColor: 'rgba(29,185,84,0.5)',
  },
  avatarSmallText: { color: COLORS.white, fontSize: 12, fontWeight: '700' },
  headerTitle: { fontSize: FONT_SIZES.sm, fontWeight: '700', color: COLORS.white },
  headerSub: { fontSize: 11, color: 'rgba(255,255,255,0.5)' },
  logoSmall: {
    width: 28, height: 28, backgroundColor: COLORS.primary,
    borderRadius: RADIUS.lg, alignItems: 'center', justifyContent: 'center',
  },
  bellBtn: { position: 'relative', padding: 8, minWidth: 44, minHeight: 44, alignItems: 'center', justifyContent: 'center' },
  bellBadge: {
    position: 'absolute', top: 4, right: 4,
    backgroundColor: COLORS.danger, width: 16, height: 16,
    borderRadius: 8, alignItems: 'center', justifyContent: 'center',
  },
  bellBadgeText: { color: COLORS.white, fontSize: 10, fontWeight: '700' },
  scrollContent: { padding: SPACING[4] },
  statsGrid: { flexDirection: 'row', gap: 8, marginBottom: SPACING[5] },
  statCard: {
    flex: 1, backgroundColor: COLORS.white, borderRadius: RADIUS.xl,
    padding: SPACING[4], alignItems: 'center',
    borderWidth: 1, borderColor: COLORS.neutral100,
  },
  statValue: { fontSize: FONT_SIZES['3xl'], fontWeight: '800' },
  statLabel: { fontSize: 11, color: COLORS.neutral400, fontWeight: '500', marginTop: 2, textAlign: 'center' },
  sectionCard: {
    backgroundColor: COLORS.white, borderRadius: RADIUS.lg,
    marginBottom: SPACING[4], borderWidth: 1, borderColor: COLORS.neutral100,
    overflow: 'hidden',
  },
  sectionHeader: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: SPACING[4], paddingVertical: SPACING[3],
    borderBottomWidth: 1, borderBottomColor: COLORS.neutral100,
  },
  sectionHeaderLeft: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  sectionTitle: { fontSize: FONT_SIZES.sm, fontWeight: '600', color: COLORS.navy },
  sectionBadge: {
    backgroundColor: 'rgba(29,185,84,0.1)',
    paddingHorizontal: 8, paddingVertical: 2,
    borderRadius: RADIUS.full,
  },
  sectionBadgeText: { fontSize: 11, fontWeight: '600', color: COLORS.primary },
  emptySection: { alignItems: 'center', paddingVertical: SPACING[8], gap: 8 },
  emptyText: { fontSize: FONT_SIZES.sm, color: COLORS.neutral400 },
  taskRow: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: SPACING[4], paddingVertical: SPACING[3],
    borderBottomWidth: 1, borderBottomColor: COLORS.neutral50,
  },
  taskInfo: { flex: 1, marginRight: SPACING[3] },
  taskTitle: { fontSize: FONT_SIZES.sm, fontWeight: '500', color: COLORS.navy },
  taskMeta: { fontSize: 12, color: COLORS.neutral500, marginTop: 2 },
  completeBtn: {
    flexDirection: 'row', alignItems: 'center', gap: 4,
    backgroundColor: COLORS.primary, paddingVertical: 6, paddingHorizontal: 12,
    borderRadius: RADIUS.md,
  },
  completeBtnText: { color: COLORS.white, fontSize: 12, fontWeight: '600' },
  dayLabel: { fontSize: 12, color: COLORS.neutral400 },
  statusBadge: { paddingHorizontal: 8, paddingVertical: 3, borderRadius: RADIUS.full },
  statusBadgeText: { fontSize: 11, fontWeight: '600' },
  activityRow: {
    flexDirection: 'row', alignItems: 'center', gap: 10,
    paddingVertical: 10, paddingHorizontal: SPACING[4],
    borderBottomWidth: 1, borderBottomColor: COLORS.neutral50,
  },
  activityDot: { width: 6, height: 6, borderRadius: 3 },
  activityInfo: { flex: 1 },
  activityTitle: { fontSize: 12, color: COLORS.neutral600 },
  activityMeta: { fontSize: 11, color: COLORS.neutral400, marginTop: 1 },
  activityStatus: { fontSize: 11, fontWeight: '600' },
  bottomNav: {
    flexDirection: 'row', borderTopWidth: 1, borderTopColor: COLORS.neutral200,
    backgroundColor: COLORS.white, paddingVertical: 8,
  },
  bottomNavBtn: {
    flex: 1, alignItems: 'center', justifyContent: 'center', paddingVertical: 4,
  },
  bottomNavText: { fontSize: 10, color: COLORS.neutral600, fontWeight: '500', marginTop: 2 },
});
