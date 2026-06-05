import { useEffect, useState, useCallback } from 'react';
import { View, Text, ScrollView, TouchableOpacity, RefreshControl, StyleSheet, TextInput, Alert } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import { ArrowLeft, ChevronLeft, ChevronRight, Calendar, CheckCircle, ClipboardList } from 'lucide-react-native';
import { tasksApi, ordersApi } from '../../api/client';
import { StatusBadge } from '../../components/StatusBadge';
import { EmptyState } from '../../components/EmptyState';
import { COLORS, RADIUS, SPACING, FONT_SIZES, SHADOWS } from '../../constants/theme';
import { formatDate, todayISO } from '../../utils/date';
import type { Task, Order } from '../../types';

export function DaybookScreen() {
  const navigation = useNavigation<any>();
  const insets = useSafeAreaInsets();
  const [selectedDate, setSelectedDate] = useState(todayISO());
  const [tasks, setTasks] = useState<Task[]>([]);
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchData = useCallback(async () => {
    try {
      const [taskRes, orderRes] = await Promise.all([
        tasksApi.list({ scheduled_date: selectedDate }),
        ordersApi.list(),
      ]);
      setTasks(Array.isArray(taskRes.data?.data) ? taskRes.data.data : []);
      const allOrders = Array.isArray(orderRes.data?.data) ? orderRes.data.data : [];
      setOrders(allOrders.filter((o: Order) => o.scheduledDate === selectedDate || o.completedDate === selectedDate));
    } catch { Alert.alert('Error', 'Failed to load daybook'); } finally { setLoading(false); }
  }, [selectedDate]);

  useEffect(() => { fetchData(); }, [fetchData]);

  const onRefresh = async () => { setRefreshing(true); await fetchData(); setRefreshing(false); };

  const scheduledTasks = tasks.filter((t) => t.status === 'pending');
  const completedTasks = tasks.filter((t) => t.status === 'completed');

  const goPrevDay = () => {
    const d = new Date(selectedDate);
    d.setDate(d.getDate() - 1);
    setSelectedDate(d.toISOString().split('T')[0]);
  };

  const goNextDay = () => {
    const d = new Date(selectedDate);
    d.setDate(d.getDate() + 1);
    setSelectedDate(d.toISOString().split('T')[0]);
  };

  const goToday = () => setSelectedDate(todayISO());

  const formatDayLabel = (dateStr: string) => {
    const d = new Date(dateStr + 'T00:00:00');
    const today = new Date(todayISO() + 'T00:00:00');
    const diff = Math.round((d.getTime() - today.getTime()) / 86400000);
    if (diff === 0) return 'Today';
    if (diff === -1) return 'Yesterday';
    if (diff === 1) return 'Tomorrow';
    return formatDate(dateStr);
  };

  return (
    <View style={styles.container}>
      <View style={[styles.header, { paddingTop: insets.top, minHeight: 56 + insets.top }]}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
          <ArrowLeft size={20} color={COLORS.neutral600} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Daybook</Text>
      </View>

      <ScrollView
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={COLORS.primary} />}
      >
        {/* Date navigator */}
        <View style={[styles.dateCard, SHADOWS.sm]}>
          <View style={styles.dateNav}>
            <TouchableOpacity style={styles.dateNavBtn} onPress={goPrevDay}>
              <ChevronLeft size={16} color={COLORS.neutral600} />
              <Text style={styles.dateNavBtnText}>Prev</Text>
            </TouchableOpacity>
            <View style={styles.dateCenter}>
              <TextInput
                style={styles.dateInput}
                value={selectedDate}
                onChangeText={setSelectedDate}
              />
              <TouchableOpacity style={styles.todayBtn} onPress={goToday}>
                <Text style={styles.todayBtnText}>Today</Text>
              </TouchableOpacity>
            </View>
            <TouchableOpacity style={styles.dateNavBtn} onPress={goNextDay}>
              <Text style={styles.dateNavBtnText}>Next</Text>
              <ChevronRight size={16} color={COLORS.neutral600} />
            </TouchableOpacity>
          </View>
          <Text style={styles.dayLabel}>{formatDayLabel(selectedDate)}</Text>
        </View>

        {/* Summary */}
        <View style={styles.summaryGrid}>
          <View style={[styles.summaryCard, SHADOWS.sm]}>
            <Text style={styles.summaryLabel}>Scheduled</Text>
            <Text style={[styles.summaryValue, { color: COLORS.navy }]}>{scheduledTasks.length}</Text>
          </View>
          <View style={[styles.summaryCard, SHADOWS.sm]}>
            <Text style={styles.summaryLabel}>Completed</Text>
            <Text style={[styles.summaryValue, { color: COLORS.primary }]}>{completedTasks.length}</Text>
          </View>
          <View style={[styles.summaryCard, SHADOWS.sm]}>
            <Text style={styles.summaryLabel}>Orders Created</Text>
            <Text style={[styles.summaryValue, { color: COLORS.navy }]}>{orders.filter((o) => o.scheduledDate === selectedDate).length}</Text>
          </View>
          <View style={[styles.summaryCard, SHADOWS.sm]}>
            <Text style={styles.summaryLabel}>Orders Done</Text>
            <Text style={[styles.summaryValue, { color: COLORS.primary }]}>{orders.filter((o) => o.completedDate === selectedDate).length}</Text>
          </View>
        </View>

        {/* Tasks Scheduled */}
        <View style={[styles.sectionCard, SHADOWS.sm]}>
          <View style={styles.sectionHeader}>
            <Calendar size={14} color={COLORS.neutral400} />
            <Text style={styles.sectionTitle}>Tasks Scheduled</Text>
          </View>
          {scheduledTasks.length === 0 ? (
            <EmptyState title="No tasks scheduled" />
          ) : (
            scheduledTasks.map((task) => (
              <View key={task.id} style={styles.entryRow}>
                <Text style={styles.entryTitle}>{task.title}</Text>
                <Text style={styles.entryMeta}>{task.customerName || '—'}</Text>
                <StatusBadge status={task.status} />
              </View>
            ))
          )}
        </View>

        {/* Tasks Completed */}
        <View style={[styles.sectionCard, SHADOWS.sm]}>
          <View style={styles.sectionHeader}>
            <CheckCircle size={14} color={COLORS.primary} />
            <Text style={styles.sectionTitle}>Tasks Completed</Text>
          </View>
          {completedTasks.length === 0 ? (
            <EmptyState title="No tasks completed" />
          ) : (
            completedTasks.map((task) => (
              <View key={task.id} style={styles.entryRow}>
                <Text style={styles.entryTitle}>{task.title}</Text>
                <Text style={styles.entryMeta}>{task.customerName || '—'}</Text>
                <StatusBadge status={task.status} />
              </View>
            ))
          )}
        </View>

        {/* Order Activity */}
        <View style={[styles.sectionCard, SHADOWS.sm]}>
          <View style={styles.sectionHeader}>
            <ClipboardList size={14} color={COLORS.neutral400} />
            <Text style={styles.sectionTitle}>Order Activity</Text>
          </View>
          {orders.length === 0 ? (
            <EmptyState title="No order activity" />
          ) : (
            orders.map((order) => (
              <View key={order.id} style={styles.entryRow}>
                <Text style={styles.entryTitle}>{order.customerName} — {order.serviceFor}</Text>
                <Text style={styles.entryMeta}>{order.problem}</Text>
                <StatusBadge status={order.status} />
              </View>
            ))
          )}
        </View>

        <View style={{ height: 80 }} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: COLORS.neutral50 },
  header: {
    flexDirection: 'row', alignItems: 'center', paddingHorizontal: SPACING[4],
    backgroundColor: COLORS.white, borderBottomWidth: 1, borderBottomColor: COLORS.neutral200,
  },
  backBtn: { padding: 8, minWidth: 44, minHeight: 44, justifyContent: 'center' },
  headerTitle: { fontSize: FONT_SIZES.lg, fontWeight: '700', color: COLORS.navy, marginLeft: 4 },
  dateCard: {
    backgroundColor: COLORS.white, borderRadius: RADIUS.lg, padding: SPACING[4],
    margin: SPACING[4], borderWidth: 1, borderColor: COLORS.neutral100,
  },
  dateNav: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  dateNavBtn: { flexDirection: 'row', alignItems: 'center', gap: 4, padding: 8 },
  dateNavBtnText: { fontSize: FONT_SIZES.sm, color: COLORS.neutral600, fontWeight: '500' },
  dateCenter: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  dateInput: {
    height: 36, borderWidth: 1, borderColor: COLORS.neutral200, borderRadius: RADIUS.md,
    paddingHorizontal: 8, fontSize: FONT_SIZES.sm, color: COLORS.neutral900, backgroundColor: COLORS.white,
    width: 120, textAlign: 'center',
  },
  todayBtn: { paddingHorizontal: 12, paddingVertical: 6, borderRadius: RADIUS.md, backgroundColor: COLORS.neutral100 },
  todayBtnText: { fontSize: FONT_SIZES.xs, fontWeight: '600', color: COLORS.neutral600 },
  dayLabel: { textAlign: 'center', fontSize: FONT_SIZES.sm, fontWeight: '600', color: COLORS.navy, marginTop: SPACING[2] },
  summaryGrid: { flexDirection: 'row', gap: 8, marginHorizontal: SPACING[4], marginBottom: SPACING[4] },
  summaryCard: {
    flex: 1, backgroundColor: COLORS.white, borderRadius: RADIUS.lg, padding: SPACING[3],
    alignItems: 'center', borderWidth: 1, borderColor: COLORS.neutral100,
  },
  summaryLabel: { fontSize: FONT_SIZES.xs, color: COLORS.neutral400, fontWeight: '500', textAlign: 'center' },
  summaryValue: { fontSize: FONT_SIZES['2xl'], fontWeight: '700', marginTop: 2 },
  sectionCard: {
    backgroundColor: COLORS.white, borderRadius: RADIUS.lg,
    marginHorizontal: SPACING[4], marginBottom: SPACING[4],
    borderWidth: 1, borderColor: COLORS.neutral100, overflow: 'hidden',
  },
  sectionHeader: {
    flexDirection: 'row', alignItems: 'center', gap: 6,
    padding: SPACING[4], borderBottomWidth: 1, borderBottomColor: COLORS.neutral100,
  },
  sectionTitle: { fontSize: FONT_SIZES.sm, fontWeight: '600', color: COLORS.navy },
  entryRow: {
    flexDirection: 'row', alignItems: 'center', gap: 8,
    paddingVertical: 10, paddingHorizontal: SPACING[4],
    borderBottomWidth: 1, borderBottomColor: COLORS.neutral50,
  },
  entryTitle: { flex: 1, fontSize: FONT_SIZES.sm, color: COLORS.neutral800, fontWeight: '500' },
  entryMeta: { fontSize: FONT_SIZES.xs, color: COLORS.neutral400, maxWidth: 120 },
});
