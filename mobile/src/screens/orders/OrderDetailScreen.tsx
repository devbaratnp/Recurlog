import { useEffect, useState } from 'react';
import { View, Text, ScrollView, TouchableOpacity, StyleSheet, ActivityIndicator, Alert, TextInput, Modal } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation, useRoute } from '@react-navigation/native';
import { ArrowLeft, User, Calendar as CalendarIcon } from 'lucide-react-native';
import { ordersApi, staffApi } from '../../api/client';
import { SearchableDropdown } from '../../components/SearchableDropdown';
import { PriorityBadge } from '../../components/PriorityBadge';
import { StatusBadge } from '../../components/StatusBadge';
import { COLORS, RADIUS, SPACING, FONT_SIZES, SHADOWS } from '../../constants/theme';
import { formatDate, todayISO } from '../../utils/date';
import type { Order } from '../../types';

export function OrderDetailScreen() {
  const navigation = useNavigation<any>();
  const insets = useSafeAreaInsets();
  const route = useRoute<any>();
  const id = route.params?.id;

  const [order, setOrder] = useState<Order | null>(null);
  const [loading, setLoading] = useState(true);
  const [staff, setStaff] = useState<any[]>([]);

  const [showAssign, setShowAssign] = useState(false);
  const [assignStaff, setAssignStaff] = useState<number | null>(null);
  const [assignDate, setAssignDate] = useState(todayISO());

  const [showComplete, setShowComplete] = useState(false);
  const [dispatchDate, setDispatchDate] = useState(todayISO());
  const [dispatchBy, setDispatchBy] = useState('');
  const [receivedName, setReceivedName] = useState('');
  const [receivedContact, setReceivedContact] = useState('');
  const [completeNotes, setCompleteNotes] = useState('');

  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (!id) { navigation.goBack(); return; }
    Promise.all([ordersApi.get(id), staffApi.list()]).then(([o, s]) => {
      setOrder(o.data?.data);
      setStaff(Array.isArray(s.data?.data) ? s.data.data : []);
    }).catch(() => navigation.goBack()).finally(() => setLoading(false));
  }, [id]);

  const refreshOrder = async () => {
    const { data } = await ordersApi.get(id);
    setOrder(data?.data);
  };

  const handleAssign = async () => {
    if (!assignStaff) { Alert.alert('Validation', 'Please select a staff member.'); return; }
    setSaving(true);
    try {
      await ordersApi.update(id, { assignedTo: assignStaff, scheduledDate: assignDate, status: 'assigned' });
      setShowAssign(false);
      await refreshOrder();
    } catch (err: any) {
      Alert.alert('Error', err?.response?.data?.error || 'Failed to assign');
    } finally { setSaving(false); }
  };

  const handleComplete = async () => {
    setSaving(true);
    try {
      await ordersApi.update(id, {
        status: 'completed',
        completedDate: todayISO(),
        dispatchDate,
        dispatchBy: dispatchBy.trim(),
        receivedName: receivedName.trim(),
        receivedContact: receivedContact.trim(),
        notes: completeNotes.trim(),
      });
      setShowComplete(false);
      await refreshOrder();
    } catch (err: any) {
      Alert.alert('Error', err?.response?.data?.error || 'Failed to complete');
    } finally { setSaving(false); }
  };

  const handleCancel = () => {
    Alert.alert('Cancel Order', 'Are you sure you want to cancel this order?', [
      { text: 'No', style: 'cancel' },
      { text: 'Cancel Order', style: 'destructive', onPress: async () => {
        try {
          await ordersApi.update(id, { status: 'cancelled' });
          await refreshOrder();
        } catch { Alert.alert('Error', 'Failed to cancel order'); }
      }},
    ]);
  };

  if (loading || !order) {
    return (
      <View style={[styles.container, { justifyContent: 'center', alignItems: 'center' }]}>
        <ActivityIndicator size="large" color={COLORS.primary} />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <View style={[styles.header, { paddingTop: insets.top, minHeight: 56 + insets.top }]}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
          <ArrowLeft size={20} color={COLORS.neutral600} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Order Detail</Text>
      </View>

      <ScrollView contentContainerStyle={styles.scroll}>
        <View style={[styles.card, SHADOWS.sm]}>
          <View style={styles.cardRow}>
            <Text style={styles.customerName}>{order.customerName}</Text>
            <PriorityBadge priority={order.priority} />
          </View>
          <StatusBadge status={order.status} />
          <Text style={styles.serviceFor}>{order.serviceFor}</Text>
          <Text style={styles.problem}>{order.problem}</Text>

          <View style={styles.metaSection}>
            {order.assignedStaffName && (
              <View style={styles.metaRow}>
                <User size={14} color={COLORS.neutral500} />
                <Text style={styles.metaText}>{order.assignedStaffName}</Text>
              </View>
            )}
            {order.scheduledDate && (
              <View style={styles.metaRow}>
                <CalendarIcon size={14} color={COLORS.neutral500} />
                <Text style={styles.metaText}>{formatDate(order.scheduledDate)}</Text>
              </View>
            )}
          </View>

          {order.notes ? <Text style={styles.notes}>{order.notes}</Text> : null}

          {order.status === 'completed' && order.dispatchDate && (
            <View style={styles.reportSection}>
              <Text style={styles.reportTitle}>Completion Report</Text>
              <Text style={styles.reportText}>Dispatch: {formatDate(order.dispatchDate)} {order.dispatchBy ? `by ${order.dispatchBy}` : ''}</Text>
              {order.receivedName && <Text style={styles.reportText}>Received by: {order.receivedName}{order.receivedContact ? ` (${order.receivedContact})` : ''}</Text>}
            </View>
          )}
        </View>

        <View style={styles.actionRow}>
          {order.status === 'pending' && (
            <>
              <TouchableOpacity style={styles.actionBtnPrimary} onPress={() => setShowAssign(true)}>
                <Text style={styles.actionBtnText}>Assign Staff</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.actionBtnDanger} onPress={handleCancel}>
                <Text style={styles.actionBtnTextDanger}>Cancel</Text>
              </TouchableOpacity>
            </>
          )}
          {order.status === 'assigned' && (
            <>
              <TouchableOpacity style={styles.actionBtnPrimary} onPress={() => setShowComplete(true)}>
                <Text style={styles.actionBtnText}>Complete</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.actionBtnDanger} onPress={handleCancel}>
                <Text style={styles.actionBtnTextDanger}>Cancel</Text>
              </TouchableOpacity>
            </>
          )}
        </View>
      </ScrollView>

      {/* Assign Modal */}
      <Modal visible={showAssign} transparent animationType="slide" onRequestClose={() => setShowAssign(false)}>
        <TouchableOpacity style={styles.modalOverlay} activeOpacity={1} onPress={() => setShowAssign(false)}>
          <TouchableOpacity style={styles.modalContent} activeOpacity={1}>
            <Text style={styles.modalTitle}>Assign Staff</Text>
            <View style={styles.fieldGroup}>
              <Text style={styles.label}>Staff Member</Text>
              <SearchableDropdown
                items={staff}
                selectedId={assignStaff}
                onSelect={(item) => setAssignStaff(item ? Number(item.id) : null)}
                placeholder="Search staff..."
                emptyText="No staff found"
                allowClear
              />
            </View>
            <View style={styles.fieldGroup}>
              <Text style={styles.label}>Scheduled Date</Text>
              <TextInput style={styles.input} value={assignDate} onChangeText={setAssignDate} placeholder="YYYY-MM-DD" />
            </View>
            <TouchableOpacity style={styles.modalBtn} onPress={handleAssign} disabled={saving}>
              {saving ? <ActivityIndicator color={COLORS.white} /> : <Text style={styles.modalBtnText}>Assign</Text>}
            </TouchableOpacity>
          </TouchableOpacity>
        </TouchableOpacity>
      </Modal>

      {/* Complete Modal */}
      <Modal visible={showComplete} transparent animationType="slide" onRequestClose={() => setShowComplete(false)}>
        <TouchableOpacity style={styles.modalOverlay} activeOpacity={1} onPress={() => setShowComplete(false)}>
          <TouchableOpacity style={styles.modalContent} activeOpacity={1}>
            <Text style={styles.modalTitle}>Complete Order</Text>
            <View style={styles.fieldGroup}>
              <Text style={styles.label}>Dispatch Date</Text>
              <TextInput style={styles.input} value={dispatchDate} onChangeText={setDispatchDate} placeholder="YYYY-MM-DD" />
            </View>
            <View style={styles.fieldGroup}>
              <Text style={styles.label}>Dispatch By</Text>
              <TextInput style={styles.input} value={dispatchBy} onChangeText={setDispatchBy} placeholder="Person who dispatched" />
            </View>
            <View style={styles.fieldGroup}>
              <Text style={styles.label}>Received By (Name)</Text>
              <TextInput style={styles.input} value={receivedName} onChangeText={setReceivedName} placeholder="Customer name" />
            </View>
            <View style={styles.fieldGroup}>
              <Text style={styles.label}>Received Contact</Text>
              <TextInput style={styles.input} value={receivedContact} onChangeText={setReceivedContact} placeholder="Phone number" keyboardType="phone-pad" />
            </View>
            <View style={styles.fieldGroup}>
              <Text style={styles.label}>Completion Notes</Text>
              <TextInput style={[styles.input, styles.textArea]} value={completeNotes} onChangeText={setCompleteNotes} multiline placeholder="Notes..." />
            </View>
            <TouchableOpacity style={styles.modalBtn} onPress={handleComplete} disabled={saving}>
              {saving ? <ActivityIndicator color={COLORS.white} /> : <Text style={styles.modalBtnText}>Complete Order</Text>}
            </TouchableOpacity>
          </TouchableOpacity>
        </TouchableOpacity>
      </Modal>
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
  scroll: { padding: SPACING[4], paddingBottom: 80 },
  card: {
    backgroundColor: COLORS.white, borderRadius: RADIUS.lg, padding: SPACING[5],
    borderWidth: 1, borderColor: COLORS.neutral100, marginBottom: SPACING[4],
  },
  cardRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 },
  customerName: { fontSize: FONT_SIZES.lg, fontWeight: '700', color: COLORS.navy },
  serviceFor: { fontSize: FONT_SIZES.base, fontWeight: '600', color: COLORS.primary, marginTop: 10 },
  problem: { fontSize: FONT_SIZES.sm, color: COLORS.neutral600, marginTop: 4, lineHeight: 20 },
  metaSection: { marginTop: 12, gap: 6 },
  metaRow: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  metaText: { fontSize: FONT_SIZES.sm, color: COLORS.neutral500 },
  notes: { fontSize: FONT_SIZES.sm, color: COLORS.neutral600, marginTop: 12, fontStyle: 'italic' },
  reportSection: { marginTop: 16, paddingTop: 12, borderTopWidth: 1, borderTopColor: COLORS.neutral100 },
  reportTitle: { fontSize: FONT_SIZES.sm, fontWeight: '600', color: COLORS.navy, marginBottom: 4 },
  reportText: { fontSize: FONT_SIZES.sm, color: COLORS.neutral600, marginBottom: 2 },
  actionRow: { flexDirection: 'row', gap: 12 },
  actionBtnPrimary: {
    flex: 1, paddingVertical: 14, borderRadius: RADIUS.lg, backgroundColor: COLORS.primary, alignItems: 'center',
  },
  actionBtnDanger: {
    flex: 1, paddingVertical: 14, borderRadius: RADIUS.lg, backgroundColor: COLORS.white,
    borderWidth: 1, borderColor: COLORS.danger, alignItems: 'center',
  },
  actionBtnText: { color: COLORS.white, fontSize: FONT_SIZES.sm, fontWeight: '600' },
  actionBtnTextDanger: { color: COLORS.danger, fontSize: FONT_SIZES.sm, fontWeight: '600' },
  modalOverlay: { flex: 1, backgroundColor: COLORS.backdrop, justifyContent: 'flex-end' },
  modalContent: {
    backgroundColor: COLORS.white, borderTopLeftRadius: RADIUS.xl, borderTopRightRadius: RADIUS.xl,
    padding: SPACING[6], maxHeight: '85%',
  },
  modalTitle: { fontSize: FONT_SIZES.lg, fontWeight: '700', color: COLORS.neutral900, marginBottom: SPACING[5] },
  fieldGroup: { marginBottom: SPACING[4] },
  label: { fontSize: FONT_SIZES.sm, fontWeight: '600', color: COLORS.neutral700, marginBottom: 6 },
  input: {
    height: 44, borderWidth: 1, borderColor: COLORS.neutral200, borderRadius: RADIUS.lg,
    paddingHorizontal: SPACING[4], fontSize: FONT_SIZES.sm, color: COLORS.neutral900, backgroundColor: COLORS.white,
  },
  textArea: { height: 80, paddingVertical: SPACING[3], textAlignVertical: 'top' },
  modalBtn: {
    paddingVertical: 14, borderRadius: RADIUS.lg, backgroundColor: COLORS.primary, alignItems: 'center', marginTop: 8,
  },
  modalBtnText: { color: COLORS.white, fontSize: FONT_SIZES.sm, fontWeight: '600' },
});
