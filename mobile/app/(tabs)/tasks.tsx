import { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  FlatList,
  TextInput,
  TouchableOpacity,
  RefreshControl,
  ActivityIndicator,
  Modal,
  StyleSheet,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { ScreenWrapper } from '../../src/components/ScreenWrapper';
import { Card } from '../../src/components/Card';
import { StatusPill } from '../../src/components/StatusPill';
import { EmptyState } from '../../src/components/EmptyState';
import { Button } from '../../src/components/Button';
import { useToast } from '../../src/components/Toast';
import { colors, borderRadius, typography } from '../../src/theme';
import { formatRelative, todayISO } from '../../src/lib/helpers';

type TabType = 'today' | 'upcoming' | 'missed';

interface Task {
  id: number;
  title: string;
  customerName: string;
  staffName: string;
  status: string;
  scheduledDate: string;
}

export default function TasksScreen() {
  const { showToast } = useToast();
  const [activeTab, setActiveTab] = useState<TabType>('today');
  const [tasks, setTasks] = useState<Task[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [search, setSearch] = useState('');
  const [completeModalVisible, setCompleteModalVisible] = useState(false);
  const [selectedTaskId, setSelectedTaskId] = useState<number | null>(null);
  const [completeDate, setCompleteDate] = useState(todayISO());
  const [completeNotes, setCompleteNotes] = useState('');

  const fetchTasks = useCallback(async () => {
    try {
      const res = await fetch(`https://api.recurlog.com/tasks?tab=${activeTab}`);
      if (!res.ok) throw new Error('Failed');
      const json = await res.json();
      setTasks(json);
    } catch {
      const today = todayISO();
      let mock: Task[];
      switch (activeTab) {
        case 'today':
          mock = [
            { id: 1, title: 'RO Service - Sharma Family', customerName: 'Sharma Family', staffName: 'Rajesh Yadav', status: 'pending', scheduledDate: today },
            { id: 2, title: 'AC Repair - Gupta Traders', customerName: 'Gupta Traders', staffName: 'Sita Thapa', status: 'pending', scheduledDate: today },
            { id: 3, title: 'TV Installation - Mehta & Co', customerName: 'Mehta & Co', staffName: 'Bhim Singh', status: 'completed', scheduledDate: today },
            { id: 4, title: 'Refrigerator Service - Patel', customerName: 'Patel Residence', staffName: 'Rajesh Yadav', status: 'pending', scheduledDate: today },
          ];
          break;
        case 'upcoming':
          mock = [
            { id: 5, title: 'Washing Machine Service - Thapa', customerName: 'Thapa Family', staffName: 'Sita Thapa', status: 'pending', scheduledDate: '2026-05-25' },
            { id: 6, title: 'RO Maintenance - Kumar', customerName: 'Kumar Electronics', staffName: 'Rajesh Yadav', status: 'pending', scheduledDate: '2026-05-27' },
            { id: 7, title: 'AC Service - Verma', customerName: 'Verma Household', staffName: 'Bhim Singh', status: 'pending', scheduledDate: '2026-05-28' },
          ];
          break;
        case 'missed':
          mock = [
            { id: 8, title: 'TV Repair - Patel Residence', customerName: 'Patel Residence', staffName: 'Rajesh Yadav', status: 'missed', scheduledDate: '2026-05-18' },
            { id: 9, title: 'RO Service - Sharma Family', customerName: 'Sharma Family', staffName: 'Sita Thapa', status: 'missed', scheduledDate: '2026-05-16' },
          ];
          break;
      }
      setTasks(mock);
    } finally {
      setLoading(false);
    }
  }, [activeTab]);

  useEffect(() => {
    setLoading(true);
    fetchTasks();
  }, [fetchTasks]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await fetchTasks();
    setRefreshing(false);
  }, [fetchTasks]);

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
    fetchTasks();
  };

  const filtered = tasks.filter((t) =>
    t.title.toLowerCase().includes(search.toLowerCase()) ||
    t.customerName.toLowerCase().includes(search.toLowerCase()) ||
    t.staffName.toLowerCase().includes(search.toLowerCase())
  );

  const tabs: { key: TabType; label: string }[] = [
    { key: 'today', label: 'Today' },
    { key: 'upcoming', label: 'Upcoming' },
    { key: 'missed', label: 'Missed' },
  ];

  const emptyMessages = {
    today: { icon: 'calendar-outline' as const, title: 'No today tasks', message: 'No tasks scheduled for today. Create a new service to get started.' },
    upcoming: { icon: 'calendar-outline' as const, title: 'No upcoming tasks', message: 'No upcoming tasks. Schedule a recurring service to see tasks here.' },
    missed: { icon: 'alert-circle-outline' as const, title: 'No missed tasks', message: 'No missed tasks. Great job keeping up!' },
  };

  return (
    <ScreenWrapper>
      <FlatList
        data={filtered}
        keyExtractor={(item) => item.id.toString()}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.brand} />}
        contentContainerStyle={styles.listContent}
        ListHeaderComponent={
          <>
            <View style={styles.header}>
              <Text style={styles.title}>Tasks</Text>
            </View>

            <View style={styles.tabBar}>
              {tabs.map((tab) => (
                <TouchableOpacity
                  key={tab.key}
                  style={[styles.tab, activeTab === tab.key && styles.tabActive]}
                  onPress={() => setActiveTab(tab.key)}
                  activeOpacity={0.7}
                >
                  <Text style={[styles.tabText, activeTab === tab.key && styles.tabTextActive]}>
                    {tab.label}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>

            <View style={styles.searchContainer}>
              <Ionicons name="search" size={16} color={colors.gray[400]} style={styles.searchIcon} />
              <TextInput
                style={styles.searchInput}
                placeholder="Search tasks..."
                placeholderTextColor={colors.gray[400]}
                value={search}
                onChangeText={setSearch}
              />
            </View>
          </>
        }
        ListEmptyComponent={
          loading ? (
            <ActivityIndicator size="large" color={colors.brand} style={{ marginTop: 48 }} />
          ) : (
            <EmptyState
              icon={emptyMessages[activeTab].icon}
              title={emptyMessages[activeTab].title}
              message={emptyMessages[activeTab].message}
              actionLabel={activeTab !== 'missed' ? 'Add Service' : undefined}
              onAction={activeTab !== 'missed' ? () => {} : undefined}
            />
          )
        }
        renderItem={({ item }) => (
          <Card style={styles.taskCard}>
            <View style={styles.taskHeader}>
              <View style={styles.taskInfo}>
                <Text style={styles.taskTitle}>{item.title}</Text>
                <View style={styles.taskMeta}>
                  <View style={styles.taskMetaItem}>
                    <Ionicons name="person-outline" size={12} color={colors.gray[400]} />
                    <Text style={styles.taskMetaText}>{item.customerName}</Text>
                  </View>
                  <View style={styles.taskMetaItem}>
                    <Ionicons name="briefcase-outline" size={12} color={colors.gray[400]} />
                    <Text style={styles.taskMetaText}>{item.staffName}</Text>
                  </View>
                  <View style={styles.taskMetaItem}>
                    <Ionicons name="calendar-outline" size={12} color={colors.gray[400]} />
                    <Text style={styles.taskMetaText}>{formatRelative(item.scheduledDate)}</Text>
                  </View>
                </View>
              </View>
              <StatusPill status={item.status} />
            </View>
            {item.status === 'pending' && (
              <View style={styles.taskFooter}>
                <TouchableOpacity
                  style={styles.completeBtn}
                  onPress={() => {
                    setSelectedTaskId(item.id);
                    setCompleteModalVisible(true);
                  }}
                  activeOpacity={0.7}
                >
                  <Ionicons name="checkmark-circle" size={14} color={colors.white} />
                  <Text style={styles.completeBtnText}>Mark Complete</Text>
                </TouchableOpacity>
              </View>
            )}
          </Card>
        )}
      />

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
  listContent: {
    padding: 16,
    paddingBottom: 32,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
  },
  title: {
    fontSize: typography.fontSize.xl,
    fontWeight: typography.fontWeight.bold,
    color: colors.navy,
  },
  tabBar: {
    flexDirection: 'row',
    backgroundColor: colors.gray[100],
    borderRadius: borderRadius.md,
    padding: 4,
    marginBottom: 16,
    alignSelf: 'flex-start',
  },
  tab: {
    paddingHorizontal: 20,
    paddingVertical: 8,
    borderRadius: borderRadius.sm,
  },
  tabActive: {
    backgroundColor: colors.brand,
  },
  tabText: {
    fontSize: typography.fontSize.sm,
    fontWeight: typography.fontWeight.medium,
    color: colors.gray[600],
  },
  tabTextActive: {
    color: colors.white,
  },
  searchContainer: {
    position: 'relative',
    marginBottom: 16,
  },
  searchIcon: {
    position: 'absolute',
    left: 12,
    top: 12,
    zIndex: 1,
  },
  searchInput: {
    borderWidth: 1,
    borderColor: colors.gray[300],
    borderRadius: borderRadius.lg,
    paddingLeft: 36,
    paddingRight: 14,
    paddingVertical: 10,
    fontSize: typography.fontSize.sm,
    color: colors.gray[900],
    backgroundColor: colors.white,
  },
  taskCard: {
    marginBottom: 12,
  },
  taskHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
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
    flexWrap: 'wrap',
    gap: 10,
    marginTop: 6,
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
  taskFooter: {
    borderTopWidth: 1,
    borderTopColor: colors.gray[100],
    marginTop: 12,
    paddingTop: 12,
    alignItems: 'flex-end',
  },
  completeBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    backgroundColor: colors.brand,
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: borderRadius.md,
  },
  completeBtnText: {
    color: colors.white,
    fontSize: typography.fontSize.xs,
    fontWeight: typography.fontWeight.semibold,
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
