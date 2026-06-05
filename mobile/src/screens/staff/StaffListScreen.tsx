import { useEffect, useState, useCallback } from 'react';
import { View, Text, FlatList, TouchableOpacity, RefreshControl, Image, StyleSheet, Alert } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import { ArrowLeft } from 'lucide-react-native';
import { staffApi, tasksApi } from '../../api/client';
import { EmptyState } from '../../components/EmptyState';
import { COLORS, RADIUS, SPACING, FONT_SIZES, SHADOWS } from '../../constants/theme';
import type { Staff, Task } from '../../types';

export function StaffListScreen() {
  const navigation = useNavigation<any>();
  const insets = useSafeAreaInsets();
  const [staff, setStaff] = useState<(Staff & { completionRate?: number; activeTaskCount?: number })[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchStaff = useCallback(async () => {
    try {
      const [staffRes, taskRes] = await Promise.all([staffApi.list(), tasksApi.list()]);
      const staffList = Array.isArray(staffRes.data?.data) ? staffRes.data.data : [];
      const allTasks = Array.isArray(taskRes.data?.data) ? taskRes.data.data : [];

      const enriched = staffList.map((s: Staff) => {
        const staffTasks = allTasks.filter((t: Task) => t.assignedTo === s.id);
        const total = staffTasks.length;
        const completed = staffTasks.filter((t: Task) => t.status === 'completed').length;
        return {
          ...s,
          activeTaskCount: total,
          completionRate: total > 0 ? Math.round((completed / total) * 100) : 0,
        };
      });
      setStaff(enriched);
    } catch { Alert.alert('Error', 'Failed to load staff'); } finally { setLoading(false); }
  }, []);

  useEffect(() => { fetchStaff(); }, []);

  const onRefresh = async () => { setRefreshing(true); await fetchStaff(); setRefreshing(false); };

  const renderStaff = ({ item }: { item: any }) => (
    <TouchableOpacity
      style={[styles.card, SHADOWS.sm]}
      onPress={() => navigation.navigate('DashboardTab', { screen: 'StaffDetail', params: { id: item.id } })}
    >
      <Image source={{ uri: item.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(item.name)}&background=1DB954&color=fff&size=200` }} style={styles.avatar} />
      <View style={styles.info}>
        <Text style={styles.name}>{item.name}</Text>
        <Text style={styles.phone}>{item.phone}</Text>
        <View style={styles.statsRow}>
          <Text style={styles.stat}>{item.activeTaskCount} tasks</Text>
          <View style={styles.progressBg}>
            <View style={[styles.progressFill, { width: `${item.completionRate}%` }]} />
          </View>
          <Text style={styles.stat}>{item.completionRate}%</Text>
        </View>
      </View>
    </TouchableOpacity>
  );

  return (
    <View style={styles.container}>
      <View style={[styles.header, { paddingTop: insets.top, minHeight: 56 + insets.top }]}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
          <ArrowLeft size={20} color={COLORS.neutral600} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Staff</Text>
      </View>

      <FlatList
        data={staff}
        keyExtractor={(item) => String(item.id)}
        renderItem={renderStaff}
        contentContainerStyle={styles.list}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={COLORS.primary} />}
        ListEmptyComponent={!loading ? <EmptyState title="No staff found" /> : null}
        windowSize={10}
        removeClippedSubviews
        maxToRenderPerBatch={10}
      />
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
  list: { padding: SPACING[4], paddingBottom: 80 },
  card: {
    backgroundColor: COLORS.white, borderRadius: RADIUS.lg, padding: SPACING[4],
    flexDirection: 'row', gap: 12, borderWidth: 1, borderColor: COLORS.neutral100,
    marginBottom: SPACING[3],
  },
  avatar: { width: 48, height: 48, borderRadius: 24, backgroundColor: COLORS.neutral200 },
  info: { flex: 1 },
  name: { fontSize: FONT_SIZES.sm, fontWeight: '600', color: COLORS.neutral900 },
  phone: { fontSize: FONT_SIZES.xs, color: COLORS.neutral500, marginBottom: 6 },
  statsRow: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  stat: { fontSize: FONT_SIZES.xs, color: COLORS.neutral500 },
  progressBg: { flex: 1, height: 6, backgroundColor: COLORS.neutral200, borderRadius: 3, overflow: 'hidden' },
  progressFill: { height: '100%', backgroundColor: COLORS.primary, borderRadius: 3 },
});
