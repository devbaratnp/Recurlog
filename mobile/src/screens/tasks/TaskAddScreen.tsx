import { useState, useEffect } from 'react';
import { View, Text, TextInput, TouchableOpacity, ScrollView, StyleSheet, ActivityIndicator, Alert, KeyboardAvoidingView, Platform } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import { ArrowLeft } from 'lucide-react-native';
import { tasksApi, customersApi, staffApi, servicesApi } from '../../api/client';
import { SearchableDropdown } from '../../components/SearchableDropdown';
import { useToastStore } from '../../store/toastStore';
import { COLORS, RADIUS, SPACING, FONT_SIZES, SHADOWS } from '../../constants/theme';
import { todayISO } from '../../utils/date';

export function TaskAddScreen() {
  const navigation = useNavigation<any>();
  const insets = useSafeAreaInsets();

  const [customerId, setCustomerId] = useState<number | null>(null);
  const [customers, setCustomers] = useState<any[]>([]);

  const [title, setTitle] = useState('');
  const [problem, setProblem] = useState('');

  const [taskType, setTaskType] = useState<'onetime' | 'recurring'>('onetime');
  const [recurrenceValue, setRecurrenceValue] = useState('1');
  const [recurrenceUnit, setRecurrenceUnit] = useState<'days' | 'weeks' | 'months' | 'years'>('days');
  const [repeatFrom, setRepeatFrom] = useState<'last-done' | 'fixed-schedule'>('last-done');

  const [assignedTo, setAssignedTo] = useState<number | null>(null);
  const [staff, setStaff] = useState<any[]>([]);

  const [scheduledDate, setScheduledDate] = useState(todayISO());
  const [notes, setNotes] = useState('');
  const [saving, setSaving] = useState(false);
  const showToast = useToastStore((s) => s.show);

  useEffect(() => {
    Promise.all([customersApi.list(), staffApi.list()]).then(([c, s]) => {
      setCustomers(Array.isArray(c.data?.data) ? c.data.data : []);
      setStaff(Array.isArray(s.data?.data) ? s.data.data : []);
    });
  }, []);

  const handleSave = async () => {
    if (!customerId) { Alert.alert('Validation', 'Please select a customer.'); return; }
    if (!title.trim()) { Alert.alert('Validation', 'Please enter a task title.'); return; }
    setSaving(true);
    try {
      const payload: any = {
        customerId,
        title: title.trim(),
        problem: problem.trim(),
        status: 'pending',
        scheduledDate,
        assignedTo,
        notes: notes.trim(),
      };
      if (taskType === 'recurring') {
        payload.isRecurring = true;
        payload.recurrence = { value: parseInt(recurrenceValue) || 1, unit: recurrenceUnit, repeatFrom };
      }
      await tasksApi.create(payload);
      showToast('Task created successfully', 'success');
      navigation.goBack();
    } catch (err: any) {
      Alert.alert('Error', err?.response?.data?.error || 'Failed to create task');
    } finally {
      setSaving(false);
    }
  };

  const recurrencePreview = taskType === 'recurring'
    ? `Every ${recurrenceValue} ${recurrenceUnit}${recurrenceValue !== '1' ? 's' : ''} (from ${repeatFrom === 'last-done' ? 'last completion' : 'fixed schedule'})`
    : '';

  return (
    <KeyboardAvoidingView style={styles.container} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <View style={[styles.header, { paddingTop: insets.top, minHeight: 56 + insets.top }]}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
          <ArrowLeft size={20} color={COLORS.neutral600} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>{taskType === 'onetime' ? 'One-Time Task' : 'Recurring Task'}</Text>
      </View>

      <ScrollView contentContainerStyle={styles.scroll}>
        <View style={styles.fieldGroup}>
          <Text style={styles.label}>Customer</Text>
          <SearchableDropdown
            items={customers}
            selectedId={customerId}
            onSelect={(item) => {
              if (item) setCustomerId(Number(item.id));
            }}
            placeholder="Search customers..."
            emptyText="No customers found"
          />
        </View>

        <View style={styles.fieldGroup}>
          <Text style={styles.label}>Task Title</Text>
          <TextInput style={styles.input} value={title} onChangeText={setTitle} placeholder="e.g. RO Service" placeholderTextColor={COLORS.neutral400} />
        </View>

        <View style={styles.fieldGroup}>
          <Text style={styles.label}>Problem Description</Text>
          <TextInput style={[styles.input, styles.textArea]} value={problem} onChangeText={setProblem} multiline placeholder="Describe the issue..." />
        </View>

        <View style={styles.fieldGroup}>
          <Text style={styles.label}>Task Type</Text>
          <View style={styles.toggleRow}>
            <TouchableOpacity
              style={[styles.toggleBtn, taskType === 'onetime' && styles.toggleActive]}
              onPress={() => setTaskType('onetime')}
            >
              <Text style={[styles.toggleText, taskType === 'onetime' && styles.toggleTextActive]}>One-Time</Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[styles.toggleBtn, taskType === 'recurring' && styles.toggleActive]}
              onPress={() => setTaskType('recurring')}
            >
              <Text style={[styles.toggleText, taskType === 'recurring' && styles.toggleTextActive]}>Recurring</Text>
            </TouchableOpacity>
          </View>
        </View>

        {taskType === 'recurring' && (
          <>
            <View style={styles.fieldGroup}>
              <Text style={styles.label}>Repeat Every</Text>
              <View style={styles.recurrenceRow}>
                <TextInput
                  style={[styles.input, { flex: 1 }]}
                  value={recurrenceValue}
                  onChangeText={setRecurrenceValue}
                  keyboardType="number-pad"
                  placeholder="1"
                />
                <TouchableOpacity
                  style={[styles.unitBtn, recurrenceUnit === 'days' && styles.unitBtnActive]}
                  onPress={() => setRecurrenceUnit('days')}
                >
                  <Text style={[styles.unitText, recurrenceUnit === 'days' && styles.unitTextActive]}>Days</Text>
                </TouchableOpacity>
                <TouchableOpacity
                  style={[styles.unitBtn, recurrenceUnit === 'weeks' && styles.unitBtnActive]}
                  onPress={() => setRecurrenceUnit('weeks')}
                >
                  <Text style={[styles.unitText, recurrenceUnit === 'weeks' && styles.unitTextActive]}>Weeks</Text>
                </TouchableOpacity>
                <TouchableOpacity
                  style={[styles.unitBtn, recurrenceUnit === 'months' && styles.unitBtnActive]}
                  onPress={() => setRecurrenceUnit('months')}
                >
                  <Text style={[styles.unitText, recurrenceUnit === 'months' && styles.unitTextActive]}>Months</Text>
                </TouchableOpacity>
                <TouchableOpacity
                  style={[styles.unitBtn, recurrenceUnit === 'years' && styles.unitBtnActive]}
                  onPress={() => setRecurrenceUnit('years')}
                >
                  <Text style={[styles.unitText, recurrenceUnit === 'years' && styles.unitTextActive]}>Years</Text>
                </TouchableOpacity>
              </View>
            </View>

            <View style={styles.fieldGroup}>
              <Text style={styles.label}>Repeat From</Text>
              <View style={styles.toggleRow}>
                <TouchableOpacity
                  style={[styles.toggleBtn, repeatFrom === 'last-done' && styles.toggleActive]}
                  onPress={() => setRepeatFrom('last-done')}
                >
                  <Text style={[styles.toggleText, repeatFrom === 'last-done' && styles.toggleTextActive]}>Last Done</Text>
                </TouchableOpacity>
                <TouchableOpacity
                  style={[styles.toggleBtn, repeatFrom === 'fixed-schedule' && styles.toggleActive]}
                  onPress={() => setRepeatFrom('fixed-schedule')}
                >
                  <Text style={[styles.toggleText, repeatFrom === 'fixed-schedule' && styles.toggleTextActive]}>Fixed Schedule</Text>
                </TouchableOpacity>
              </View>
            </View>

            <View style={styles.previewBox}>
              <Text style={styles.previewText}>{recurrencePreview}</Text>
            </View>
          </>
        )}

        <View style={styles.fieldGroup}>
          <Text style={styles.label}>Assign To</Text>
          <SearchableDropdown
            items={staff}
            selectedId={assignedTo}
            onSelect={(item) => setAssignedTo(item ? Number(item.id) : null)}
            placeholder="Select staff..."
            emptyText="No staff found"
            allowClear
          />
        </View>

        <View style={styles.fieldGroup}>
          <Text style={styles.label}>Scheduled Date</Text>
          <TextInput style={styles.input} value={scheduledDate} onChangeText={setScheduledDate} placeholder="YYYY-MM-DD" />
        </View>

        <View style={styles.fieldGroup}>
          <Text style={styles.label}>Notes</Text>
          <TextInput style={[styles.input, styles.textArea]} value={notes} onChangeText={setNotes} multiline placeholder="Additional notes..." />
        </View>

        <TouchableOpacity style={styles.saveBtn} onPress={handleSave} disabled={saving}>
          {saving ? <ActivityIndicator color={COLORS.white} /> : <Text style={styles.saveBtnText}>Create Task</Text>}
        </TouchableOpacity>
      </ScrollView>
    </KeyboardAvoidingView>
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
  recurrenceRow: { flexDirection: 'row', gap: 6, alignItems: 'center' },
  unitBtn: {
    paddingVertical: 8, paddingHorizontal: 10, borderRadius: RADIUS.md,
    backgroundColor: COLORS.neutral100, borderWidth: 1, borderColor: COLORS.neutral200,
  },
  unitBtnActive: { backgroundColor: COLORS.primary, borderColor: COLORS.primary },
  unitText: { fontSize: FONT_SIZES.xs, fontWeight: '500', color: COLORS.neutral600 },
  unitTextActive: { color: COLORS.white },
  previewBox: {
    padding: SPACING[3], backgroundColor: COLORS.primary + '10', borderRadius: RADIUS.lg,
    marginBottom: SPACING[5],
  },
  previewText: { fontSize: FONT_SIZES.sm, color: COLORS.primary, fontWeight: '500' },
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
