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
import { useRouter, useLocalSearchParams, Stack } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { ScreenWrapper } from '../../src/components/ScreenWrapper';
import { Card } from '../../src/components/Card';
import { StatusPill } from '../../src/components/StatusPill';
import { EmptyState } from '../../src/components/EmptyState';
import { colors, borderRadius, typography } from '../../src/theme';
import { formatDate } from '../../src/lib/helpers';

interface StaffDetail {
  id: number;
  name: string;
  phone: string;
  avatar: string;
}

interface StaffStats {
  total: number;
  completed: number;
  missed: number;
  completionRate: number;
}

interface Task {
  id: number;
  title: string;
  customerName: string;
  scheduledDate: string;
  status: string;
}

export default function StaffDetailScreen() {
  const router = useRouter();
  const params = useLocalSearchParams();
  const id = params.id as string;
  const [staff, setStaff] = useState<StaffDetail | null>(null);
  const [stats, setStats] = useState<StaffStats>({ total: 0, completed: 0, missed: 0, completionRate: 0 });
  const [tasks, setTasks] = useState<Task[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchData = useCallback(async () => {
    try {
      const res = await fetch(`https://api.recurlog.com/staff/${id}`);
      if (!res.ok) throw new Error('Failed');
      const json = await res.json();
      setStaff(json);
      setStats(json.stats || { total: 0, completed: 0, missed: 0, completionRate: 0 });
      setTasks(json.tasks || []);
    } catch {
      const mockStaff: Record<string, StaffDetail> = {
        '1': { id: 1, name: 'Rajesh Yadav', phone: '+977-9812345678', avatar: 'https://ui-avatars.com/api/?name=Rajesh+Yadav&background=1DB954&color=fff&size=128' },
        '2': { id: 2, name: 'Sita Thapa', phone: '+977-9845678901', avatar: 'https://ui-avatars.com/api/?name=Sita+Thapa&background=F59E0B&color=fff&size=128' },
        '3': { id: 3, name: 'Bhim Singh', phone: '+977-9876543210', avatar: 'https://ui-avatars.com/api/?name=Bhim+Singh&background=EF4444&color=fff&size=128' },
      };
      const s = mockStaff[id] || { id: Number(id), name: 'Staff Member', phone: '+977-XXXXXXXXXX', avatar: `https://ui-avatars.com/api/?name=Staff+Member&background=1DB954&color=fff&size=128` };
      setStaff(s);
      setStats({ total: 44, completed: 42, missed: 2, completionRate: 95 });
      setTasks([
        { id: 1, title: 'RO Service - Sharma Family', customerName: 'Sharma Family', scheduledDate: '2026-05-22', status: 'pending' },
        { id: 2, title: 'AC Repair - Gupta Traders', customerName: 'Gupta Traders', scheduledDate: '2026-05-22', status: 'pending' },
        { id: 3, title: 'TV Installation - Mehta & Co', customerName: 'Mehta & Co', scheduledDate: '2026-05-20', status: 'completed' },
        { id: 4, title: 'Refrigerator Service - Patel', customerName: 'Patel Residence', scheduledDate: '2026-05-19', status: 'completed' },
        { id: 5, title: 'Washing Machine Repair - Thapa', customerName: 'Thapa Family', scheduledDate: '2026-05-18', status: 'missed' },
      ]);
    } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await fetchData();
    setRefreshing(false);
  }, [fetchData]);

  if (loading) {
    return (
      <ScreenWrapper>
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={colors.brand} />
        </View>
      </ScreenWrapper>
    );
  }

  if (!staff) {
    return (
      <ScreenWrapper>
        <EmptyState icon="person-outline" message="Staff member not found" />
      </ScreenWrapper>
    );
  }

  return (
    <ScreenWrapper>
      <Stack.Screen options={{ title: staff.name }} />
      <FlatList
        data={tasks}
        keyExtractor={(item) => item.id.toString()}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.brand} />}
        contentContainerStyle={styles.listContent}
        ListHeaderComponent={
          <>
            <View style={styles.header}>
              <TouchableOpacity onPress={() => router.back()} activeOpacity={0.7}>
                <Ionicons name="arrow-back" size={24} color={colors.gray[400]} />
              </TouchableOpacity>
              <Text style={styles.title}>{staff.name}</Text>
            </View>

            {/* Profile Card */}
            <Card style={styles.profileCard}>
              <View style={styles.profileRow}>
                <View style={styles.avatar}>
                  <Text style={styles.avatarText}>
                    {staff.name.split(' ').map(n => n[0]).join('')}
                  </Text>
                </View>
                <View style={styles.profileInfo}>
                  <Text style={styles.profileName}>{staff.name}</Text>
                  <Text style={styles.profilePhone}>{staff.phone}</Text>
                </View>
              </View>

              <View style={styles.statsGrid}>
                <View style={styles.statBox}>
                  <Text style={styles.statNumber}>{stats.total}</Text>
                  <Text style={styles.statLabel}>Total Tasks</Text>
                </View>
                <View style={styles.statBox}>
                  <Text style={[styles.statNumber, { color: colors.brand }]}>{stats.completed}</Text>
                  <Text style={styles.statLabel}>Completed</Text>
                </View>
                <View style={styles.statBox}>
                  <Text style={[styles.statNumber, { color: colors.danger }]}>{stats.missed}</Text>
                  <Text style={styles.statLabel}>Missed</Text>
                </View>
                <View style={styles.statBox}>
                  <Text style={[styles.statNumber, {
                    color: stats.completionRate >= 90 ? colors.brand : stats.completionRate >= 70 ? colors.amber : colors.danger
                  }]}>
                    {stats.completionRate}%
                  </Text>
                  <Text style={styles.statLabel}>Rate</Text>
                </View>
              </View>
            </Card>

            {/* Assigned Tasks Header */}
            <View style={styles.sectionHeader}>
              <Ionicons name="clipboard-outline" size={18} color={colors.brand} />
              <Text style={styles.sectionTitle}>Assigned Tasks</Text>
            </View>
          </>
        }
        ListEmptyComponent={
          <Card>
            <EmptyState icon="clipboard-outline" message="No tasks assigned" />
          </Card>
        }
        renderItem={({ item }) => (
          <Card style={styles.taskCard}>
            <View style={styles.taskRow}>
              <View style={styles.taskInfo}>
                <Text style={styles.taskTitle}>{item.title}</Text>
                <Text style={styles.taskCustomer}>{item.customerName}</Text>
                <Text style={styles.taskDate}>{formatDate(item.scheduledDate)}</Text>
              </View>
              <StatusPill status={item.status} />
            </View>
          </Card>
        )}
      />
    </ScreenWrapper>
  );
}

