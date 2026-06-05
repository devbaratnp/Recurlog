import { useEffect, useState, useCallback } from 'react';
import { View, Text, ScrollView, RefreshControl, StyleSheet, TouchableOpacity } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { Plus, Wrench, Calendar, Bell } from 'lucide-react-native';
import { useAuthStore } from '../../store/authStore';
import { useNotificationStore } from '../../store/notificationStore';
import { StatCard } from '../../components/StatCard';
import { DashboardSkeleton } from '../../components/LoadingSkeleton';
import { customersApi, staffApi, tasksApi, ordersApi } from '../../api/client';
import { COLORS, RADIUS, SPACING, FONT_SIZES, SHADOWS } from '../../constants/theme';
import { todayISO } from '../../utils/date';

interface DashStat {
  total: number;
  completedToday: number;
  pending: number;
  today: number;
  tomorrow: number;
}

export function DashboardScreen() {
  const navigation = useNavigation<any>();
  const user = useAuthStore((s) => s.user);
  const { unreadCount } = useNotificationStore();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [customerStat, setCustomerStat] = useState<DashStat>({ total: 0, completedToday: 0, pending: 0, today: 0, tomorrow: 0 });
  const [orderStat, setOrderStat] = useState<DashStat>({ total: 0, completedToday: 0, pending: 0, today: 0, tomorrow: 0 });
  const [recurringStat, setRecurringStat] = useState<DashStat>({ total: 0, completedToday: 0, pending: 0, today: 0, tomorrow: 0 });
  const [oneTimeStat, setOneTimeStat] = useState<DashStat>({ total: 0, completedToday: 0, pending: 0, today: 0, tomorrow: 0 });

  const fetchStats = useCallback(async () => {
    try {
      const today = todayISO();
      const tomorrow = new Date(); tomorrow.setDate(tomorrow.getDate() + 1);
      const tomorrowStr = tomorrow.toISOString().split('T')[0];

      const [custRes, taskRes, orderRes, svcRes] = await Promise.all([
        customersApi.list(),
        tasksApi.list(),
        ordersApi.list(),
        staffApi.list(),
      ]);

      let tasks = Array.isArray(taskRes.data?.data) ? taskRes.data.data : [];
      let orders = Array.isArray(orderRes.data?.data) ? orderRes.data.data : [];
      let customersList = Array.isArray(custRes.data?.data) ? custRes.data.data : [];

      const ct = tasks.filter((t: any) => t.status === 'completed' && t.completedDate === today).length;
      const cp = tasks.filter((t: any) => t.status === 'pending').length;
      const ctd = tasks.filter((t: any) => t.scheduledDate === today && t.status === 'pending').length;
      const ctm = tasks.filter((t: any) => t.scheduledDate === tomorrowStr && t.status === 'pending').length;

      setCustomerStat({
        total: customersList.length,
        completedToday: ct,
        pending: cp,
        today: ctd,
        tomorrow: ctm,
      });

      const oc = orders.filter((o: any) => o.status === 'completed' && o.completedDate === today).length;
      const op = orders.filter((o: any) => o.status === 'pending' || o.status === 'assigned').length;
      const otd = orders.filter((o: any) => o.scheduledDate === today && (o.status === 'pending' || o.status === 'assigned')).length;
      const otm = orders.filter((o: any) => o.scheduledDate === tomorrowStr && (o.status === 'pending' || o.status === 'assigned')).length;

      setOrderStat({
        total: orders.length,
        completedToday: oc,
        pending: op,
        today: otd,
        tomorrow: otm,
      });

      const recurringTasks = tasks.filter((t: any) => t.title?.toLowerCase().includes('recurring'));
      const oneTimeTasks = tasks.filter((t: any) => !t.title?.toLowerCase().includes('recurring'));
      setRecurringStat({
        total: recurringTasks.length,
        completedToday: recurringTasks.filter((t: any) => t.status === 'completed' && t.completedDate === today).length,
        pending: recurringTasks.filter((t: any) => t.status === 'pending').length,
        today: recurringTasks.filter((t: any) => t.scheduledDate === today && t.status === 'pending').length,
        tomorrow: recurringTasks.filter((t: any) => t.scheduledDate === tomorrowStr && t.status === 'pending').length,
      });
      setOneTimeStat({
        total: oneTimeTasks.length,
        completedToday: oneTimeTasks.filter((t: any) => t.status === 'completed' && t.completedDate === today).length,
        pending: oneTimeTasks.filter((t: any) => t.status === 'pending').length,
        today: oneTimeTasks.filter((t: any) => t.scheduledDate === today && t.status === 'pending').length,
        tomorrow: oneTimeTasks.filter((t: any) => t.scheduledDate === tomorrowStr && t.status === 'pending').length,
      });
    } catch {}
  }, []);

  useEffect(() => {
    const load = async () => {
      setLoading(true);
      await fetchStats();
      setLoading(false);
    };
    load();
  }, []);

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchStats();
    setRefreshing(false);
  };

  const QuickAddButton = ({ label, onPress }: { label: string; onPress: () => void }) => (
    <TouchableOpacity style={styles.quickBtn} onPress={onPress}>
      <Plus size={16} color={COLORS.white} />
      <Text style={styles.quickBtnText}>{label}</Text>
    </TouchableOpacity>
  );

  if (loading) {
    return (
      <View style={styles.container}>
        <View style={styles.header}>
          <View style={styles.headerLeft}>
            <View style={styles.logoSmall}>
              <Wrench size={16} color={COLORS.white} />
            </View>
            <Text style={styles.headerTitle}>Recurlog</Text>
          </View>
          <TouchableOpacity style={styles.bellBtn} onPress={() => navigation.navigate('Notifications')}>
            <Bell size={20} color={COLORS.neutral500} />
            {unreadCount > 0 && (
              <View style={styles.bellBadge}>
                <Text style={styles.bellBadgeText}>{unreadCount > 9 ? '9+' : unreadCount}</Text>
              </View>
            )}
          </TouchableOpacity>
        </View>
        <DashboardSkeleton />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <View style={styles.headerLeft}>
          <View style={styles.logoSmall}>
            <Wrench size={16} color={COLORS.white} />
          </View>
          <Text style={styles.headerTitle}>Recurlog</Text>
        </View>
        <TouchableOpacity style={styles.bellBtn} onPress={() => navigation.navigate('Notifications')}>
          <Bell size={20} color={COLORS.neutral500} />
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
        <Text style={styles.pageTitle}>Dashboard — Overview</Text>

        <View style={styles.quickGrid}>
          <QuickAddButton label="Customer" onPress={() => navigation.navigate('CustomerAdd')} />
          <QuickAddButton label="Order" onPress={() => navigation.navigate('OrderAdd')} />
          <QuickAddButton label="One Time Task" onPress={() => navigation.navigate('TaskAdd', { type: 'onetime' })} />
          <QuickAddButton label="Recurring Task" onPress={() => navigation.navigate('TaskAdd', { type: 'recurring' })} />
        </View>

        {/* Customer Card */}
        <View style={[styles.sectionCard, SHADOWS.sm]}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>Customer</Text>
          </View>
          <View style={styles.statRow}>
            <StatCard label="All" sublabel="Customers" value={customerStat.total} color={COLORS.navy} onPress={() => navigation.navigate('CustomerList')} />
            <StatCard label="Todays" sublabel="Completed" value={customerStat.completedToday} color={COLORS.navy} />
            <StatCard label="To Do" value={customerStat.pending} color={COLORS.danger} />
            <StatCard label="Today" value={customerStat.today} color={COLORS.primary} />
            <StatCard label="Tomorrow" value={customerStat.tomorrow} color={COLORS.primary} />
          </View>
        </View>

        {/* Order Card */}
        <View style={[styles.sectionCard, SHADOWS.sm]}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>Order</Text>
          </View>
          <View style={styles.statRow}>
            <StatCard label="All" sublabel="Orders" value={orderStat.total} color={COLORS.navy} onPress={() => navigation.navigate('OrderList')} />
            <StatCard label="Todays" sublabel="Completed" value={orderStat.completedToday} color={COLORS.navy} />
            <StatCard label="To Deliver" value={orderStat.pending} color={COLORS.danger} />
            <StatCard label="Today" value={orderStat.today} color={COLORS.primary} />
            <StatCard label="Tomorrow" value={orderStat.tomorrow} color={COLORS.primary} />
          </View>
        </View>

        {/* Recurring Tasks */}
        <View style={[styles.sectionCard, SHADOWS.sm]}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>Recurring Tasks</Text>
          </View>
          <View style={styles.statRow}>
            <StatCard label="All" sublabel="Tasks" value={recurringStat.total} color={COLORS.navy} />
            <StatCard label="Todays" sublabel="Completed" value={recurringStat.completedToday} color={COLORS.navy} />
            <StatCard label="Pending" value={recurringStat.pending} color={COLORS.danger} />
            <StatCard label="Today" value={recurringStat.today} color={COLORS.primary} />
            <StatCard label="Tomorrow" value={recurringStat.tomorrow} color={COLORS.primary} />
          </View>
        </View>

        {/* One-Time Tasks */}
        <View style={[styles.sectionCard, SHADOWS.sm]}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>One-Time Tasks</Text>
          </View>
          <View style={styles.statRow}>
            <StatCard label="All" sublabel="Tasks" value={oneTimeStat.total} color={COLORS.navy} />
            <StatCard label="Todays" sublabel="Completed" value={oneTimeStat.completedToday} color={COLORS.navy} />
            <StatCard label="Pending" value={oneTimeStat.pending} color={COLORS.danger} />
            <StatCard label="Today" value={oneTimeStat.today} color={COLORS.primary} />
            <StatCard label="Tomorrow" value={oneTimeStat.tomorrow} color={COLORS.primary} />
          </View>
        </View>

        {/* Staff */}
        <View style={[styles.sectionCard, SHADOWS.sm]}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>Staff</Text>
          </View>
          <TouchableOpacity onPress={() => navigation.navigate('StaffList')}>
            <Text style={styles.linkText}>View Staff Directory →</Text>
          </TouchableOpacity>
        </View>

        {/* Reports */}
        <View style={[styles.sectionCard, SHADOWS.sm]}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>Reports</Text>
          </View>
          <TouchableOpacity onPress={() => navigation.navigate('Reports')}>
            <Text style={styles.linkText}>View Reports →</Text>
          </TouchableOpacity>
        </View>

        <View style={{ height: 80 }} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: COLORS.neutral50 },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: SPACING[4],
    height: 56,
    backgroundColor: COLORS.white,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.neutral200,
  },
  headerLeft: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  logoSmall: {
    width: 28, height: 28, backgroundColor: COLORS.primary,
    borderRadius: RADIUS.lg, alignItems: 'center', justifyContent: 'center',
  },
  headerTitle: { fontSize: FONT_SIZES.sm, fontWeight: '700', color: COLORS.navy },
  bellBtn: { position: 'relative', padding: 8, minWidth: 44, minHeight: 44, alignItems: 'center', justifyContent: 'center' },
  bellBadge: {
    position: 'absolute', top: 4, right: 4,
    backgroundColor: COLORS.danger, width: 16, height: 16,
    borderRadius: 8, alignItems: 'center', justifyContent: 'center',
  },
  bellBadgeText: { color: COLORS.white, fontSize: 10, fontWeight: '700' },
  scrollContent: { padding: SPACING[4] },
  pageTitle: { fontSize: FONT_SIZES.xl, fontWeight: '700', color: COLORS.navy, marginBottom: SPACING[5] },
  quickGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: SPACING[2], marginBottom: SPACING[6] },
  quickBtn: {
    flexDirection: 'row', alignItems: 'center', gap: 6,
    paddingVertical: 14, paddingHorizontal: 16,
    backgroundColor: COLORS.primary, borderRadius: RADIUS.lg,
    minHeight: 48, minWidth: '47%', justifyContent: 'center',
  },
  quickBtnText: { color: COLORS.white, fontSize: FONT_SIZES.sm, fontWeight: '600' },
  sectionCard: {
    backgroundColor: COLORS.white, borderRadius: RADIUS.lg,
    borderWidth: 1, borderColor: COLORS.neutral100,
    marginBottom: SPACING[4], overflow: 'hidden',
  },
  sectionHeader: {
    paddingVertical: SPACING[3], paddingHorizontal: SPACING[5],
    borderBottomWidth: 1, borderBottomColor: COLORS.neutral100,
  },
  sectionTitle: { fontSize: FONT_SIZES.sm, fontWeight: '600', color: COLORS.navy },
  statRow: { flexDirection: 'row', paddingVertical: SPACING[2] },
  linkText: { color: COLORS.primary, fontSize: FONT_SIZES.sm, fontWeight: '500', padding: SPACING[4] },
});
