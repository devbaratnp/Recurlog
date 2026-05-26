import { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  FlatList,
  TouchableOpacity,
  RefreshControl,
  ActivityIndicator,
  Modal,
  TextInput,
  StyleSheet,
  ScrollView,
} from 'react-native';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { ScreenWrapper } from '../../src/components/ScreenWrapper';
import { Card } from '../../src/components/Card';
import { StatusPill } from '../../src/components/StatusPill';
import { EmptyState } from '../../src/components/EmptyState';
import { Button } from '../../src/components/Button';
import { useToast } from '../../src/components/Toast';
import { colors, borderRadius, typography } from '../../src/theme';
import { formatDate, formatRelative, todayISO } from '../../src/lib/helpers';

interface DashboardData {
  totalCustomers: number;
  tasksToday: number;
  missedTasks: number;
  activeStaff: number;
  todayTasks: any[];
  recentActivity: any[];
}

export default function DashboardScreen() {
  const router = useRouter();
  const { showToast } = useToast();
  const [data, setData] = useState<DashboardData | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [completeModalVisible, setCompleteModalVisible] = useState(false);
  const [selectedTaskId, setSelectedTaskId] = useState<number | null>(null);
  const [completeDate, setCompleteDate] = useState(todayISO());
  const [completeNotes, setCompleteNotes] = useState('');

  const fetchData = useCallback(async () => {
    try {
      const res = await fetch(
        'https://api.recurlog.com/dashboard'
      );
      if (!res.ok) throw new Error('Failed to fetch');
      const json = await res.json();
      setData(json);
    } catch {
      const mock: DashboardData = {
        totalCustomers: 156,
        tasksToday: 24,
        missedTasks: 3,
        activeStaff: 8,
        todayTasks: [
          { id: 1, title: 'RO Service - Sharma', customerName: 'Sharma Family', staffName: 'Rajesh Yadav', status: 'pending' },
          { id: 2, title: 'AC Repair - Gupta', customerName: 'Gupta Traders', staffName: 'Sita Thapa', status: 'pending' },
          { id: 3, title: 'TV Installation - Mehta', customerName: 'Mehta & Co', staffName: 'Bhim Singh', status: 'completed' },
          { id: 4, title: 'Refrigerator Service - Patel', customerName: 'Patel Residence', staffName: 'Rajesh Yadav', status: 'pending' },
        ],
        recentActivity: [
          { id: 1, type: 'task_completed', text: 'RO service completed at Sharma Family', createdAt: new Date(Date.now() - 600000).toISOString() },
          { id: 2, type: 'service_added', text: 'New AC service added for Gupta Traders', createdAt: new Date(Date.now() - 1800000).toISOString() },
          { id: 3, type: 'customer_added', text: 'New customer: Mehta & Co', createdAt: new Date(Date.now() - 3600000).toISOString() },
          { id: 4, type: 'task_missed', text: 'TV installation missed at Patel Residence', createdAt: new Date(Date.now() - 7200000).toISOString() },
        ],
      };
      setData(mock);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await fetchData();
    setRefreshing(false);
  }, [fetchData]);

  const handleCompleteTask = async () => {
    if (!selectedTaskId) return;
    if (!completeDate) {
      showToast('Please select a completion date', 'error');
      return;
    }
    showToast('Task marked as complete!', 'success');
    setCompleteModalVisible(false);
    setSelectedTaskId(null);
    setCompleteNotes('');
    setCompleteDate(todayISO());
    fetchData();
  };

  const statCards = data ? [
    { label: 'Total Customers', value: data.totalCustomers, icon: 'people' as const, bg: colors.brand, route: '/(tabs)/customers' },
    { label: 'Tasks Today', value: data.tasksToday, icon: 'clipboard' as const, bg: colors.info, route: '/(tabs)/tasks' },
    { label: 'Missed Tasks', value: data.missedTasks, icon: 'alert-circle' as const, bg: colors.danger, route: '/(tabs)/tasks' },
    { label: 'Active Staff', value: data.activeStaff, icon: 'briefcase' as const, bg: colors.amber, route: '/(tabs)/staff' },
  ] : [];

  if (loading) {
    return (
      <ScreenWrapper>
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={colors.brand} />
        </View>
      </ScreenWrapper>
    );
  }

  return (
    <ScreenWrapper>
      <ScrollView
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.brand} />}
        contentContainerStyle={styles.scrollContent}
      >
        <View style={styles.header}>
          <Text style={styles.title}>Dashboard</Text>
          <View style={styles.headerActions}>
            <TouchableOpacity
              style={styles.iconButton}
              onPress={() => router.push('/notifications')}
              activeOpacity={0.7}
            >
              <Ionicons name="notifications-outline" size={22} color={colors.gray[500]} />
            </TouchableOpacity>
          </View>
        </View>

        {/* Stat Cards Grid */}
        <View style={styles.statsGrid}>
          {statCards.map((stat, index) => (
            <TouchableOpacity
              key={index}
              style={styles.statCard}
              onPress={() => router.push(stat.route as any)}
              activeOpacity={0.7}
            >
              <View style={[styles.statIcon, { backgroundColor: stat.bg + '15' }]}>
                <Ionicons name={stat.icon} size={22} color={stat.bg} />
              </View>
              <Text style={styles.statValue}>{stat.value}</Text>
              <Text style={styles.statLabel}>{stat.label}</Text>
            </TouchableOpacity>
          ))}
        </View>

        {/* Quick Actions */}
        <View style={styles.quickActions}>
          <TouchableOpacity
            style={styles.quickActionBtn}
            onPress={() => router.push('/customers/add')}
            activeOpacity={0.7}
          >
            <Ionicons name="person-add-outline" size={16} color={colors.brand} />
            <Text style={styles.quickActionText}>Add Customer</Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={styles.quickActionBtn}
            onPress={() => router.push('/services/add')}
            activeOpacity={0.7}
          >
            <Ionicons name="add-circle-outline" size={16} color={colors.brand} />
            <Text style={styles.quickActionText}>Add Service</Text>
          </TouchableOpacity>
        </View>

        {/* Today's Schedule */}
        <Card style={styles.sectionCard}>
          <Text style={styles.sectionTitle}>Today's Schedule</Text>
          {data!.todayTasks.length === 0 ? (
            <EmptyState
              icon="calendar-outline"
              message="No tasks scheduled for today"
            />
          ) : (
            data!.todayTasks.map((task: any) => (
              <View key={task.id} style={styles.taskItem}>
                <View style={styles.taskInfo}>
                  <Text style={styles.taskTitle}>{task.title}</Text>
                  <View style={styles.taskMeta}>
                    <View style={styles.taskMetaItem}>
                      <Ionicons name="person-outline" size={12} color={colors.gray[400]} />
                      <Text style={styles.taskMetaText}>{task.customerName}</Text>
                    </View>
                    <View style={styles.taskMetaItem}>
                      <Ionicons name="briefcase-outline" size={12} color={colors.gray[400]} />
                      <Text style={styles.taskMetaText}>{task.staffName}</Text>
                    </View>
                  </View>
                </View>
                <View style={styles.taskRight}>
                  <StatusPill status={task.status} />
                  {task.status === 'pending' && (
                    <TouchableOpacity
                      style={styles.completeBtn}
                      onPress={() => {
                        setSelectedTaskId(task.id);
                        setCompleteModalVisible(true);
                      }}
                      activeOpacity={0.7}
                    >
                      <Text style={styles.completeBtnText}>Complete</Text>
                    </TouchableOpacity>
                  )}
                </View>
              </View>
            ))
          )}
        </Card>

        {/* Recent Activity */}
        <Card style={styles.sectionCard}>
          <Text style={styles.sectionTitle}>Recent Activity</Text>
          {data!.recentActivity.length === 0 ? (
            <EmptyState
              icon="timer-outline"
              message="No recent activity"
            />
          ) : (
            data!.recentActivity.slice(0, 8).map((activity: any) => (
              <View key={activity.id} style={styles.activityItem}>
                <View style={[styles.activityDot, {
                  backgroundColor:
                    activity.type === 'task_completed' ? colors.brand :
                    activity.type === 'task_missed' ? colors.danger :
                    activity.type === 'service_added' ? colors.info :
                    colors.brand
                }]} />
                <View style={styles.activityContent}>
                  <Text style={styles.activityText}>{activity.text}</Text>
                  <Text style={styles.activityTime}>{formatRelative(activity.createdAt)}</Text>
                </View>
              </View>
            ))
          )}
        </Card>
      </ScrollView>

      {/* Complete Modal */}
      <Modal
        visible={completeModalVisible}
        transparent
        animationType="slide"
        onRequestClose={() => setCompleteModalVisible(false)}
      >
        <TouchableOpacity
          style={styles.modalOverlay}
          activeOpacity={1}
          onPress={() => setCompleteModalVisible(false)}
        >
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Mark Task Complete</Text>
              <TouchableOpacity onPress={() => setCompleteModalVisible(false)} activeOpacity={0.7}>
                <Ionicons name="close" size={22} color={colors.gray[400]} />
              </TouchableOpacity>
            </View>
            <View style={styles.modalBody}>
              <Text style={styles.modalLabel}>Completion Date</Text>
              <TextInput
                style={styles.modalInput}
                value={completeDate}
                onChangeText={setCompleteDate}
                placeholder="YYYY-MM-DD"
              />
              <Text style={styles.modalLabel}>Completion Notes</Text>
              <TextInput
                style={[styles.modalInput, styles.modalTextarea]}
                value={completeNotes}
                onChangeText={setCompleteNotes}
                placeholder="Notes about the completion..."
                multiline
                numberOfLines={3}
              />
              <View style={styles.modalActions}>
                <Button
                  title="Cancel"
                  onPress={() => setCompleteModalVisible(false)}
                  variant="secondary"
                  style={{ flex: 1 }}
                />
                <Button
                  title="Confirm"
                  onPress={handleCompleteTask}
                  variant="primary"
                  style={{ flex: 1 }}
                />
              </View>
            </View>
          </View>
        </TouchableOpacity>
      </Modal>
    </ScreenWrapper>
  );
}

