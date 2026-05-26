import { useState } from 'react';
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
import { serviceTypes } from '../../src/lib/helpers';

export default function AddCustomerScreen() {
  const router = useRouter();
  const { showToast } = useToast();
  const [name, setName] = useState('');
  const [address, setAddress] = useState('');
  const [area, setArea] = useState('');
  const [phone, setPhone] = useState('+977-');
  const [selectedServices, setSelectedServices] = useState<string[]>([]);
  const [saving, setSaving] = useState(false);

  const toggleService = (service: string) => {
    setSelectedServices((prev) =>
      prev.includes(service)
        ? prev.filter((s) => s !== service)
        : [...prev, service]
    );
  };

  const handleSave = async () => {
    if (!name.trim()) {
      showToast('Please enter the customer name.', 'error');
      return;
    }
    if (!address.trim()) {
      showToast('Please enter the customer address.', 'error');
      return;
    }
    if (!area.trim()) {
      showToast('Please enter the area / locality.', 'error');
      return;
    }
    if (!phone.trim() || phone.trim() === '+977-') {
      showToast('Please enter a valid contact number.', 'error');
      return;
    }
    if (selectedServices.length === 0) {
      showToast('Please select at least one service.', 'error');
      return;
    }

    setSaving(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 500));
      showToast('Customer added successfully!', 'success');
      router.back();
    } catch {
      showToast('Failed to save customer', 'error');
    } finally {
      setSaving(false);
    }
  };

  return (
    <ScreenWrapper>
      <Stack.Screen options={{ title: 'Add Customer' }} />
      <ScrollView contentContainerStyle={styles.scrollContent}>
        <View style={styles.header}>
          <TouchableOpacity onPress={() => router.back()} activeOpacity={0.7}>
            <Ionicons name="arrow-back" size={24} color={colors.gray[400]} />
          </TouchableOpacity>
          <Text style={styles.title}>Add Customer</Text>
        </View>

        <Text style={styles.subtitle}>Register a new customer and their service details</Text>

        <Card style={styles.formCard}>
          <View style={styles.inputGroup}>
            <Text style={styles.label}>Customer Name <Text style={styles.required}>*</Text></Text>
            <TextInput
              style={styles.input}
              value={name}
              onChangeText={setName}
              placeholder="e.g. Sharma Family"
              placeholderTextColor={colors.gray[400]}
            />
          </View>

          <View style={styles.inputGroup}>
            <Text style={styles.label}>Address <Text style={styles.required}>*</Text></Text>
            <TextInput
              style={styles.input}
              value={address}
              onChangeText={setAddress}
              placeholder="e.g. Adarsh Nagar, Birgunj"
              placeholderTextColor={colors.gray[400]}
            />
          </View>

          <View style={styles.inputGroup}>
            <Text style={styles.label}>Area / Locality <Text style={styles.required}>*</Text></Text>
            <TextInput
              style={styles.input}
              value={area}
              onChangeText={setArea}
              placeholder="e.g. Adarsh Nagar, Ward No. 5"
              placeholderTextColor={colors.gray[400]}
            />
          </View>

          <View style={styles.inputGroup}>
            <Text style={styles.label}>Contact Number <Text style={styles.required}>*</Text></Text>
            <TextInput
              style={styles.input}
              value={phone}
              onChangeText={setPhone}
              placeholder="+977-98XXXXXXXX"
              placeholderTextColor={colors.gray[400]}
              keyboardType="phone-pad"
            />
          </View>

          <View style={styles.inputGroup}>
            <Text style={styles.label}>Service For <Text style={styles.required}>*</Text></Text>
            <View style={styles.chipsContainer}>
              {serviceTypes.map((service) => (
                <TouchableOpacity
                  key={service}
                  style={[
                    styles.chip,
                    selectedServices.includes(service) && styles.chipSelected,
                  ]}
                  onPress={() => toggleService(service)}
                  activeOpacity={0.7}
                >
                  <Text
                    style={[
                      styles.chipText,
                      selectedServices.includes(service) && styles.chipTextSelected,
                    ]}
                  >
                    {service}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>
          </View>

          <View style={styles.inputGroup}>
            <Text style={styles.label}>Location Map</Text>
            <View style={styles.mapPlaceholder}>
              <Ionicons name="map-outline" size={32} color={colors.gray[300]} />
              <Text style={styles.mapPlaceholderText}>Tap to set location</Text>
            </View>
            <View style={styles.locationRow}>
              <View style={styles.locationField}>
                <Text style={styles.locationLabel}>Latitude</Text>
                <TextInput style={styles.locationInput} value="27.0000" editable={false} />
              </View>
              <View style={styles.locationField}>
                <Text style={styles.locationLabel}>Longitude</Text>
                <TextInput style={styles.locationInput} value="84.8700" editable={false} />
              </View>
            </View>
          </View>
        </Card>

        <View style={styles.formActions}>
          <Button title="Cancel" onPress={() => router.back()} variant="secondary" style={{ flex: 1 }} />
          <Button
            title="Save Customer"
            onPress={handleSave}
            variant="primary"
            loading={saving}
            style={{ flex: 1 }}
          />
        </View>
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
    marginBottom: 4,
  },
  title: {
    fontSize: typography.fontSize.xl,
    fontWeight: typography.fontWeight.bold,
    color: colors.navy,
  },
  subtitle: {
    fontSize: typography.fontSize.sm,
    color: colors.gray[500],
    marginBottom: 16,
    marginTop: 8,
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
  required: {
    color: colors.danger,
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
  chipsContainer: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  chip: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: borderRadius.lg,
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
  mapPlaceholder: {
    height: 200,
    backgroundColor: colors.gray[50],
    borderRadius: borderRadius.lg,
    borderWidth: 1,
    borderColor: colors.gray[200],
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
  },
  mapPlaceholderText: {
    fontSize: typography.fontSize.sm,
    color: colors.gray[400],
  },
  locationRow: {
    flexDirection: 'row',
    gap: 12,
    marginTop: 12,
  },
  locationField: {
    flex: 1,
  },
  locationLabel: {
    fontSize: typography.fontSize.xs,
    fontWeight: typography.fontWeight.medium,
    color: colors.gray[500],
    marginBottom: 4,
  },
  locationInput: {
    backgroundColor: colors.gray[50],
    borderWidth: 1,
    borderColor: colors.gray[200],
    borderRadius: borderRadius.md,
    paddingHorizontal: 12,
    paddingVertical: 8,
    fontSize: typography.fontSize.sm,
    color: colors.gray[600],
  },
  formActions: {
    flexDirection: 'row',
    gap: 12,
    marginTop: 24,
  },
});
