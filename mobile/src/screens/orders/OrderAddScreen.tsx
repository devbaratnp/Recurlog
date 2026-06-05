import { useState, useEffect } from 'react';
import { View, Text, TextInput, TouchableOpacity, ScrollView, StyleSheet, ActivityIndicator, Alert, KeyboardAvoidingView, Platform } from 'react-native';
import { useNavigation, useRoute } from '@react-navigation/native';
import { ArrowLeft, Check } from 'lucide-react-native';
import { ordersApi, customersApi, staffApi } from '../../api/client';
import { COLORS, RADIUS, SPACING, FONT_SIZES, SHADOWS } from '../../constants/theme';
import { todayISO } from '../../utils/date';

export function OrderAddScreen() {
  const navigation = useNavigation<any>();
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

  const [customers, setCustomers] = useState<any[]>([]);
  const [staff, setStaff] = useState<any[]>([]);
  const [customerSearch, setCustomerSearch] = useState('');
  const [showCustomerDropdown, setShowCustomerDropdown] = useState(false);
  const [showStaffDropdown, setShowStaffDropdown] = useState(false);

  useEffect(() => {
    Promise.all([customersApi.list(), staffApi.list()]).then(([c, s]) => {
      setCustomers(Array.isArray(c.data?.data) ? c.data.data : []);
      setStaff(Array.isArray(s.data?.data) ? s.data.data : []);
    });
  }, []);

  const filteredCustomers = customers.filter((c: any) =>
    c.name?.toLowerCase().includes(customerSearch.toLowerCase())
  );

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
      navigation.goBack();
    } catch (err: any) {
      Alert.alert('Error', err?.response?.data?.error || 'Failed to create order');
    } finally {
      setSaving(false);
    }
  };

  return (
    <KeyboardAvoidingView style={styles.container} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <View style={styles.header}>
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
          <TextInput
            style={styles.input}
            value={customerSearch}
            onChangeText={(t) => { setCustomerSearch(t); setShowCustomerDropdown(true); }}
            placeholder="Search customers..."
            placeholderTextColor={COLORS.neutral400}
          />
          {showCustomerDropdown && customerSearch.length > 0 && (
            <View style={styles.dropdown}>
              {filteredCustomers.map((c: any) => (
                <TouchableOpacity
                  key={c.id}
                  style={[styles.dropdownItem, customerId === c.id && styles.dropdownItemActive]}
                  onPress={() => {
                    setCustomerId(c.id);
                    setCustomerName(c.name);
                    setCustomerSearch(c.name);
                    setShowCustomerDropdown(false);
                  }}
                >
                  <Text style={styles.dropdownItemText}>{c.name}</Text>
                  {customerId === c.id && <Check size={16} color={COLORS.primary} />}
                </TouchableOpacity>
              ))}
              {filteredCustomers.length === 0 && (
                <Text style={styles.dropdownEmpty}>No customers found</Text>
              )}
            </View>
          )}
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
          <TouchableOpacity style={styles.selectBtn} onPress={() => setShowStaffDropdown(!showStaffDropdown)}>
            <Text style={assignedTo ? styles.selectText : styles.selectPlaceholder}>
              {assignedTo ? staff.find((s: any) => s.id === assignedTo)?.name || 'Selected' : 'Select staff...'}
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
                  {assignedTo === s.id && <Check size={16} color={COLORS.primary} />}
                </TouchableOpacity>
              ))}
            </View>
          )}
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
    paddingHorizontal: SPACING[4], height: 56, backgroundColor: COLORS.white,
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
  dropdownEmpty: { padding: SPACING[4], fontSize: FONT_SIZES.sm, color: COLORS.neutral400, textAlign: 'center' },
});
