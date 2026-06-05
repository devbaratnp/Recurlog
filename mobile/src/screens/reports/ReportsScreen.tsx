import { useEffect, useState, useCallback } from 'react';
import { View, Text, ScrollView, TouchableOpacity, RefreshControl, StyleSheet } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { ArrowLeft } from 'lucide-react-native';
import { tasksApi, staffApi, categoriesApi } from '../../api/client';
import { StatusBadge } from '../../components/StatusBadge';
import { EmptyState } from '../../components/EmptyState';
import { COLORS, RADIUS, SPACING, FONT_SIZES, SHADOWS } from '../../constants/theme';
import { formatDate } from '../../utils/date';
import type { Task, Staff, Category } from '../../types';

export function ReportsScreen() {
  const navigation = useNavigation<any>();
  const [allTasks, setAllTasks] = useState<Task[]>([]);
  const [staff, setStaff] = useState<Staff[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchData = useCallback(async () => {
    try {
      const [taskRes, staffRes, catRes] = await Promise.all([
        tasksApi.list(),
        staffApi.list(),
        categoriesApi.list(),
      ]);
      setAllTasks(Array.isArray(taskRes.data.data) ? taskRes.data.data : []);
      setStaff(Array.isArray(staffRes.data.data) ? staffRes.data.data : []);
      setCategories(Array.isArray(catRes.data.data) ? catRes.data.data : []);
    } catch {} finally { setLoading(false); }
  }, []);

  useEffect(() => { fetchData(); }, []);

  const onRefresh = async () => { setRefreshing(true); await fetchData(); setRefreshing(false); };

  const recurringTasks = allTasks.filter((t) => {
    const s = (t as any).serviceProblem;
    return typeof s !== 'undefined';
  });
  const completed = allTasks.filter((t) => t.status === 'completed').length;
  const missed = allTasks.filter((t) => t.status === 'missed').length;
  const pending = allTasks.filter((t) => t.status === 'pending').length;
  const completionRate = allTasks.length > 0 ? Math.round((completed / allTasks.length) * 100) : 0;

  const statCards = [
    { label: 'Total Tasks', value: allTasks.length, color: COLORS.navy },
    { label: 'Completed', value: completed, color: COLORS.primary },
    { label: 'Missed', value: missed, color: COLORS.danger },
    { label: 'Rate', value: `${completionRate}%`, color: COLORS.info },
  ];

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
          <ArrowLeft size={20} color={COLORS.neutral600} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Reports</Text>
      </View>

      <ScrollView contentContainerStyle={styles.scroll}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={COLORS.primary} />}
      >
        <View style={styles.statRow}>
          {statCards.map((s, i) => (
            <View key={i} style={[styles.statCard, SHADOWS.sm]}>
              <Text style={styles.statLabel}>{s.label}</Text>
              <Text style={[styles.statValue, { color: s.color }]}>{s.value}</Text>
            </View>
          ))}
        </View>

        {/* Staff Wise */}
        <View style={[styles.sectionCard, SHADOWS.sm]}>
          <Text style={styles.sectionTitle}>Staff Wise</Text>
          {staff.length === 0 ? <EmptyState title="No staff data" /> : (
            staff.map((s) => {
              const staffTasks = allTasks.filter((t) => t.assignedTo === s.id);
              const sc = staffTasks.filter((t) => t.status === 'completed').length;
              const st = staffTasks.length;
              return (
                <View key={s.id} style={styles.reportRow}>
                  <Text style={styles.reportLabel}>{s.name}</Text>
                  <View style={styles.reportBarBg}>
                    <View style={[styles.reportBarFill, { width: `${st > 0 ? (sc / st) * 100 : 0}%` }]} />
                  </View>
                  <Text style={styles.reportValue}>{st > 0 ? Math.round((sc / st) * 100) : 0}%</Text>
                </View>
              );
            })
          )}
        </View>

        {/* Category Wise */}
        <View style={[styles.sectionCard, SHADOWS.sm]}>
          <Text style={styles.sectionTitle}>Category Wise</Text>
          {categories.length === 0 ? <EmptyState title="No category data" /> : (
            categories.map((cat) => {
              const catTasks = allTasks.filter((t) => t.categoryId === cat.id);
              const cc = catTasks.filter((t) => t.status === 'completed').length;
              const ct = catTasks.length;
              return (
                <View key={cat.id} style={styles.reportRow}>
                  <Text style={styles.reportLabel}>{cat.name}</Text>
                  <View style={styles.reportBarBg}>
                    <View style={[styles.reportBarFill, { backgroundColor: cat.color, width: `${ct > 0 ? (cc / ct) * 100 : 0}%` }]} />
                  </View>
                  <Text style={styles.reportValue}>{ct > 0 ? Math.round((cc / ct) * 100) : 0}%</Text>
                </View>
              );
            })
          )}
        </View>

        {/* Recent Tasks */}
        <View style={[styles.sectionCard, SHADOWS.sm]}>
          <Text style={styles.sectionTitle}>Recent Tasks</Text>
          {allTasks.slice(0, 20).map((t) => (
            <View key={t.id} style={styles.taskRow}>
              <View style={{ flex: 1 }}>
                <Text style={styles.taskTitle}>{t.title}</Text>
                <Text style={styles.taskDate}>{formatDate(t.scheduledDate)}</Text>
              </View>
              <StatusBadge status={t.status} />
            </View>
          ))}
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
    height: 56, backgroundColor: COLORS.white, borderBottomWidth: 1, borderBottomColor: COLORS.neutral200,
  },
  backBtn: { padding: 8, minWidth: 44, minHeight: 44, justifyContent: 'center' },
  headerTitle: { fontSize: FONT_SIZES.lg, fontWeight: '700', color: COLORS.navy, marginLeft: 4 },
  scroll: { padding: SPACING[4] },
  statRow: { flexDirection: 'row', gap: 8, marginBottom: SPACING[6] },
  statCard: {
    flex: 1, backgroundColor: COLORS.white, borderRadius: RADIUS.lg,
    padding: SPACING[3], alignItems: 'center', borderWidth: 1, borderColor: COLORS.neutral100,
  },
  statLabel: { fontSize: FONT_SIZES.xs, color: COLORS.neutral400, fontWeight: '500' },
  statValue: { fontSize: FONT_SIZES.xl, fontWeight: '700' },
  sectionCard: {
    backgroundColor: COLORS.white, borderRadius: RADIUS.lg, padding: SPACING[4],
    borderWidth: 1, borderColor: COLORS.neutral100, marginBottom: SPACING[4],
  },
  sectionTitle: { fontSize: FONT_SIZES.base, fontWeight: '600', color: COLORS.navy, marginBottom: SPACING[3] },
  reportRow: { flexDirection: 'row', alignItems: 'center', gap: 8, marginBottom: 8 },
  reportLabel: { width: 100, fontSize: FONT_SIZES.xs, color: COLORS.neutral700 },
  reportBarBg: { flex: 1, height: 8, backgroundColor: COLORS.neutral200, borderRadius: 4, overflow: 'hidden' },
  reportBarFill: { height: '100%', backgroundColor: COLORS.primary, borderRadius: 4 },
  reportValue: { width: 40, fontSize: FONT_SIZES.xs, fontWeight: '600', textAlign: 'right', color: COLORS.neutral700 },
  taskRow: {
    flexDirection: 'row', alignItems: 'center', paddingVertical: 8,
    borderBottomWidth: 1, borderBottomColor: COLORS.neutral50,
  },
  taskTitle: { fontSize: FONT_SIZES.sm, color: COLORS.neutral800 },
  taskDate: { fontSize: FONT_SIZES.xs, color: COLORS.neutral400, marginTop: 2 },
});