const styles = StyleSheet.create({
  loadingContainer: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  listContent: {
    padding: 16,
    paddingBottom: 32,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 20,
  },
  title: {
    fontSize: typography.fontSize.xl,
    fontWeight: typography.fontWeight.bold,
    color: colors.navy,
  },
  profileCard: {
    marginBottom: 24,
    gap: 20,
  },
  profileRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 16,
  },
  avatar: {
    width: 64,
    height: 64,
    borderRadius: 32,
    backgroundColor: colors.brand,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: {
    fontSize: typography.fontSize['2xl'],
    fontWeight: typography.fontWeight.bold,
    color: colors.white,
  },
  profileInfo: {
    flex: 1,
  },
  profileName: {
    fontSize: typography.fontSize.lg,
    fontWeight: typography.fontWeight.bold,
    color: colors.navy,
  },
  profilePhone: {
    fontSize: typography.fontSize.sm,
    color: colors.gray[500],
    marginTop: 2,
  },
  statsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  statBox: {
    width: '48%',
    backgroundColor: colors.gray[50],
    borderRadius: borderRadius.md,
    padding: 12,
    alignItems: 'center',
  },
  statNumber: {
    fontSize: typography.fontSize['2xl'],
    fontWeight: typography.fontWeight.bold,
    color: colors.navy,
  },
  statLabel: {
    fontSize: typography.fontSize.xs,
    color: colors.gray[500],
    marginTop: 2,
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 12,
  },
  sectionTitle: {
    fontSize: typography.fontSize.base,
    fontWeight: typography.fontWeight.semibold,
    color: colors.navy,
  },
  taskCard: {
    marginBottom: 8,
  },
  taskRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  taskInfo: {
    flex: 1,
    marginRight: 12,
  },
  taskTitle: {
    fontSize: typography.fontSize.sm,
    fontWeight: typography.fontWeight.semibold,
    color: colors.navy,
  },
  taskCustomer: {
    fontSize: typography.fontSize.xs,
    color: colors.gray[500],
    marginTop: 2,
  },
  taskDate: {
    fontSize: typography.fontSize.xs,
    color: colors.gray[400],
    marginTop: 2,
  },
});
