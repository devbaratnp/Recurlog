import { useEffect, useState } from 'react';
import { View, Text, TextInput, ScrollView, TouchableOpacity, StyleSheet, Alert, ActivityIndicator } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation, useRoute } from '@react-navigation/native';
import { ArrowLeft, Check, Save } from 'lucide-react-native';
import { customersApi } from '../../api/client';
import { ServiceChip } from '../../components/ServiceChip';
import { COLORS, RADIUS, SPACING, FONT_SIZES, SHADOWS, SERVICE_COLORS } from '../../constants/theme';
import type { Customer } from '../../types';

const ALL_SERVICES = ['RO', 'TV', 'Refrigerator', 'AC', 'Washing Machine', 'Other'];

export function CustomerAddScreen() {
  const navigation = useNavigation<any>();
  const insets = useSafeAreaInsets();
  const route = useRoute<any>();
  const editId = route.params?.id;

  const [name, setName] = useState('');
  const [address, setAddress] = useState('');
  const [area, setArea] = useState('');
  const [phone, setPhone] = useState('+977-');
  const [servicesFor, setServicesFor] = useState<string[]>([]);
  const [saving, setSaving] = useState(false);
  const [loading, setLoading] = useState(!!editId);

  useEffect(() => {
    if (editId) {
      customersApi.get(editId).then(({ data }) => {
        const c = data.data;
        setName(c.name);
        setAddress(c.address);
        setArea(c.area || '');
        setPhone(c.phone);
        setServicesFor(c.servicesFor || []);
      }).finally(() => setLoading(false));
    }
  }, [editId]);

  const toggleService = (svc: string) => {
    setServicesFor((prev) =>
      prev.includes(svc) ? prev.filter((s) => s !== svc) : [...prev, svc]
    );
  };

  const validate = () => {
    if (!name.trim()) { Alert.alert('Validation', 'Name is required'); return false; }
    if (!phone.trim()) { Alert.alert('Validation', 'Phone is required'); return false; }
    return true;
  };

  const handleSave = async () => {
    if (!validate()) return;
    setSaving(true);
    try {
      const payload = { name: name.trim(), address: address.trim(), area: area.trim(), phone: phone.trim(), servicesFor };
      if (editId) {
        await customersApi.update(editId, payload);
      } else {
        await customersApi.create(payload);
      }
      navigation.goBack();
    } catch (err: any) {
      Alert.alert('Error', err?.response?.data?.error || 'Failed to save customer');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <View style={[styles.container, { justifyContent: 'center', alignItems: 'center' }]}>
        <ActivityIndicator size="large" color={COLORS.primary} />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <View style={[styles.header, { paddingTop: insets.top, minHeight: 56 + insets.top }]}>
        <View style={styles.headerLeft}>
          <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
            <ArrowLeft size={20} color={COLORS.neutral600} />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>{editId ? 'Edit Customer' : 'Add Customer'}</Text>
        </View>
        <TouchableOpacity onPress={handleSave} disabled={saving} style={styles.saveBtn}>
          {saving ? (
            <ActivityIndicator color={COLORS.white} size="small" />
          ) : (
            <>
              <Save size={16} color={COLORS.white} />
              <Text style={styles.saveBtnText}>Save</Text>
            </>
          )}
        </TouchableOpacity>
      </View>

      <ScrollView contentContainerStyle={styles.form}>
        <View style={styles.fieldGroup}>
          <Text style={styles.label}>Name</Text>
          <TextInput style={styles.input} value={name} onChangeText={setName} placeholder="Customer name" placeholderTextColor={COLORS.neutral400} maxLength={255} />
        </View>
        <View style={styles.fieldGroup}>
          <Text style={styles.label}>Address</Text>
          <TextInput style={styles.input} value={address} onChangeText={setAddress} placeholder="Address" placeholderTextColor={COLORS.neutral400} maxLength={255} />
        </View>
        <View style={styles.fieldGroup}>
          <Text style={styles.label}>Area / Locality</Text>
          <TextInput style={styles.input} value={area} onChangeText={setArea} placeholder="Area" placeholderTextColor={COLORS.neutral400} maxLength={100} />
        </View>
        <View style={styles.fieldGroup}>
          <Text style={styles.label}>Contact Number</Text>
          <TextInput style={styles.input} value={phone} onChangeText={setPhone} placeholder="+977-" placeholderTextColor={COLORS.neutral400} keyboardType="phone-pad" maxLength={20} />
        </View>
        <View style={styles.fieldGroup}>
          <Text style={styles.label}>Services For</Text>
          <View style={styles.chipRow}>
            {ALL_SERVICES.map((svc) => (
              <TouchableOpacity
                key={svc}
                style={[styles.serviceChip, servicesFor.includes(svc) && { backgroundColor: (SERVICE_COLORS[svc] || '#6B7280') + '20', borderColor: SERVICE_COLORS[svc] || '#6B7280' }]}
                onPress={() => toggleService(svc)}
              >
                {servicesFor.includes(svc) && <Check size={12} color={SERVICE_COLORS[svc] || '#6B7280'} />}
                <Text style={[styles.chipText, servicesFor.includes(svc) && { color: SERVICE_COLORS[svc] || '#6B7280', fontWeight: '600' }]}>{svc}</Text>
              </TouchableOpacity>
            ))}
          </View>
        </View>
        <View style={{ height: 80 }} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: COLORS.neutral50 },
  header: {
    flexDirection: 'row', alignItems: 'center',
    paddingHorizontal: SPACING[4], backgroundColor: COLORS.white,
    borderBottomWidth: 1, borderBottomColor: COLORS.neutral200,
  },
  headerLeft: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  backBtn: { padding: 8, minWidth: 44, minHeight: 44, justifyContent: 'center' },
  headerTitle: { fontSize: FONT_SIZES.lg, fontWeight: '700', color: COLORS.navy },
  saveBtn: {
    flexDirection: 'row', alignItems: 'center', gap: 6,
    paddingHorizontal: 16, paddingVertical: 8,
    backgroundColor: COLORS.primary, borderRadius: RADIUS.md,
    minHeight: 36, minWidth: 70, justifyContent: 'center',
  },
  saveBtnText: { color: COLORS.white, fontSize: FONT_SIZES.xs, fontWeight: '600' },
  form: { padding: SPACING[4] },
  fieldGroup: { marginBottom: SPACING[5] },
  label: { fontSize: FONT_SIZES.sm, fontWeight: '500', color: COLORS.neutral700, marginBottom: 6 },
  input: {
    height: 44, borderWidth: 1, borderColor: COLORS.neutral200, borderRadius: RADIUS.lg,
    paddingHorizontal: SPACING[4], fontSize: FONT_SIZES.sm, color: COLORS.neutral900,
    backgroundColor: COLORS.white,
  },
  chipRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  serviceChip: {
    flexDirection: 'row', alignItems: 'center', gap: 4,
    paddingHorizontal: 12, paddingVertical: 8,
    borderWidth: 1, borderColor: COLORS.neutral200, borderRadius: RADIUS.full,
    backgroundColor: COLORS.white,
  },
  chipText: { fontSize: FONT_SIZES.xs, color: COLORS.neutral700 },
});
