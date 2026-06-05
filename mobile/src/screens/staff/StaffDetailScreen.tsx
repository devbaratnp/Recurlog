import { useEffect, useState } from 'react';
import { View, Text, ScrollView, Image, StyleSheet, ActivityIndicator, TouchableOpacity } from 'react-native';
import { useNavigation, useRoute } from '@react-navigation/native';
import { ArrowLeft } from 'lucide-react-native';
import { staffApi, tasksApi } from '../../api/client';
import { StatusBadge } from '../../components/StatusBadge';
import { StatCard } from '../../components/StatCard';
import { COLORS, RADIUS, SPACING, FONT_SIZES, SHADOWS } from '../../constants/theme';
import { formatDate } from '../../utils/date';
import type { Staff, Task } from '../../types';

export function StaffDetailScreen() {
  const navigation = useNavigation<any>();
  const route = useRoute<any>();
  const id = route.params?.id;

  const [staff, setStaff] = useState<Staff | null>(null);
  const [tasks, setTasks] = useState<Task[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!id) { navigation.goBack(); return; }
    const load = async () => {
      try {
        const [staffRes, taskRes] = await Promise.all([
          staffApi.get(id),
          tasksApi.list({ assigned_to: id }),
        ]);
        setStaff(staffRes.data.data);
        const list = Array.isArray(taskRes.data.data) ? taskRes.data.data : [];
        setTasks(list.sort((a: any, b: any) => b.scheduledDate.localeCompare(a.scheduledDate)));
      } catch {} finally { setLoading(false); }
    };
    load();
  }, [id]);

  if (loading || !staff) {
    return (
      <View style={[styles.container, { justifyContent: 'center', alignItems: 'center' }]}>
        <ActivityIndicator size="large" color={COLORS.primary} />
      </View>
    );
  }

  const total = tasks.length;
  const completed = tasks.filter((t) => t.status === 'completed').length;
  const missed = tasks.filter((t) => t.status === 'missed').length;
  const rate = total > 0 ? Math.round((completed / total) * 100) : 0;

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <View style={styles.headerLeft}>
          <TouchableOpacity style={styles.backBtn} onPress={() => navigation.goBack()}>
            <ArrowLeft size={20} color={COLORS.neutral600} />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Staff Detail</Text>
        </View>
      </View>

      <ScrollView contentContainerStyle={styles.scroll}>
        <View style={[styles.profileCard, SHADOWS.sm]}>
          <Image source={{ uri: staff.avatar }} style={styles.avatar} />
          <View style={{ marginLeft: SPACING[4], flex: 1 }}>
            <Text style={styles.name}>{staff.name}</Text>
            <Text style={styles.phone}>{staff.phone}</Text>
          </View>
        </View>

        <View style={styles.statGrid}>
          <StatCard label="Total" sublabel="Tasks" value={total} color={COLORS.navy} />
          <StatCard label="Completed" value={completed} color={COLORS.primary} />
          <StatCard label="Missed" value={missed} color={COLORS.danger} />
          <StatCard label="Rate" value={`${rate}%`} color={COLORS.info} />
        </View>

        <Text style={styles.sectionTitle}>Assigned Tasks</Text>
        {tasks.map((task) => (
          <View key={task.id} style={[styles.taskCard, SHADOWS.sm]}>
            <Text style={styles.taskTitle}>{task.title}</Text>
            <Text style={styles.taskDate}>{formatDate(task.scheduledDate)}</Text>
            <StatusBadge status={task.status} />
          </View>
        ))}
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
  headerLeft: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  backBtn: { padding: 8, minWidth: 44 },
  headerTitle: { fontSize: FONT_SIZES.lg, fontWeight: '700', color: COLORS.navy },
  scroll: { padding: SPACING[4] },
  profileCard: {
    backgroundColor: COLORS.white, borderRadius: RADIUS.lg, padding: SPACING[5],
    flexDirection: 'row', alignItems: 'center', borderWidth: 1, borderColor: COLORS.neutral100,
    marginBottom: SPACING[6],
  },
  avatar: { width: 60, height: 60, borderRadius: 30, backgroundColor: COLORS.neutral200 },
  name: { fontSize: FONT_SIZES.lg, fontWeight: '700', color: COLORS.navy },
  phone: { fontSize: FONT_SIZES.sm, color: COLORS.neutral500, marginTop: 2 },
  statGrid: { flexDirection: 'row', gap: 8, marginBottom: SPACING[6] },
  sectionTitle: { fontSize: FONT_SIZES.base, fontWeight: '600', color: COLORS.navy, marginBottom: SPACING[3] },
  taskCard: {
    backgroundColor: COLORS.white, borderRadius: RADIUS.lg, padding: SPACING[4],
    borderWidth: 1, borderColor: COLORS.neutral100, marginBottom: SPACING[2],
  },
  taskTitle: { fontSize: FONT_SIZES.sm, fontWeight: '500', color: COLORS.neutral900 },
  taskDate: { fontSize: FONT_SIZES.xs, color: COLORS.neutral500, marginBottom: 4 },
});
