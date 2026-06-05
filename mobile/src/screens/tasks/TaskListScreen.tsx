import { useEffect, useState, useCallback } from 'react';
import { View, Text, FlatList, TouchableOpacity, RefreshControl, StyleSheet, Modal, TextInput, Alert, ActivityIndicator } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { ArrowLeft, Calendar, User, CheckCircle, X, Filter } from 'lucide-react-native';
import { tasksApi } from '../../api/client';
import { useAuthStore } from '../../store/authStore';
import { SearchBar } from '../../components/SearchBar';
import { StatusBadge } from '../../components/StatusBadge';
import { EmptyState } from '../../components/EmptyState';
import { StaffTaskCompleteModal } from '../../components/StaffTaskCompleteModal';
import { COLORS, RADIUS, SPACING, FONT_SIZES, SHADOWS } from '../../constants/theme';
import { formatRelative, todayISO } from '../../utils/date';
import type { Task } from '../../types';

type Tab = 'today' | 'upcoming' | 'missed';

export function TaskListScreen() {
  const navigation = useNavigation<any>();
  const user = useAuthStore((s) => s.user);
  const isStaff = user?.role === 'staff';
  const [tasks, setTasks] = useState<Task[]>([]);
  const [filtered, setFiltered] = useState<Task[]>([]);
  const [search, setSearch] = useState('');
  const [activeTab, setActiveTab] = useState<Tab>('today');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [modalVisible, setModalVisible] = useState(false);
  const [selectedTask, setSelectedTask] = useState<Task | null>(null);
  const [compDate, setCompDate] = useState(todayISO());
  const [compNotes, setCompNotes] = useState('');
  const [completing, setCompleting] = useState(false);

  const fetchTasks = useCallback(async () => {
    try {
      const today = todayISO();
      let params: any = {};
      if (activeTab === 'today') params.scheduled_date = today;
      else if (activeTab === 'upcoming') { params.start_date = today; params.status = 'pending'; }
      else if (activeTab === 'missed') params.status = 'missed';
      if (isStaff && user?.staffId) params.assigned_to = user.staffId;

      const { data } = await tasksApi.list(params);
      const list = Array.isArray(data.data) ? data.data : [];
      setTasks(list);
    } catch {} finally { setLoading(false); }
  }, [activeTab]);

  useEffect(() => { fetchTasks(); }, [activeTab]);

  useEffect(() => {
    const q = search.toLowerCase().trim();
    if (!q) { setFiltered(tasks); return; }
    setFiltered(tasks.filter((t) => t.title.toLowerCase().includes(q) || (t.customerName || '').toLowerCase().includes(q)));
  }, [search, tasks]);

  const onRefresh = async () => { setRefreshing(true); await fetchTasks(); setRefreshing(false); };

  const openCompleteModal = (task: Task) => {
    setSelectedTask(task);
    setCompDate(todayISO());
    setCompNotes('');
    setModalVisible(true);
  };

  const handleComplete = async () => {
    if (!selectedTask || !compDate) return;
    setCompleting(true);
    try {
      await tasksApi.complete(selectedTask.id, compDate, compNotes);
      setModalVisible(false);
      fetchTasks();
    } catch (err: any) {
      Alert.alert('Error', err?.response?.data?.error || 'Failed to complete task');
    } finally { setCompleting(false); }
  };

  const TabButton = ({ tab, label }: { tab: Tab; label: string }) => (
    <TouchableOpacity
      style={[styles.tabBtn, activeTab === tab && styles.tabActive]}
      onPress={() => setActiveTab(tab)}
    >
      <Text style={[styles.tabText, activeTab === tab && styles.tabTextActive]}>{label}</Text>
    </TouchableOpacity>
  );

  const renderTask = ({ item }: { item: Task }) => (
    <TouchableOpacity style={[styles.taskCard, SHADOWS.sm]} onPress={() => navigation.navigate('TaskDetail', { id: item.id })}>
      <View style={styles.taskContent}>
        <View style={{ flex: 1 }}>
          <Text style={styles.taskTitle}>{item.title}</Text>
          <View style={styles.taskMeta}>
            <View style={styles.metaItem}>
              <User size={12} color={COLORS.neutral500} />
              <Text style={styles.metaText}>{item.customerName || 'Unknown'}</Text>
            </View>
            <View style={styles.metaItem}>
              <Calendar size={12} color={COLORS.neutral500} />
              <Text style={styles.metaText}>{formatRelative(item.scheduledDate)}</Text>
            </View>
          </View>
        </View>
        <StatusBadge status={item.status} />
      </View>
      {item.status === 'pending' && (
        <TouchableOpacity style={styles.completeBtn} onPress={() => openCompleteModal(item)}>
          <CheckCircle size={14} color={COLORS.white} />
          <Text style={styles.completeBtnText}>Mark Complete</Text>
        </TouchableOpacity>
      )}
    </TouchableOpacity>
  );

  const emptyMessages = {
    today: { title: 'No tasks for today', sub: 'Create a new service to get started.' },
    upcoming: { title: 'No upcoming tasks', sub: 'Schedule a recurring service to see tasks here.' },
    missed: { title: 'No missed tasks', sub: 'Great job keeping up!' },
  };

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
          <ArrowLeft size={20} color={COLORS.neutral600} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Tasks</Text>
      </View>

      <View style={styles.tabRow}>
        <TabButton tab="today" label="Today" />
        <TabButton tab="upcoming" label="Upcoming" />
        <TabButton tab="missed" label="Missed" />
      </View>

      <SearchBar value={search} onChangeText={setSearch} placeholder="Search tasks..." />

      <FlatList
        data={filtered}
        keyExtractor={(item) => String(item.id)}
        renderItem={renderTask}
        contentContainerStyle={styles.list}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={COLORS.primary} />}
        ListEmptyComponent={
          !loading ? <EmptyState title={emptyMessages[activeTab].title} subtitle={emptyMessages[activeTab].sub} /> : null
        }
      />

      {isStaff && selectedTask ? (
        <StaffTaskCompleteModal
          visible={modalVisible}
          task={selectedTask}
          onClose={() => setModalVisible(false)}
          onComplete={() => { setModalVisible(false); fetchTasks(); }}
        />
      ) : (
        <Modal visible={modalVisible} transparent animationType="slide" onRequestClose={() => setModalVisible(false)}>
          <TouchableOpacity style={styles.modalOverlay} activeOpacity={1} onPress={() => setModalVisible(false)}>
            <TouchableOpacity style={styles.modalContent} activeOpacity={1}>
              <View style={styles.modalHeader}>
                <Text style={styles.modalTitle}>Mark Task Complete</Text>
                <TouchableOpacity onPress={() => setModalVisible(false)}>
                  <X size={20} color={COLORS.neutral500} />
                </TouchableOpacity>
              </View>
              <Text style={styles.modalCustomer}>{selectedTask?.customerName} — {selectedTask?.title}</Text>
              <View style={styles.fieldGroup}>
                <Text style={styles.label}>Completion Date</Text>
                <TextInput style={styles.input} value={compDate} onChangeText={setCompDate} placeholder="YYYY-MM-DD" />
              </View>
              <View style={styles.fieldGroup}>
                <Text style={styles.label}>Notes</Text>
                <TextInput style={[styles.input, styles.textArea]} value={compNotes} onChangeText={setCompNotes} multiline placeholder="Notes..." maxLength={1000} />
              </View>
              <View style={styles.modalActions}>
                <TouchableOpacity style={styles.cancelBtn} onPress={() => setModalVisible(false)}>
                  <Text style={styles.cancelBtnText}>Cancel</Text>
                </TouchableOpacity>
                <TouchableOpacity style={styles.confirmBtn} onPress={handleComplete} disabled={completing}>
                  {completing ? <ActivityIndicator color={COLORS.white} size="small" /> : <Text style={styles.confirmBtnText}>Confirm</Text>}
                </TouchableOpacity>
              </View>
            </TouchableOpacity>
          </TouchableOpacity>
        </Modal>
      )}
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
  tabRow: { flexDirection: 'row', padding: SPACING[3], gap: 8 },
  tabBtn: { paddingHorizontal: 20, paddingVertical: 8, borderRadius: RADIUS.md, backgroundColor: COLORS.neutral100 },
  tabActive: { backgroundColor: COLORS.primary },
  tabText: { fontSize: FONT_SIZES.sm, fontWeight: '500', color: COLORS.neutral600 },
  tabTextActive: { color: COLORS.white },
  list: { padding: SPACING[4], paddingBottom: 80 },
  taskCard: {
    backgroundColor: COLORS.white, borderRadius: RADIUS.lg, padding: SPACING[4],
    borderWidth: 1, borderColor: COLORS.neutral100, marginBottom: SPACING[3],
  },
  taskContent: { flexDirection: 'row', alignItems: 'flex-start', gap: 10 },
  taskTitle: { fontSize: FONT_SIZES.sm, fontWeight: '600', color: COLORS.neutral900, marginBottom: 4 },
  taskMeta: { flexDirection: 'row', gap: 12 },
  metaItem: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  metaText: { fontSize: FONT_SIZES.xs, color: COLORS.neutral500 },
  completeBtn: {
    flexDirection: 'row', alignItems: 'center', gap: 6, justifyContent: 'center',
    marginTop: 10, paddingVertical: 8, backgroundColor: COLORS.primary,
    borderRadius: RADIUS.lg,
  },
  completeBtnText: { color: COLORS.white, fontSize: FONT_SIZES.xs, fontWeight: '600' },
  modalOverlay: {
    flex: 1, backgroundColor: COLORS.backdrop, justifyContent: 'flex-end',
  },
  modalContent: {
    backgroundColor: COLORS.white, borderTopLeftRadius: RADIUS.xl, borderTopRightRadius: RADIUS.xl,
    padding: SPACING[6], maxHeight: '85%',
  },
  modalHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 },
  modalTitle: { fontSize: FONT_SIZES.lg, fontWeight: '700', color: COLORS.neutral900 },
  modalCustomer: { fontSize: FONT_SIZES.sm, color: COLORS.neutral500, marginBottom: SPACING[5] },
  fieldGroup: { marginBottom: SPACING[4] },
  label: { fontSize: FONT_SIZES.sm, fontWeight: '600', color: COLORS.neutral700, marginBottom: 6 },
  input: {
    height: 44, borderWidth: 1, borderColor: COLORS.neutral200, borderRadius: RADIUS.lg,
    paddingHorizontal: SPACING[4], fontSize: FONT_SIZES.sm, color: COLORS.neutral900, backgroundColor: COLORS.white,
  },
  textArea: { height: 80, paddingVertical: SPACING[3], textAlignVertical: 'top' },
  modalActions: { flexDirection: 'row', gap: 12, marginTop: 8 },
  cancelBtn: { flex: 1, paddingVertical: 14, borderRadius: RADIUS.lg, borderWidth: 1, borderColor: COLORS.neutral300, alignItems: 'center' },
  cancelBtnText: { fontSize: FONT_SIZES.sm, fontWeight: '600', color: COLORS.neutral700 },
  confirmBtn: { flex: 1, paddingVertical: 14, borderRadius: RADIUS.lg, backgroundColor: COLORS.primary, alignItems: 'center' },
  confirmBtnText: { fontSize: FONT_SIZES.sm, fontWeight: '600', color: COLORS.white },
});
