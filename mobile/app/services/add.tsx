import { useState, useEffect } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  ScrollView,
  StyleSheet,
} from 'react-native';
import { useRouter, Stack } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { ScreenWrapper } from '../../src/components/ScreenWrapper';
import { Card } from '../../src/components/Card';
import { Button } from '../../src/components/Button';
import { useToast } from '../../src/components/Toast';
import { colors, borderRadius, typography } from '../../src/theme';
import { serviceTypes, todayISO } from '../../src/lib/helpers';

export default function AddServiceScreen() {
  const router = useRouter();
  const { showToast } = useToast();
  const [customerId, setCustomerId] = useState('');
  const [selectedServiceFor, setSelectedServiceFor] = useState('');
  const [categoryId, setCategoryId] = useState('');
  const [title, setTitle] = useState('');
  const [problem, setProblem] = useState('');
  const [isRecurring, setIsRecurring] = useState(true);
  const [recValue, setRecValue] = useState('1');
  const [recUnit, setRecUnit] = useState('months');
  const [repeatFrom, setRepeatFrom] = useState('last-done');
  const [firstDate, setFirstDate] = useState(todayISO());
  const [staffId, setStaffId] = useState('');
  const [notes, setNotes] = useState('');
  const [saving, setSaving] = useState(false);
  const [customers, setCustomers] = useState<{ id: number; name: string }[]>([]);
  const [categories, setCategories] = useState<{ id: number; name: string }[]>([]);
  const [staff, setStaff] = useState<{ id: number; name: string }[]>([]);

  useEffect(() => {
    setCustomers([
      { id: 1, name: 'Sharma Family' },
      { id: 2, name: 'Gupta Traders' },
      { id: 3, name: 'Mehta & Co' },
      { id: 4, name: 'Patel Residence' },
    ]);
    setCategories([
      { id: 1, name: 'Water Purifier' },
      { id: 2, name: 'Electronics' },
      { id: 3, name: 'Appliances' },
      { id: 4, name: 'Cooling' },
    ]);
    setStaff([
      { id: 1, name: 'Rajesh Yadav' },
      { id: 2, name: 'Sita Thapa' },
      { id: 3, name: 'Bhim Singh' },
      { id: 4, name: 'Gita Sharma' },
    ]);
  }, []);

  const recPreview = `This service will repeat every ${recValue} ${recValue === '1' ? recUnit.slice(0, -1) : recUnit} from the ${repeatFrom === 'last-done' ? 'Last Done Date' : 'Fixed Schedule'}`;

  const handleSave = async () => {
    if (!customerId) { showToast('Please select a customer', 'error'); return; }
    if (!selectedServiceFor) { showToast('Please select a service type', 'error'); return; }
    if (!categoryId) { showToast('Please select a category', 'error'); return; }
    if (!firstDate) { showToast('Please select a first scheduled date', 'error'); return; }
    if (!staffId) { showToast('Please assign a staff member', 'error'); return; }

    setSaving(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 500));
      showToast('Service added successfully!', 'success');
      router.back();
    } catch {
      showToast('Failed to save service', 'error');
    } finally {
      setSaving(false);
    }
  };

  return (
    <ScreenWrapper>
      <Stack.Screen options={{ title: 'Add Service' }} />
      <ScrollView contentContainerStyle={styles.scrollContent}>
        <View style={styles.header}>
          <TouchableOpacity onPress={() => router.back()} activeOpacity={0.7}>
            <Ionicons name="arrow-back" size={24} color={colors.gray[400]} />
          </TouchableOpacity>
          <Text style={styles.title}>Add Service</Text>
        </View>

        <Card style={styles.formCard}>
          {/* Customer Dropdown */}
          <View style={styles.inputGroup}>
            <Text style={styles.label}>Customer</Text>
            <View style={styles.selectContainer}>
              {customers.map((c) => (
                <TouchableOpacity
                  key={c.id}
                  style={[styles.selectOption, customerId === String(c.id) && styles.selectOptionActive]}
                  onPress={() => setCustomerId(String(c.id))}
                  activeOpacity={0.7}
                >
                  <Text style={[styles.selectOptionText, customerId === String(c.id) && styles.selectOptionTextActive]}>
                    {c.name}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>
          </View>

          {/* Service For */}
          <View style={styles.inputGroup}>
            <Text style={styles.label}>Service For</Text>
            <View style={styles.chipsContainer}>
              {serviceTypes.map((st) => (
                <TouchableOpacity
                  key={st}
                  style={[styles.chip, selectedServiceFor === st && styles.chipSelected]}
                  onPress={() => setSelectedServiceFor(st)}
                  activeOpacity={0.7}
                >
                  <Text style={[styles.chipText, selectedServiceFor === st && styles.chipTextSelected]}>
                    {st}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>
          </View>

          {/* Problem */}
          <View style={styles.inputGroup}>
            <Text style={styles.label}>Problem / Issue Description</Text>
            <TextInput
              style={[styles.input, styles.textarea]}
              value={problem}
              onChangeText={setProblem}
              placeholder="Describe the customer's problem or issue in detail..."
              multiline
              numberOfLines={3}
            />
          </View>

          {/* Category */}
          <View style={styles.inputGroup}>
            <Text style={styles.label}>Category</Text>
            <View style={styles.selectContainer}>
              {categories.map((cat) => (
                <TouchableOpacity
                  key={cat.id}
                  style={[styles.selectOption, categoryId === String(cat.id) && styles.selectOptionActive]}
                  onPress={() => setCategoryId(String(cat.id))}
                  activeOpacity={0.7}
                >
                  <Text style={[styles.selectOptionText, categoryId === String(cat.id) && styles.selectOptionTextActive]}>
                    {cat.name}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>
          </View>

          {/* Service Type Toggle */}
          <View style={styles.inputGroup}>
            <Text style={styles.label}>Service Type</Text>
            <View style={styles.toggleContainer}>
              <TouchableOpacity
                style={[styles.toggleBtn, !isRecurring && styles.toggleBtnActive]}
                onPress={() => setIsRecurring(false)}
                activeOpacity={0.7}
              >
                <Text style={[styles.toggleBtnText, !isRecurring && styles.toggleBtnTextActive]}>One Time</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.toggleBtn, isRecurring && styles.toggleBtnActive]}
                onPress={() => setIsRecurring(true)}
                activeOpacity={0.7}
              >
                <Text style={[styles.toggleBtnText, isRecurring && styles.toggleBtnTextActive]}>Recurring</Text>
              </TouchableOpacity>
            </View>
          </View>

          {/* Recurrence Section */}
          {isRecurring && (
            <View style={styles.recurrenceSection}>
              <View style={styles.recurrenceHeader}>
                <Ionicons name="repeat" size={16} color={colors.brand} />
                <Text style={styles.recurrenceTitle}>Recurrence Settings</Text>
              </View>

              <View style={styles.recurrenceRow}>
                <Text style={styles.recurrenceLabel}>Repeat Every</Text>
                <TextInput
                  style={[styles.input, { width: 70, textAlign: 'center' }]}
                  value={recValue}
                  onChangeText={setRecValue}
                  keyboardType="number-pad"
                />
                <View style={styles.unitSelector}>
                  {['days', 'weeks', 'months', 'years'].map((unit) => (
                    <TouchableOpacity
                      key={unit}
                      style={[styles.unitBtn, recUnit === unit && styles.unitBtnActive]}
                      onPress={() => setRecUnit(unit)}
                      activeOpacity={0.7}
                    >
                      <Text style={[styles.unitBtnText, recUnit === unit && styles.unitBtnTextActive]}>
                        {unit.charAt(0).toUpperCase() + unit.slice(1)}
                      </Text>
                    </TouchableOpacity>
                  ))}
                </View>
              </View>

              <View style={styles.radioGroup}>
                <Text style={styles.recurrenceLabel}>Repeat From</Text>
                <View style={styles.radioRow}>
                  <TouchableOpacity
                    style={styles.radio}
                    onPress={() => setRepeatFrom('last-done')}
                    activeOpacity={0.7}
                  >
                    <View style={[styles.radioCircle, repeatFrom === 'last-done' && styles.radioCircleActive]}>
                      {repeatFrom === 'last-done' && <View style={styles.radioDot} />}
                    </View>
                    <Text style={styles.radioLabel}>Last Done Date</Text>
                  </TouchableOpacity>
                  <TouchableOpacity
                    style={styles.radio}
                    onPress={() => setRepeatFrom('fixed')}
                    activeOpacity={0.7}
                  >
                    <View style={[styles.radioCircle, repeatFrom === 'fixed' && styles.radioCircleActive]}>
                      {repeatFrom === 'fixed' && <View style={styles.radioDot} />}
                    </View>
                    <Text style={styles.radioLabel}>Fixed Schedule</Text>
                  </TouchableOpacity>
                </View>
              </View>

              <View style={styles.previewBox}>
                <Text style={styles.previewText}>{recPreview}</Text>
              </View>
            </View>
          )}

          {/* First Scheduled Date */}
          <View style={styles.inputGroup}>
            <Text style={styles.label}>First Scheduled Date</Text>
            <TextInput
              style={styles.input}
              value={firstDate}
              onChangeText={setFirstDate}
              placeholder="YYYY-MM-DD"
            />
          </View>

          {/* Assign To */}
          <View style={styles.inputGroup}>
            <Text style={styles.label}>Assign To</Text>
            <View style={styles.selectContainer}>
              {staff.map((s) => (
                <TouchableOpacity
                  key={s.id}
                  style={[styles.selectOption, staffId === String(s.id) && styles.selectOptionActive]}
                  onPress={() => setStaffId(String(s.id))}
                  activeOpacity={0.7}
                >
                  <Text style={[styles.selectOptionText, staffId === String(s.id) && styles.selectOptionTextActive]}>
                    {s.name}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>
          </View>

          {/* Notes */}
          <View style={styles.inputGroup}>
            <Text style={styles.label}>Notes</Text>
            <TextInput
              style={[styles.input, styles.textarea]}
              value={notes}
              onChangeText={setNotes}
              placeholder="Any additional notes..."
              multiline
              numberOfLines={4}
            />
          </View>

          {/* Save */}
          <Button
            title="Create Service"
            onPress={handleSave}
            variant="primary"
            loading={saving}
            fullWidth
          />
        </Card>
      </ScrollView>
    </ScreenWrapper>
  );
}

const styles = StyleSheet.create({
  scrollContent: {
    padding: 16,
    paddingBottom: 32,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 16,
  },
  title: {
    fontSize: typography.fontSize.xl,
    fontWeight: typography.fontWeight.bold,
    color: colors.navy,
  },
  formCard: {
    gap: 20,
  },
  inputGroup: {
    gap: 6,
  },
  label: {
    fontSize: typography.fontSize.sm,
    fontWeight: typography.fontWeight.semibold,
    color: colors.gray[700],
  },
  input: {
    borderWidth: 1,
    borderColor: colors.gray[200],
    borderRadius: borderRadius.lg,
    paddingHorizontal: 14,
    paddingVertical: 12,
    fontSize: typography.fontSize.sm,
    color: colors.gray[900],
    backgroundColor: colors.white,
  },
  textarea: {
    minHeight: 80,
    textAlignVertical: 'top',
  },
  chipsContainer: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  chip: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: borderRadius.full,
    borderWidth: 1,
    borderColor: colors.gray[200],
    backgroundColor: colors.white,
  },
  chipSelected: {
    backgroundColor: colors.brand,
    borderColor: colors.brand,
  },
  chipText: {
    fontSize: typography.fontSize.sm,
    fontWeight: typography.fontWeight.medium,
    color: colors.gray[600],
  },
  chipTextSelected: {
    color: colors.white,
  },
  selectContainer: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  selectOption: {
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: borderRadius.md,
    borderWidth: 1,
    borderColor: colors.gray[200],
    backgroundColor: colors.white,
  },
  selectOptionActive: {
    backgroundColor: colors.brand,
    borderColor: colors.brand,
  },
  selectOptionText: {
    fontSize: typography.fontSize.sm,
    fontWeight: typography.fontWeight.medium,
    color: colors.gray[600],
  },
  selectOptionTextActive: {
    color: colors.white,
  },
  toggleContainer: {
    flexDirection: 'row',
    borderRadius: borderRadius.md,
    borderWidth: 1,
    borderColor: colors.gray[300],
    overflow: 'hidden',
    alignSelf: 'flex-start',
  },
  toggleBtn: {
    paddingHorizontal: 20,
    paddingVertical: 10,
  },
  toggleBtnActive: {
    backgroundColor: colors.brand,
  },
  toggleBtnText: {
    fontSize: typography.fontSize.sm,
    fontWeight: typography.fontWeight.medium,
    color: colors.gray[600],
  },
  toggleBtnTextActive: {
    color: colors.white,
  },
  recurrenceSection: {
    backgroundColor: colors.gray[50],
    borderRadius: borderRadius.lg,
    borderWidth: 1,
    borderColor: colors.gray[200],
    padding: 16,
    gap: 16,
  },
  recurrenceHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  recurrenceTitle: {
    fontSize: typography.fontSize.sm,
    fontWeight: typography.fontWeight.semibold,
    color: colors.gray[700],
  },
  recurrenceRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    flexWrap: 'wrap',
  },
  recurrenceLabel: {
    fontSize: typography.fontSize.sm,
    color: colors.gray[600],
  },
  unitSelector: {
    flexDirection: 'row',
    gap: 4,
  },
  unitBtn: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: borderRadius.sm,
    backgroundColor: colors.white,
    borderWidth: 1,
    borderColor: colors.gray[200],
  },
  unitBtnActive: {
    backgroundColor: colors.brand,
    borderColor: colors.brand,
  },
  unitBtnText: {
    fontSize: typography.fontSize.xs,
    color: colors.gray[600],
    fontWeight: typography.fontWeight.medium,
  },
  unitBtnTextActive: {
    color: colors.white,
  },
  radioGroup: {
    gap: 8,
  },
  radioRow: {
    flexDirection: 'row',
    gap: 20,
  },
  radio: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  radioCircle: {
    width: 20,
    height: 20,
    borderRadius: 10,
    borderWidth: 2,
    borderColor: colors.gray[300],
    alignItems: 'center',
    justifyContent: 'center',
  },
  radioCircleActive: {
    borderColor: colors.brand,
  },
  radioDot: {
    width: 10,
    height: 10,
    borderRadius: 5,
    backgroundColor: colors.brand,
  },
  radioLabel: {
    fontSize: typography.fontSize.sm,
    color: colors.gray[600],
  },
  previewBox: {
    backgroundColor: colors.white,
    borderRadius: borderRadius.md,
    borderWidth: 1,
    borderColor: colors.gray[200],
    padding: 10,
  },
  previewText: {
    fontSize: typography.fontSize.sm,
    color: colors.gray[500],
    fontStyle: 'italic',
  },
});
