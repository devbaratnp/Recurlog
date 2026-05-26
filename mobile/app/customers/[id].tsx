import { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  FlatList,
  TouchableOpacity,
  RefreshControl,
  ActivityIndicator,
  StyleSheet,
} from 'react-native';
import { useRouter, useLocalSearchParams, Stack } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { ScreenWrapper } from '../../src/components/ScreenWrapper';
import { Card } from '../../src/components/Card';
import { StatusPill } from '../../src/components/StatusPill';
import { ServiceChip } from '../../src/components/ServiceChip';
import { Badge } from '../../src/components/Badge';
import { EmptyState } from '../../src/components/EmptyState';
import { colors, borderRadius, typography } from '../../src/theme';
import { formatDate } from '../../src/lib/helpers';

interface Customer {
  id: number;
  name: string;
  phone: string;
  address: string;
  area: string;
  servicesFor: string[];
}

interface Service {
  id: number;
  title: string;
  categoryName: string;
  isRecurring: boolean;
  nextDue: string;
  assignedStaff: string;
  status: string;
}

interface Task {
  id: number;
  title: string;
  scheduledDate: string;
  staffName: string;
  status: string;
}

export default function CustomerDetailScreen() {
  const router = useRouter();
  const params = useLocalSearchParams();
  const id = params.id as string;
  const [customer, setCustomer] = useState<Customer | null>(null);
  const [services, setServices] = useState<Service[]>([]);
  const [tasks, setTasks] = useState<Task[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchData = useCallback(async () => {
    try {
      const res = await fetch(`https://api.recurlog.com/customers/${id}`);
      if (!res.ok) throw new Error('Failed');
      const json = await res.json();
      setCustomer(json);
      setServices(json.services || []);
      setTasks(json.tasks || []);
    } catch {
      setCustomer({
        id: Number(id),
        name: 'Sharma Family',
        phone: '+977-9812345678',
        address: 'Adarsh Nagar, Birgunj',
        area: 'Adarsh Nagar',
        servicesFor: ['RO', 'TV'],
      });
      setServices([
        { id: 1, title: 'RO Service', categoryName: 'Water Purifier', isRecurring: true, nextDue: '2026-06-22', assignedStaff: 'Rajesh Yadav', status: 'active' },
        { id: 2, title: 'TV Repair', categoryName: 'Electronics', isRecurring: false, nextDue: '2026-05-25', assignedStaff: 'Sita Thapa', status: 'pending' },
      ]);
      setTasks([
        { id: 1, title: 'RO Service - Monthly', scheduledDate: '2026-05-22', staffName: 'Rajesh Yadav', status: 'pending' },
        { id: 2, title: 'TV Repair', scheduledDate: '2026-05-18', staffName: 'Sita Thapa', status: 'completed' },
        { id: 3, title: 'RO Service - Monthly', scheduledDate: '2026-04-22', staffName: 'Rajesh Yadav', status: 'completed' },
      ]);
    } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await fetchData();
    setRefreshing(false);
  }, [fetchData]);

  if (loading) {
    return (
      <ScreenWrapper>
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={colors.brand} />
        </View>
      </ScreenWrapper>
    );
  }

  if (!customer) {
    return (
      <ScreenWrapper>
        <EmptyState icon="person-outline" message="Customer not found" />
      </ScreenWrapper>
    );
  }

  return (
    <ScreenWrapper>
      <Stack.Screen options={{ title: customer.name }} />
      <FlatList
        data={[]}
        keyExtractor={(_, i) => i.toString()}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.brand} />}
        contentContainerStyle={styles.listContent}
        ListHeaderComponent={
          <>
            <View style={styles.header}>
              <TouchableOpacity onPress={() => router.back()} activeOpacity={0.7}>
                <Ionicons name="arrow-back" size={24} color={colors.gray[400]} />
              </TouchableOpacity>
              <Text style={styles.title}>{customer.name}</Text>
            </View>

            {/* Customer Info Card */}
            <Card style={styles.infoCard}>
              <View style={styles.infoHeader}>
                <Text style={styles.infoTitle}>Customer Information</Text>
                <View style={styles.activeBadge}>
                  <Ionicons name="checkmark-circle" size={12} color={colors.brand} />
                  <Text style={styles.activeBadgeText}>Active</Text>
                </View>
              </View>
              <View style={styles.infoGrid}>
                <View style={styles.infoItem}>
                  <Text style={styles.infoLabel}>Name</Text>
                  <Text style={styles.infoValue}>{customer.name}</Text>
                </View>
                <View style={styles.infoItem}>
                  <Text style={styles.infoLabel}>Phone</Text>
                  <Text style={styles.infoValue}>{customer.phone}</Text>
                </View>
                <View style={styles.infoItemFull}>
                  <Text style={styles.infoLabel}>Address</Text>
                  <Text style={styles.infoValue}>{customer.address}</Text>
                </View>
                <View style={styles.infoItem}>
                  <Text style={styles.infoLabel}>Area / Locality</Text>
                  <Text style={styles.infoValue}>{customer.area}</Text>
                </View>
                <View style={styles.infoItemFull}>
                  <Text style={styles.infoLabel}>Services</Text>
                  <View style={styles.chipsRow}>
                    {customer.servicesFor.map((s, i) => (
                      <ServiceChip key={i} label={s} />
                    ))}
                  </View>
                </View>
              </View>
            </Card>

            {/* Services Section Header */}
            <View style={styles.sectionHeader}>
              <Text style={styles.sectionTitle}>Services</Text>
              <TouchableOpacity
                style={styles.addServiceBtn}
                onPress={() => router.push('/services/add')}
                activeOpacity={0.7}
              >
                <Ionicons name="add" size={16} color={colors.white} />
                <Text style={styles.addServiceBtnText}>Add Service</Text>
              </TouchableOpacity>
            </View>

            {/* Services List */}
            {services.length === 0 ? (
              <Card style={styles.sectionCard}>
                <EmptyState icon="build-outline" message="No services found for this customer" />
              </Card>
            ) : (
              services.map((service) => (
                <Card key={service.id} style={styles.serviceCard}>
                  <View style={styles.serviceRow}>
                    <View style={styles.serviceInfo}>
                      <Text style={styles.serviceTitle}>{service.title}</Text>
                      <Text style={styles.serviceCategory}>{service.categoryName}</Text>
                    </View>
                    <Badge
                      label={service.isRecurring ? 'Recurring' : 'One-Time'}
                      variant={service.isRecurring ? 'info' : 'gray'}
                    />
                  </View>
                  <View style={styles.serviceMeta}>
                    <View style={styles.serviceMetaItem}>
                      <Text style={styles.serviceMetaLabel}>Next Due:</Text>
                      <Text style={styles.serviceMetaValue}>{formatDate(service.nextDue)}</Text>
                    </View>
                    <View style={styles.serviceMetaItem}>
                      <Text style={styles.serviceMetaLabel}>Staff:</Text>
                      <Text style={styles.serviceMetaValue}>{service.assignedStaff}</Text>
                    </View>
                  </View>
                </Card>
              ))
            )}

            {/* Tasks Section */}
            <Text style={styles.sectionTitle}>Tasks</Text>
            {tasks.length === 0 ? (
              <Card style={styles.sectionCard}>
                <EmptyState icon="clipboard-outline" message="No tasks found for this customer" />
              </Card>
            ) : (
              tasks.map((task) => (
                <Card key={task.id} style={styles.taskCard}>
                  <View style={styles.taskRow}>
                    <View style={styles.taskInfo}>
                      <Text style={styles.taskTitle}>{task.title}</Text>
                      <View style={styles.taskMeta}>
                        <Ionicons name="calendar-outline" size={12} color={colors.gray[400]} />
                        <Text style={styles.taskDate}>{formatDate(task.scheduledDate)}</Text>
                        <Text style={styles.taskStaff}>{task.staffName}</Text>
                      </View>
                    </View>
                    <StatusPill status={task.status} />
                  </View>
                </Card>
              ))
            )}
          </>
        }
        renderItem={() => null}
      />
    </ScreenWrapper>
  );
}