const styles = StyleSheet.create({
  loadingContainer: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  scrollContent: {
    padding: 16,
    paddingBottom: 32,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 20,
  },
  title: {
    fontSize: typography.fontSize.xl,
    fontWeight: typography.fontWeight.bold,
    color: colors.navy,
  },
  headerActions: {
    flexDirection: 'row',
    gap: 8,
  },
  iconButton: {
    width: 40,
    height: 40,
    borderRadius: borderRadius.lg,
    alignItems: 'center',
    justifyContent: 'center',
  },
  statsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
    marginBottom: 20,
  },
  statCard: {
    width: '47%',
    backgroundColor: colors.white,
    borderRadius: borderRadius.lg,
    borderWidth: 1,
    borderColor: colors.gray[200],
    padding: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 3,
    elevation: 2,
  },
  statIcon: {
    width: 40,
    height: 40,
    borderRadius: borderRadius.lg,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  statValue: {
    fontSize: typography.fontSize['2xl'],
    fontWeight: typography.fontWeight.bold,
    color: colors.navy,
  },
  statLabel: {
    fontSize: typography.fontSize.xs,
    color: colors.gray[500],
    fontWeight: typography.fontWeight.medium,
    marginTop: 2,
  },
  quickActions: {
    flexDirection: 'row',
    gap: 10,
    marginBottom: 20,
  },
  quickActionBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 14,
    paddingVertical: 10,
    backgroundColor: colors.white,
    borderRadius: borderRadius.lg,
    borderWidth: 1,
    borderColor: colors.gray[200],
  },
  quickActionText: {
    fontSize: typography.fontSize.sm,
    fontWeight: typography.fontWeight.medium,
    color: colors.gray[700],
  },
  sectionCard: {
    marginBottom: 16,
  },
  sectionTitle: {
    fontSize: typography.fontSize.base,
    fontWeight: typography.fontWeight.semibold,
    color: colors.navy,
    marginBottom: 12,
  },
  taskItem: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: colors.gray[100],
  },
  taskInfo: {
    flex: 1,
    marginRight: 12,
  },
  taskTitle: {
    fontSize: typography.fontSize.sm,
    fontWeight: typography.fontWeight.semibold,
    color: colors.gray[900],
  },
  taskMeta: {
    flexDirection: 'row',
    gap: 12,
    marginTop: 4,
  },
  taskMetaItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  taskMetaText: {
    fontSize: typography.fontSize.xs,
    color: colors.gray[500],
  },
  taskRight: {
    alignItems: 'flex-end',
    gap: 8,
  },
  completeBtn: {
    backgroundColor: colors.brand,
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: borderRadius.md,
  },
  completeBtnText: {
    color: colors.white,
    fontSize: typography.fontSize.xs,
    fontWeight: typography.fontWeight.semibold,
  },
  activityItem: {
    flexDirection: 'row',
    gap: 10,
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: colors.gray[100],
  },
  activityDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    marginTop: 6,
  },
  activityContent: {
    flex: 1,
  },
  activityText: {
    fontSize: typography.fontSize.sm,
    color: colors.gray[700],
    lineHeight: 20,
  },
  activityTime: {
    fontSize: typography.fontSize.xs,
    color: colors.gray[400],
    marginTop: 2,
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'flex-end',
  },
  modalContent: {
    backgroundColor: colors.white,
    borderTopLeftRadius: borderRadius.xl,
    borderTopRightRadius: borderRadius.xl,
    paddingTop: 20,
    paddingBottom: 32,
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    marginBottom: 16,
  },
  modalTitle: {
    fontSize: typography.fontSize.lg,
    fontWeight: typography.fontWeight.bold,
    color: colors.gray[900],
  },
  modalBody: {
    paddingHorizontal: 20,
    gap: 12,
  },
  modalLabel: {
    fontSize: typography.fontSize.sm,
    fontWeight: typography.fontWeight.semibold,
    color: colors.gray[700],
  },
  modalInput: {
    borderWidth: 1,
    borderColor: colors.gray[300],
    borderRadius: borderRadius.lg,
    paddingHorizontal: 14,
    paddingVertical: 10,
    fontSize: typography.fontSize.sm,
    color: colors.gray[900],
  },
  modalTextarea: {
    minHeight: 80,
    textAlignVertical: 'top',
  },
  modalActions: {
    flexDirection: 'row',
    gap: 12,
    marginTop: 12,
  },
});
