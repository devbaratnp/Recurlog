import { useState, useEffect } from 'react';
import { View, Text, TextInput, TouchableOpacity, ScrollView, StyleSheet, ActivityIndicator, Alert, KeyboardAvoidingView, Platform } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation, useRoute } from '@react-navigation/native';
import { ArrowLeft } from 'lucide-react-native';
import { ordersApi, customersApi, staffApi } from '../../api/client';
import { SearchableDropdown } from '../../components/SearchableDropdown';
import { useToastStore } from '../../store/toastStore';
import { COLORS, RADIUS, SPACING, FONT_SIZES, SHADOWS } from '../../constants/theme';
import { todayISO } from '../../utils/date';

export function OrderAddScreen() {
  const navigation = useNavigation<any>();
  const insets = useSafeAreaInsets();
  const route = useRoute<any>();
  const editId = route.params?.id;

  const [customerId, setCustomerId] = useState<number | null>(null);
  const [customerName, setCustomerName] = useState('');
  const [serviceFor, setServiceFor] = useState('');
  const [problem, setProblem] = useState('');
  const [priority, setPriority] = useState<'normal' | 'urgent'>('normal');
  const [assignedTo, setAssignedTo] = useState<number | null>(null);
  const [scheduledDate, setScheduledDate] = useState(todayISO());
  const [notes, setNotes] = useState('');
  const [saving, setSaving] = useState(false);
  const showToast = useToastStore((s) => s.show);

  const [customers, setCustomers] = useState<any[]>([]);
  const [staff, setStaff] = useState<any[]>([]);

  useEffect(() => {
    Promise.all([customersApi.list(), staffApi.list()]).then(([c, s]) => {
      setCustomers(Array.isArray(c.data?.data) ? c.data.data : []);
      setStaff(Array.isArray(s.data?.data) ? s.data.data : []);
    });
  }, []);

  const handleSave = async () => {
    if (!customerId) { Alert.alert('Validation', 'Please select a customer.'); return; }
    if (!serviceFor.trim()) { Alert.alert('Validation', 'Please enter service for.'); return; }
    setSaving(true);
    try {
      await ordersApi.create({
        customerId,
        customerName,
        serviceFor: serviceFor.trim(),
        problem: problem.trim(),
        priority,
        assignedTo,
        scheduledDate,
        notes: notes.trim(),
        status: assignedTo ? 'assigned' : 'pending',
      });
      showToast('Order created successfully', 'success');
      navigation.goBack();
    } catch (err: any) {
      Alert.alert('Error', err?.response?.data?.error || 'Failed to create order');
    } finally {
      setSaving(false);
    }
  };

  return (
    <KeyboardAvoidingView style={styles.container} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <View style={[styles.header, { paddingTop: insets.top, minHeight: 56 + insets.top }]}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
          <ArrowLeft size={20} color={COLORS.neutral600} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>{editId ? 'Edit Order' : 'New Order'}</Text>
        <TouchableOpacity onPress={handleSave} disabled={saving} style={styles.saveBtn}>
          {saving ? <ActivityIndicator size="small" color={COLORS.white} /> : <Text style={styles.saveBtnText}>Save</Text>}
        </TouchableOpacity>
      </View>

      <ScrollView contentContainerStyle={styles.scroll}>
        <View style={styles.fieldGroup}>
          <Text style={styles.label}>Customer</Text>
          <SearchableDropdown
            items={customers}
            selectedId={customerId}
            onSelect={(item) => {
              if (item) {
                setCustomerId(Number(item.id));
                setCustomerName(item.name);
              }
            }}
            placeholder="Search customers..."
            emptyText="No customers found"
          />
        </View>

        <View style={styles.fieldGroup}>
          <Text style={styles.label}>Service For</Text>
          <TextInput style={styles.input} value={serviceFor} onChangeText={setServiceFor} placeholder="e.g. RO, AC, TV" placeholderTextColor={COLORS.neutral400} />
        </View>

        <View style={styles.fieldGroup}>
          <Text style={styles.label}>Problem Description</Text>
          <TextInput style={[styles.input, styles.textArea]} value={problem} onChangeText={setProblem} multiline placeholder="Describe the issue..." placeholderTextColor={COLORS.neutral400} />
        </View>

        <View style={styles.fieldGroup}>
          <Text style={styles.label}>Priority</Text>
          <View style={styles.toggleRow}>
            <TouchableOpacity
              style={[styles.toggleBtn, priority === 'normal' && styles.toggleActive]}
              onPress={() => setPriority('normal')}
            >
              <Text style={[styles.toggleText, priority === 'normal' && styles.toggleTextActive]}>Normal</Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[styles.toggleBtn, priority === 'urgent' && styles.toggleActiveDanger]}
              onPress={() => setPriority('urgent')}
            >
              <Text style={[styles.toggleText, priority === 'urgent' && styles.toggleTextActive]}>Urgent</Text>
            </TouchableOpacity>
          </View>
        </View>

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
          <TextInput style={styles.input} value={scheduledDate} onChangeText={setScheduledDate} placeholder="YYYY-MM-DD" placeholderTextColor={COLORS.neutral400} />
        </View>

        <View style={styles.fieldGroup}>
          <Text style={styles.label}>Notes</Text>
          <TextInput style={[styles.input, styles.textArea]} value={notes} onChangeText={setNotes} multiline placeholder="Additional notes..." placeholderTextColor={COLORS.neutral400} />
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: COLORS.neutral50 },
  header: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: SPACING[4], backgroundColor: COLORS.white,
    borderBottomWidth: 1, borderBottomColor: COLORS.neutral200,
  },
  backBtn: { padding: 8, minWidth: 44, minHeight: 44, justifyContent: 'center' },
  headerTitle: { fontSize: FONT_SIZES.lg, fontWeight: '700', color: COLORS.navy },
  saveBtn: {
    paddingHorizontal: 16, paddingVertical: 8, backgroundColor: COLORS.primary,
    borderRadius: RADIUS.md, minWidth: 60, alignItems: 'center',
  },
  saveBtnText: { color: COLORS.white, fontSize: FONT_SIZES.sm, fontWeight: '600' },
  scroll: { padding: SPACING[4], paddingBottom: 80 },
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
  toggleActiveDanger: { backgroundColor: COLORS.danger, borderColor: COLORS.danger },
  toggleText: { fontSize: FONT_SIZES.sm, fontWeight: '500', color: COLORS.neutral600 },
  toggleTextActive: { color: COLORS.white },
});