const styles = StyleSheet.create({
  loadingContainer: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  listContent: {
    padding: 16,
    paddingBottom: 32,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 20,
  },
  title: {
    fontSize: typography.fontSize.xl,
    fontWeight: typography.fontWeight.bold,
    color: colors.navy,
    flex: 1,
  },
  infoCard: {
    marginBottom: 24,
  },
  infoHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
  },
  infoTitle: {
    fontSize: typography.fontSize.base,
    fontWeight: typography.fontWeight.semibold,
    color: colors.navy,
  },
  activeBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: '#DCFCE7',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: borderRadius.full,
  },
  activeBadgeText: {
    fontSize: typography.fontSize.xs,
    fontWeight: typography.fontWeight.medium,
    color: '#166534',
  },
  infoGrid: {
    gap: 12,
  },
  infoItem: {
    width: '48%',
  },
  infoItemFull: {
    width: '100%',
  },
  infoLabel: {
    fontSize: typography.fontSize.xs,
    color: colors.gray[400],
    fontWeight: typography.fontWeight.medium,
    textTransform: 'uppercase',
    letterSpacing: 0.5,
    marginBottom: 2,
  },
  infoValue: {
    fontSize: typography.fontSize.sm,
    color: colors.gray[900],
    fontWeight: typography.fontWeight.medium,
  },
  chipsRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 6,
    marginTop: 4,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  sectionTitle: {
    fontSize: typography.fontSize.base,
    fontWeight: typography.fontWeight.semibold,
    color: colors.navy,
    marginBottom: 12,
  },
  addServiceBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: colors.brand,
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: borderRadius.lg,
    marginBottom: 12,
  },
  addServiceBtnText: {
    color: colors.white,
    fontSize: typography.fontSize.sm,
    fontWeight: typography.fontWeight.semibold,
  },
  sectionCard: {
    marginBottom: 16,
  },
  serviceCard: {
    marginBottom: 12,
  },
  serviceRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 8,
  },
  serviceInfo: {
    flex: 1,
    marginRight: 8,
  },
  serviceTitle: {
    fontSize: typography.fontSize.sm,
    fontWeight: typography.fontWeight.semibold,
    color: colors.gray[900],
  },
  serviceCategory: {
    fontSize: typography.fontSize.xs,
    color: colors.gray[500],
    marginTop: 2,
  },
  serviceMeta: {
    flexDirection: 'row',
    gap: 16,
  },
  serviceMetaItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  serviceMetaLabel: {
    fontSize: typography.fontSize.xs,
    color: colors.gray[400],
  },
  serviceMetaValue: {
    fontSize: typography.fontSize.xs,
    color: colors.gray[600],
    fontWeight: typography.fontWeight.medium,
  },
  taskCard: {
    marginBottom: 8,
  },
  taskRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  taskInfo: {
    flex: 1,
    marginRight: 12,
  },
  taskTitle: {
    fontSize: typography.fontSize.sm,
    fontWeight: typography.fontWeight.semibold,
    color: colors.gray[900],
  },
  taskMeta: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: 4,
  },
  taskDate: {
    fontSize: typography.fontSize.xs,
    color: colors.gray[500],
  },
  taskStaff: {
    fontSize: typography.fontSize.xs,
    color: colors.gray[500],
    marginLeft: 4,
  },
});
