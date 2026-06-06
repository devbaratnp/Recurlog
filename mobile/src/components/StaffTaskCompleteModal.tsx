import { useState } from 'react';
import {
  View, Text, TextInput, TouchableOpacity, StyleSheet,
  Modal, ScrollView, KeyboardAvoidingView, Platform, ActivityIndicator, Alert,
} from 'react-native';
import { X } from 'lucide-react-native';
import { tasksApi } from '../api/client';
import { SignaturePad } from './SignaturePad';
import { useToastStore } from '../store/toastStore';
import { COLORS, RADIUS, SPACING, FONT_SIZES, SHADOWS } from '../constants/theme';
import type { Task } from '../types';

interface StaffTaskCompleteModalProps {
  visible: boolean;
  task: Task | null;
  onClose: () => void;
  onComplete: () => void;
}

export function StaffTaskCompleteModal({ visible, task, onClose, onComplete }: StaffTaskCompleteModalProps) {
  const [notes, setNotes] = useState('');
  const [receivedName, setReceivedName] = useState('');
  const [receivedContact, setReceivedContact] = useState('');
  const [signature, setSignature] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const showToast = useToastStore((s) => s.show);

  const reset = () => {
    setNotes('');
    setReceivedName('');
    setReceivedContact('');
    setSignature('');
  };

  const handleSubmit = async () => {
    if (!task) return;
    setSubmitting(true);
    try {
      await tasksApi.update(task.id, {
        status: 'completed',
        notes: notes || 'Completed via mobile',
        receivedName: receivedName || undefined,
        receivedContact: receivedContact || undefined,
        signature: signature || undefined,
        completedDate: new Date().toISOString().split('T')[0],
      } as any);
      reset();
      showToast('Task completed successfully', 'success');
      onComplete();
    } catch { Alert.alert('Error', 'Failed to complete task'); } finally {
      setSubmitting(false);
    }
  };

  return (
    <Modal visible={visible} transparent animationType="slide" onRequestClose={onClose}>
      <KeyboardAvoidingView
        style={styles.overlay}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      >
        <TouchableOpacity style={StyleSheet.absoluteFill} activeOpacity={1} onPress={onClose} />
        <View style={[styles.modal, SHADOWS.lg]}>
          <ScrollView bounces={false} keyboardShouldPersistTaps="handled">
            <View style={styles.modalHeader}>
              <View>
                <Text style={styles.modalTitle}>Complete Task</Text>
                {task && <Text style={styles.modalTaskTitle}>{task.title}</Text>}
              </View>
              <TouchableOpacity onPress={onClose} style={styles.closeBtn}>
                <X size={20} color={COLORS.neutral500} />
              </TouchableOpacity>
            </View>

            <View style={styles.formBody}>
              <Text style={styles.label}>Notes</Text>
              <TextInput
                style={styles.textArea}
                value={notes}
                onChangeText={setNotes}
                placeholder="Completion notes..."
                multiline
                numberOfLines={3}
                maxLength={500}
              />

              <Text style={styles.label}>Received By</Text>
              <TextInput
                style={styles.input}
                value={receivedName}
                onChangeText={setReceivedName}
                placeholder="Name of person who received"
                maxLength={100}
              />

              <Text style={styles.label}>Contact</Text>
              <TextInput
                style={styles.input}
                value={receivedContact}
                onChangeText={setReceivedContact}
                placeholder="Phone or email"
                keyboardType="phone-pad"
                maxLength={50}
              />

              <Text style={styles.label}>Signature</Text>
              <SignaturePad onData={setSignature} />
            </View>

            <View style={styles.modalActions}>
              <TouchableOpacity
                style={[styles.submitBtn, submitting && styles.submitBtnDisabled]}
                onPress={handleSubmit}
                disabled={submitting}
              >
                {submitting ? (
                  <ActivityIndicator size="small" color={COLORS.white} />
                ) : (
                  <Text style={styles.submitBtnText}>Mark Complete</Text>
                )}
              </TouchableOpacity>
            </View>
          </ScrollView>
        </View>
      </KeyboardAvoidingView>
    </Modal>
  );
}

const styles = StyleSheet.create({
  overlay: {
    flex: 1, justifyContent: 'flex-end', backgroundColor: 'rgba(0,0,0,0.4)',
  },
  modal: {
    backgroundColor: COLORS.white,
    borderTopLeftRadius: RADIUS.xl,
    borderTopRightRadius: RADIUS.xl,
    maxHeight: '85%',
  },
  modalHeader: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start',
    padding: SPACING[5], borderBottomWidth: 1, borderBottomColor: COLORS.neutral100,
  },
  modalTitle: { fontSize: FONT_SIZES.lg, fontWeight: '700', color: COLORS.navy },
  modalTaskTitle: { fontSize: FONT_SIZES.sm, color: COLORS.neutral500, marginTop: 2 },
  closeBtn: { padding: 4 },
  formBody: { padding: SPACING[5], gap: SPACING[4] },
  label: { fontSize: FONT_SIZES.sm, fontWeight: '600', color: COLORS.neutral700 },
  input: {
    height: 44, borderWidth: 1, borderColor: COLORS.neutral200,
    borderRadius: RADIUS.md, paddingHorizontal: SPACING[3],
    fontSize: FONT_SIZES.sm, color: COLORS.neutral900, backgroundColor: COLORS.white,
  },
  textArea: {
    borderWidth: 1, borderColor: COLORS.neutral200,
    borderRadius: RADIUS.md, padding: SPACING[3],
    fontSize: FONT_SIZES.sm, color: COLORS.neutral900, backgroundColor: COLORS.white,
    minHeight: 80, textAlignVertical: 'top',
  },
  modalActions: { padding: SPACING[5], paddingTop: 0 },
  submitBtn: {
    backgroundColor: COLORS.primary, borderRadius: RADIUS.lg,
    height: 48, alignItems: 'center', justifyContent: 'center',
  },
  submitBtnDisabled: { opacity: 0.6 },
  submitBtnText: { color: COLORS.white, fontSize: FONT_SIZES.sm, fontWeight: '700' },
});
