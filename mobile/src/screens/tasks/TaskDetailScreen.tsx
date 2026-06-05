import { useEffect, useState } from 'react';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import {
  View, Text, ScrollView, StyleSheet, TouchableOpacity, ActivityIndicator, Image, Alert,
} from 'react-native';
import { useNavigation, useRoute } from '@react-navigation/native';
import { ArrowLeft, Calendar, User, Phone, ClipboardCheck, CheckCircle, CheckSquare, Pencil } from 'lucide-react-native';
import { tasksApi } from '../../api/client';
import { StatusBadge } from '../../components/StatusBadge';
import { StaffTaskCompleteModal } from '../../components/StaffTaskCompleteModal';
import { useAuthStore } from '../../store/authStore';
import { COLORS, RADIUS, SPACING, FONT_SIZES, SHADOWS } from '../../constants/theme';
import type { Task } from '../../types';

export function TaskDetailScreen() {
  const navigation = useNavigation<any>();
  const insets = useSafeAreaInsets();
  const route = useRoute<any>();
  const taskId = route.params?.id;
  const user = useAuthStore((s) => s.user);
  const isStaff = user?.role === 'staff';
  const [task, setTask] = useState<Task | null>(null);
  const [loading, setLoading] = useState(true);
  const [completeModalVisible, setCompleteModalVisible] = useState(false);

  const fetchTask = async () => {
    if (!taskId) return;
    try {
      const { data } = await tasksApi.get(taskId);
      setTask(data?.data || null);
    } catch { Alert.alert('Error', 'Failed to load task'); } finally { setLoading(false); }
  };

  useEffect(() => {
    if (!taskId) { navigation.goBack(); return; }
    fetchTask();
  }, [taskId]);

  if (loading) {
    return (
      <View style={styles.container}>
        <View style={[styles.header, { paddingTop: insets.top, minHeight: 56 + insets.top }]}>
          <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
            <ArrowLeft size={20} color={COLORS.neutral600} />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Task Detail</Text>
        </View>
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={COLORS.primary} />
        </View>
      </View>
    );
  }

  if (!task) {
    return (
      <View style={styles.container}>
        <View style={[styles.header, { paddingTop: insets.top, minHeight: 56 + insets.top }]}>
          <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
            <ArrowLeft size={20} color={COLORS.neutral600} />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Task Detail</Text>
        </View>
        <Text style={{ textAlign: 'center', marginTop: 40, color: COLORS.neutral400 }}>Task not found</Text>
      </View>
    );
  }

  const InfoIcon = ({ icon: Icon, label, value }: { icon: any; label: string; value: string | null }) => (
    <View style={styles.infoRow}>
      <Icon size={16} color={COLORS.neutral400} />
      <Text style={styles.infoLabel}>{label}</Text>
      <Text style={styles.infoValue}>{value || '—'}</Text>
    </View>
  );

  return (
    <View style={styles.container}>
      <View style={[styles.header, { paddingTop: insets.top, minHeight: 56 + insets.top }]}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
          <ArrowLeft size={20} color={COLORS.neutral600} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Task Detail</Text>
        {!isStaff && (
          <TouchableOpacity onPress={() => navigation.navigate('TaskEdit', { id: task.id })} style={styles.editBtn}>
            <Pencil size={18} color={COLORS.neutral600} />
          </TouchableOpacity>
        )}
      </View>

      <ScrollView contentContainerStyle={styles.scrollContent}>
        {/* Header Card */}
        <View style={[styles.card, SHADOWS.sm]}>
          <View style={styles.headerCard}>
            <View style={styles.headerCardLeft}>
              <View style={styles.headerIcon}>
                <ClipboardCheck size={20} color={COLORS.primary} />
              </View>
              <View style={{ flex: 1 }}>
                <Text style={styles.taskTitle}>{task.title}</Text>
                <Text style={styles.taskId}>Task #{task.id}</Text>
              </View>
            </View>
            <StatusBadge status={task.status} />
          </View>
        </View>

        {/* Customer Card */}
        <View style={[styles.card, SHADOWS.sm]}>
          <Text style={styles.sectionLabel}>Customer</Text>
          <View style={styles.customerRow}>
            <View style={styles.customerAvatar}>
              <Text style={styles.customerAvatarText}>
                {(task.customerName || 'U').split(' ').map((s) => s[0]).join('').toUpperCase().slice(0, 2)}
              </Text>
            </View>
            <View>
              <Text style={styles.customerName}>{task.customerName || 'Unknown'}</Text>
            </View>
          </View>
        </View>

        {/* Schedule Card */}
        <View style={[styles.card, SHADOWS.sm]}>
          <Text style={styles.sectionLabel}>Schedule</Text>
          <InfoIcon icon={Calendar} label="Scheduled" value={task.scheduledDate} />
          {task.completedDate && <InfoIcon icon={CheckCircle} label="Completed" value={task.completedDate} />}
        </View>

        {/* Assignment Card */}
        <View style={[styles.card, SHADOWS.sm]}>
          <Text style={styles.sectionLabel}>Assignment</Text>
          <InfoIcon icon={User} label="Assigned To" value={task.assignedStaffName || (task.assignedTo ? `Staff #${task.assignedTo}` : 'Unassigned')} />
          {task.completedBy && <InfoIcon icon={User} label="Completed By" value={task.completedBy} />}
        </View>

        {/* Details Card */}
        {(task.description || task.serviceProblem || task.notes) && (
          <View style={[styles.card, SHADOWS.sm]}>
            <Text style={styles.sectionLabel}>Details</Text>
            {task.description && (
              <View style={styles.detailBlock}>
                <Text style={styles.detailLabel}>Description</Text>
                <Text style={styles.detailText}>{task.description}</Text>
              </View>
            )}
            {task.serviceProblem && (
              <View style={styles.detailBlock}>
                <Text style={styles.detailLabel}>Problem</Text>
                <Text style={styles.detailText}>{task.serviceProblem}</Text>
              </View>
            )}
            {task.notes && (
              <View style={styles.detailBlock}>
                <Text style={styles.detailLabel}>Notes</Text>
                <Text style={styles.detailText}>{task.notes}</Text>
              </View>
            )}
          </View>
        )}

        {/* Completion Details */}
        {task.status === 'completed' && (task.receivedName || task.receivedContact || task.signature) && (
          <View style={[styles.card, SHADOWS.sm]}>
            <View style={styles.sectionHeaderRow}>
              <ClipboardCheck size={16} color={COLORS.primary} />
              <Text style={styles.sectionLabel}>Completion Details</Text>
            </View>
            {task.receivedName && (
              <InfoIcon icon={User} label="Received By" value={task.receivedName} />
            )}
            {task.receivedContact && (
              <InfoIcon icon={Phone} label="Contact" value={task.receivedContact} />
            )}
            {task.signature && (
              <View style={styles.signatureBlock}>
                <Text style={styles.detailLabel}>Signature</Text>
                <Image
                  source={{ uri: task.signature }}
                  style={styles.signatureImage}
                  resizeMode="contain"
                />
              </View>
            )}
          </View>
        )}

        <View style={{ height: 80 }} />
      </ScrollView>

      {/* Complete button for staff */}
      {isStaff && task.status === 'pending' && (
        <View style={styles.bottomBar}>
          <TouchableOpacity
            style={styles.completeBtn}
            onPress={() => setCompleteModalVisible(true)}
          >
            <CheckSquare size={18} color={COLORS.white} />
            <Text style={styles.completeBtnText}>Mark Complete</Text>
          </TouchableOpacity>
        </View>
      )}

      <StaffTaskCompleteModal
        visible={completeModalVisible}
        task={task}
        onClose={() => setCompleteModalVisible(false)}
        onComplete={() => {
          setCompleteModalVisible(false);
          setLoading(true);
          fetchTask();
        }}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: COLORS.neutral50 },
  loadingContainer: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  header: {
    flexDirection: 'row', alignItems: 'center', paddingHorizontal: SPACING[4],
    backgroundColor: COLORS.white, borderBottomWidth: 1, borderBottomColor: COLORS.neutral200,
  },
  backBtn: { padding: 8, minWidth: 44, minHeight: 44, justifyContent: 'center' },
  editBtn: { padding: 8, minWidth: 44, minHeight: 44, justifyContent: 'center', alignItems: 'center' },
  headerTitle: { fontSize: FONT_SIZES.lg, fontWeight: '700', color: COLORS.navy, marginLeft: 4 },
  scrollContent: { padding: SPACING[4], paddingBottom: 40 },
  card: {
    backgroundColor: COLORS.white, borderRadius: RADIUS.lg, padding: SPACING[4],
    borderWidth: 1, borderColor: COLORS.neutral100, marginBottom: SPACING[4],
  },
  headerCard: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: 12 },
  headerCardLeft: { flexDirection: 'row', alignItems: 'center', gap: 12, flex: 1 },
  headerIcon: {
    width: 40, height: 40, borderRadius: RADIUS.full,
    backgroundColor: 'rgba(29,185,84,0.1)',
    alignItems: 'center', justifyContent: 'center',
  },
  taskTitle: { fontSize: FONT_SIZES.base, fontWeight: '700', color: COLORS.navy },
  taskId: { fontSize: FONT_SIZES.xs, color: COLORS.neutral400, marginTop: 2 },
  sectionLabel: { fontSize: 11, fontWeight: '700', color: COLORS.neutral400, textTransform: 'uppercase', letterSpacing: 0.5, marginBottom: SPACING[3] },
  sectionHeaderRow: { flexDirection: 'row', alignItems: 'center', gap: 8, marginBottom: SPACING[3] },
  customerRow: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  customerAvatar: {
    width: 40, height: 40, borderRadius: RADIUS.full,
    backgroundColor: COLORS.primary, alignItems: 'center', justifyContent: 'center',
  },
  customerAvatarText: { color: COLORS.white, fontSize: 13, fontWeight: '700' },
  customerName: { fontSize: FONT_SIZES.sm, fontWeight: '600', color: COLORS.navy },
  infoRow: { flexDirection: 'row', alignItems: 'center', gap: 8, marginBottom: SPACING[2] },
  infoLabel: { fontSize: FONT_SIZES.sm, color: COLORS.neutral500, width: 100 },
  infoValue: { fontSize: FONT_SIZES.sm, fontWeight: '500', color: COLORS.navy, flex: 1, textAlign: 'right' },
  detailBlock: { marginBottom: SPACING[3] },
  detailLabel: { fontSize: FONT_SIZES.xs, color: COLORS.neutral500, marginBottom: 4 },
  detailText: { fontSize: FONT_SIZES.sm, color: COLORS.neutral800, lineHeight: 20 },
  signatureBlock: { marginTop: SPACING[2] },
  signatureImage: { height: 64, borderWidth: 1, borderColor: COLORS.neutral200, borderRadius: RADIUS.md, backgroundColor: COLORS.neutral50, marginTop: 4 },
  bottomBar: {
    position: 'absolute', bottom: 0, left: 0, right: 0,
    paddingHorizontal: SPACING[4], paddingVertical: SPACING[3],
    backgroundColor: COLORS.white, borderTopWidth: 1, borderTopColor: COLORS.neutral200,
  },
  completeBtn: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8,
    backgroundColor: COLORS.primary, borderRadius: RADIUS.lg, height: 48,
  },
  completeBtnText: { color: COLORS.white, fontSize: FONT_SIZES.sm, fontWeight: '700' },
});
