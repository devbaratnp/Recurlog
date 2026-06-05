import { useEffect, useState } from 'react';
import { View, Text, ScrollView, TouchableOpacity, StyleSheet, ActivityIndicator, Alert } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation, useRoute } from '@react-navigation/native';
import { ArrowLeft, Plus, Circle, Check as CheckIcon } from 'lucide-react-native';
import { customersApi, servicesApi, tasksApi } from '../../api/client';
import { StatusBadge } from '../../components/StatusBadge';
import { ServiceChip } from '../../components/ServiceChip';
import { EmptyState } from '../../components/EmptyState';
import { COLORS, RADIUS, SPACING, FONT_SIZES, SHADOWS } from '../../constants/theme';
import { formatDate } from '../../utils/date';
import type { Customer, Service, Task } from '../../types';

export function CustomerDetailScreen() {
  const navigation = useNavigation<any>();
  const insets = useSafeAreaInsets();
  const route = useRoute<any>();
  const id = route.params?.id;

  const [customer, setCustomer] = useState<Customer | null>(null);
  const [services, setServices] = useState<Service[]>([]);
  const [tasks, setTasks] = useState<Task[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!id) { navigation.goBack(); return; }
    const load = async () => {
      try {
        const [custRes, svcRes, taskRes] = await Promise.all([
          customersApi.get(id),
          servicesApi.list({ customer_id: id }),
          tasksApi.list({ customer_id: id }),
        ]);
        setCustomer(custRes.data?.data);
        setServices(Array.isArray(svcRes.data?.data) ? svcRes.data.data : []);
        const taskList = Array.isArray(taskRes.data?.data) ? taskRes.data.data : [];
        setTasks(taskList.sort((a: any, b: any) => b.scheduledDate.localeCompare(a.scheduledDate)));
      } catch { Alert.alert('Error', 'Failed to load customer data'); } finally { setLoading(false); }
    };
    load();
  }, [id]);

  if (loading || !customer) {
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
          <Text style={styles.headerTitle}>{customer.name}</Text>
        </View>
      </View>

      <ScrollView contentContainerStyle={styles.scrollContent}>
        {/* Info Card */}
        <View style={[styles.infoCard, SHADOWS.sm]}>
          <Text style={styles.sectionTitle}>Customer Information</Text>
          <View style={styles.infoGrid}>
            <View>
              <Text style={styles.infoLabel}>Name</Text>
              <Text style={styles.infoValue}>{customer.name}</Text>
            </View>
            <View>
              <Text style={styles.infoLabel}>Phone</Text>
              <Text style={styles.infoValue}>{customer.phone}</Text>
            </View>
            <View style={styles.infoFull}>
              <Text style={styles.infoLabel}>Address</Text>
              <Text style={styles.infoValue}>{customer.address}</Text>
            </View>
            <View>
              <Text style={styles.infoLabel}>Area</Text>
              <Text style={styles.infoValue}>{customer.area || '—'}</Text>
            </View>
            <View style={styles.infoFull}>
              <Text style={styles.infoLabel}>Services</Text>
              <View style={styles.chips}>
                {customer.servicesFor.map((s) => <ServiceChip key={s} service={s} />)}
              </View>
            </View>
          </View>
        </View>

        {/* Services */}
        <View style={styles.sectionHeaderRow}>
          <Text style={styles.sectionTitle}>Services</Text>
          <TouchableOpacity
            style={styles.addSvcBtn}
            onPress={() => navigation.navigate('CustomerAdd', { id })}
          >
            <Plus size={14} color={COLORS.white} />
            <Text style={styles.addSvcText}>Add Service</Text>
          </TouchableOpacity>
        </View>

        {services.length === 0 ? (
          <EmptyState title="No services found" />
        ) : (
          services.map((svc) => {
            const latestTask = tasks.filter((t) => t.serviceId === svc.id).sort((a, b) => b.scheduledDate.localeCompare(a.scheduledDate))[0];
            return (
              <View key={svc.id} style={[styles.serviceCard, SHADOWS.sm]}>
                <Text style={styles.serviceTitle}>{svc.title}</Text>
                <View style={styles.serviceMeta}>
                  <StatusBadge status={svc.isRecurring ? 'pending' : 'completed'} />
                  <Text style={styles.serviceDate}>
                    {svc.firstScheduledDate ? formatDate(svc.firstScheduledDate) : '—'}
                  </Text>
                </View>
                {latestTask && (
                  <View style={styles.serviceStatus}>
                    <StatusBadge status={latestTask.status} />
                  </View>
                )}
              </View>
            );
          })
        )}

        {/* Tasks */}
        <Text style={[styles.sectionTitle, { marginTop: SPACING[6], marginBottom: SPACING[3] }]}>Tasks</Text>
        {tasks.length === 0 ? (
          <EmptyState title="No tasks found" />
        ) : (
          tasks.map((task) => (
            <View key={task.id} style={[styles.taskCard, SHADOWS.sm]}>
              <View style={styles.taskRow}>
                {task.status === 'completed' ? (
                  <View style={styles.taskDone}>
                    <CheckIcon size={14} color={COLORS.white} />
                  </View>
                ) : (
                  <Circle size={18} color={COLORS.neutral300} />
                )}
                <View style={styles.taskInfo}>
                  <Text style={styles.taskTitle}>{task.title}</Text>
                  <Text style={styles.taskDate}>{formatDate(task.scheduledDate)}</Text>
                </View>
                <StatusBadge status={task.status} />
              </View>
            </View>
          ))
        )}
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
  headerTitle: { fontSize: FONT_SIZES.lg, fontWeight: '700', color: COLORS.navy, flex: 1 },
  scrollContent: { padding: SPACING[4] },
  infoCard: {
    backgroundColor: COLORS.white, borderRadius: RADIUS.lg, padding: SPACING[5],
    borderWidth: 1, borderColor: COLORS.neutral100, marginBottom: SPACING[6],
  },
  infoGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: SPACING[4] },
  infoFull: { width: '100%' },
  infoLabel: { fontSize: FONT_SIZES.xs, color: COLORS.neutral400, textTransform: 'uppercase', fontWeight: '500', marginBottom: 2 },
  infoValue: { fontSize: FONT_SIZES.sm, color: COLORS.neutral900, fontWeight: '500' },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: 4, marginTop: 4 },
  sectionHeaderRow: {
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center',
    marginBottom: SPACING[3],
  },
  sectionTitle: { fontSize: FONT_SIZES.base, fontWeight: '600', color: COLORS.navy },
  addSvcBtn: {
    flexDirection: 'row', alignItems: 'center', gap: 4,
    paddingHorizontal: 12, paddingVertical: 6, backgroundColor: COLORS.primary,
    borderRadius: RADIUS.md,
  },
  addSvcText: { color: COLORS.white, fontSize: FONT_SIZES.xs, fontWeight: '600' },
  serviceCard: {
    backgroundColor: COLORS.white, borderRadius: RADIUS.lg, padding: SPACING[4],
    borderWidth: 1, borderColor: COLORS.neutral100, marginBottom: SPACING[3],
  },
  serviceTitle: { fontSize: FONT_SIZES.sm, fontWeight: '600', color: COLORS.neutral900, marginBottom: 4 },
  serviceMeta: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  serviceDate: { fontSize: FONT_SIZES.xs, color: COLORS.neutral500 },
  serviceStatus: { marginTop: 6 },
  taskCard: {
    backgroundColor: COLORS.white, borderRadius: RADIUS.lg, padding: SPACING[4],
    borderWidth: 1, borderColor: COLORS.neutral100, marginBottom: SPACING[2],
  },
  taskRow: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  taskDone: {
    width: 20, height: 20, borderRadius: 10, backgroundColor: COLORS.primary,
    alignItems: 'center', justifyContent: 'center',
  },
  taskInfo: { flex: 1 },
  taskTitle: { fontSize: FONT_SIZES.sm, fontWeight: '500', color: COLORS.neutral900 },
  taskDate: { fontSize: FONT_SIZES.xs, color: COLORS.neutral500 },
});
