import { useState, useEffect } from 'react';
import { View, Text, TextInput, TouchableOpacity, ScrollView, StyleSheet, ActivityIndicator, Alert, KeyboardAvoidingView, Platform } from 'react-native';
import { useNavigation, useRoute } from '@react-navigation/native';
import { ArrowLeft } from 'lucide-react-native';
import { tasksApi, staffApi } from '../../api/client';
import { useAuthStore } from '../../store/authStore';
import { COLORS, RADIUS, SPACING, FONT_SIZES, SHADOWS } from '../../constants/theme';
import { todayISO } from '../../utils/date';
import type { Task } from '../../types';

export function TaskEditScreen() {
  const navigation = useNavigation<any>();
  const route = useRoute<any>();
  const taskId = route.params?.id as number;
  const user = useAuthStore((s) => s.user);
  const isStaff = user?.role === 'staff';

  const [title, setTitle] = useState('');
  const [status, setStatus] = useState<'pending' | 'completed' | 'missed'>('pending');
  const [assignedTo, setAssignedTo] = useState<number | null>(null);
  const [staff, setStaff] = useState<any[]>([]);
  const [showStaffDropdown, setShowStaffDropdown] = useState(false);
  const [scheduledDate, setScheduledDate] = useState(todayISO());
  const [notes, setNotes] = useState('');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (!taskId) { navigation.goBack(); return; }
    Promise.all([
      tasksApi.get(taskId),
      staffApi.list(),
    ]).then(([taskRes, staffRes]) => {
      const t: Task = taskRes.data?.data || taskRes.data;
      setTitle(t.title);
      setStatus(t.status);
      setAssignedTo(t.assignedTo || null);
      setScheduledDate(t.scheduledDate || todayISO());
      setNotes(t.notes || '');
      setStaff(Array.isArray(staffRes.data?.data) ? staffRes.data.data : []);
    }).catch(() => {
      Alert.alert('Error', 'Failed to load task');
      navigation.goBack();
    }).finally(() => setLoading(false));
  }, [taskId]);

  const handleSave = async () => {
    if (!title.trim()) { Alert.alert('Validation', 'Title is required.'); return; }
    setSaving(true);
    try {
      await tasksApi.update(taskId, {
        title: title.trim(),
        status,
        assignedTo,
        scheduledDate,
        notes: notes.trim(),
      });
      navigation.goBack();
    } catch (err: any) {
      Alert.alert('Error', err?.response?.data?.error || 'Failed to update task');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <View style={styles.container}>
        <View style={styles.header}>
          <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
            <ArrowLeft size={20} color={COLORS.neutral600} />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Edit Task</Text>
        </View>
        <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
          <ActivityIndicator size="large" color={COLORS.primary} />
        </View>
      </View>
    );
  }

  const statusOptions: { label: string; value: 'pending' | 'completed' | 'missed' }[] = [
    { label: 'Pending', value: 'pending' },
    { label: 'Completed', value: 'completed' },
    { label: 'Missed', value: 'missed' },
  ];

  return (
    <KeyboardAvoidingView style={styles.container} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
          <ArrowLeft size={20} color={COLORS.neutral600} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Edit Task</Text>
      </View>

      <ScrollView contentContainerStyle={styles.scroll}>
        <View style={styles.fieldGroup}>
          <Text style={styles.label}>Title</Text>
          <TextInput style={styles.input} value={title} onChangeText={setTitle} placeholder="Task title" placeholderTextColor={COLORS.neutral400} />
        </View>

        <View style={styles.fieldGroup}>
          <Text style={styles.label}>Status</Text>
          <View style={styles.toggleRow}>
            {statusOptions.map((opt) => (
              <TouchableOpacity
                key={opt.value}
                style={[styles.toggleBtn, status === opt.value && styles.toggleActive]}
                onPress={() => setStatus(opt.value)}
              >
                <Text style={[styles.toggleText, status === opt.value && styles.toggleTextActive]}>{opt.label}</Text>
              </TouchableOpacity>
            ))}
          </View>
        </View>

        <View style={styles.fieldGroup}>
          <Text style={styles.label}>Scheduled Date</Text>
          <TextInput style={styles.input} value={scheduledDate} onChangeText={setScheduledDate} placeholder="YYYY-MM-DD" placeholderTextColor={COLORS.neutral400} />
        </View>

        <View style={styles.fieldGroup}>
          <Text style={styles.label}>Assign To</Text>
          <TouchableOpacity style={styles.selectBtn} onPress={() => setShowStaffDropdown(!showStaffDropdown)}>
            <Text style={assignedTo ? styles.selectText : styles.selectPlaceholder}>
              {assignedTo ? staff.find((s: any) => s.id === assignedTo)?.name || 'Selected' : 'Unassigned'}
            </Text>
          </TouchableOpacity>
          {showStaffDropdown && (
            <View style={styles.dropdown}>
              <TouchableOpacity style={styles.dropdownItem} onPress={() => { setAssignedTo(null); setShowStaffDropdown(false); }}>
                <Text style={styles.dropdownItemText}>Unassigned</Text>
              </TouchableOpacity>
              {staff.map((s: any) => (
                <TouchableOpacity
                  key={s.id}
                  style={[styles.dropdownItem, assignedTo === s.id && styles.dropdownItemActive]}
                  onPress={() => { setAssignedTo(s.id); setShowStaffDropdown(false); }}
                >
                  <Text style={styles.dropdownItemText}>{s.name}</Text>
                </TouchableOpacity>
              ))}
            </View>
          )}
        </View>

        <View style={styles.fieldGroup}>
          <Text style={styles.label}>Notes</Text>
          <TextInput style={[styles.input, styles.textArea]} value={notes} onChangeText={setNotes} multiline placeholder="Additional notes..." placeholderTextColor={COLORS.neutral400} />
        </View>

        <TouchableOpacity style={styles.saveBtn} onPress={handleSave} disabled={saving}>
          {saving ? <ActivityIndicator color={COLORS.white} /> : <Text style={styles.saveBtnText}>Update Task</Text>}
        </TouchableOpacity>
      </ScrollView>
    </KeyboardAvoidingView>
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
  scroll: { padding: SPACING[4], paddingBottom: 120 },
  fieldGroup: { marginBottom: SPACING[5] },
  label: { fontSize: FONT_SIZES.sm, fontWeight: '600', color: COLORS.neutral700, marginBottom: 6 },
  input: {
    height: 44, borderWidth: 1, borderColor: COLORS.neutral200, borderRadius: RADIUS.lg,
    paddingHorizontal: SPACING[4], fontSize: FONT_SIZES.sm, color: COLORS.neutral900, backgroundColor: COLORS.white,
  },
  textArea: { height: 80, paddingVertical: SPACING[3], textAlignVertical: 'top' },
  toggleRow: { flexDirection: 'row', gap: 8 },
  toggleBtn: {
    flex: 1, paddingVertical: 10, borderRadius: RADIUS.lg, alignItems: 'center',
    backgroundColor: COLORS.neutral100, borderWidth: 1, borderColor: COLORS.neutral200,
  },
  toggleActive: { backgroundColor: COLORS.primary, borderColor: COLORS.primary },
  toggleText: { fontSize: FONT_SIZES.sm, fontWeight: '500', color: COLORS.neutral600 },
  toggleTextActive: { color: COLORS.white },
  selectBtn: {
    height: 44, borderWidth: 1, borderColor: COLORS.neutral200, borderRadius: RADIUS.lg,
    paddingHorizontal: SPACING[4], justifyContent: 'center', backgroundColor: COLORS.white,
  },
  selectText: { fontSize: FONT_SIZES.sm, color: COLORS.neutral900 },
  selectPlaceholder: { fontSize: FONT_SIZES.sm, color: COLORS.neutral400 },
  dropdown: {
    marginTop: 4, borderWidth: 1, borderColor: COLORS.neutral200, borderRadius: RADIUS.lg,
    backgroundColor: COLORS.white, maxHeight: 200, overflow: 'hidden',
  },
  dropdownItem: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: SPACING[4], paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: COLORS.neutral50,
  },
  dropdownItemActive: { backgroundColor: COLORS.primary + '10' },
  dropdownItemText: { fontSize: FONT_SIZES.sm, color: COLORS.neutral800 },
  saveBtn: {
    paddingVertical: 14, borderRadius: RADIUS.lg, backgroundColor: COLORS.primary,
    alignItems: 'center', marginTop: 8,
  },
  saveBtnText: { color: COLORS.white, fontSize: FONT_SIZES.sm, fontWeight: '600' },
});
