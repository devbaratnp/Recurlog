import { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  FlatList,
  TextInput,
  TouchableOpacity,
  RefreshControl,
  ActivityIndicator,
  StyleSheet,
} from 'react-native';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { ScreenWrapper } from '../../src/components/ScreenWrapper';
import { Card } from '../../src/components/Card';
import { ServiceChip } from '../../src/components/ServiceChip';
import { EmptyState } from '../../src/components/EmptyState';
import { Button } from '../../src/components/Button';
import { colors, borderRadius, typography } from '../../src/theme';

interface Customer {
  id: number;
  name: string;
  address: string;
  phone: string;
  servicesFor: string[];
}

export default function CustomersScreen() {
  const router = useRouter();
  const [customers, setCustomers] = useState<Customer[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [search, setSearch] = useState('');

  const fetchCustomers = useCallback(async () => {
    try {
      const res = await fetch('https://api.recurlog.com/customers');
      if (!res.ok) throw new Error('Failed');
      const json = await res.json();
      setCustomers(json);
    } catch {
      setCustomers([
        { id: 1, name: 'Sharma Family', address: 'Adarsh Nagar, Birgunj', phone: '+977-9812345678', servicesFor: ['RO', 'TV'] },
        { id: 2, name: 'Gupta Traders', address: 'Ghantaghar, Birgunj', phone: '+977-9845678901', servicesFor: ['AC', 'Refrigerator'] },
        { id: 3, name: 'Mehta & Co', address: 'Station Road, Birgunj', phone: '+977-9876543210', servicesFor: ['TV'] },
        { id: 4, name: 'Patel Residence', address: 'Ranjit Nagar, Birgunj', phone: '+977-9865432109', servicesFor: ['Washing Machine', 'RO'] },
        { id: 5, name: 'Thapa Family', address: 'Milan Chowk, Birgunj', phone: '+977-9854321098', servicesFor: ['Refrigerator'] },
        { id: 6, name: 'Kumar Electronics', address: 'Bara Road, Birgunj', phone: '+977-9843210987', servicesFor: ['AC', 'TV', 'RO'] },
        { id: 7, name: 'Verma Household', address: 'Bank Road, Birgunj', phone: '+977-9832109876', servicesFor: ['Other'] },
        { id: 8, name: 'Singh Residence', address: 'Jhanda Chowk, Birgunj', phone: '+977-9821098765', servicesFor: ['Washing Machine'] },
      ]);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchCustomers();
  }, [fetchCustomers]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await fetchCustomers();
    setRefreshing(false);
  }, [fetchCustomers]);

  const filtered = customers.filter((c) =>
    c.name.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <ScreenWrapper>
      <FlatList
        data={filtered}
        keyExtractor={(item) => item.id.toString()}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.brand} />}
        contentContainerStyle={styles.listContent}
        ListHeaderComponent={
          <>
            <View style={styles.header}>
              <Text style={styles.title}>Customers</Text>
              <TouchableOpacity
                style={styles.addButton}
                onPress={() => router.push('/customers/add')}
                activeOpacity={0.7}
              >
                <Ionicons name="add" size={18} color={colors.white} />
                <Text style={styles.addButtonText}>Add</Text>
              </TouchableOpacity>
            </View>
            <Text style={styles.subtitle}>Manage your customer accounts and service history</Text>
            <View style={styles.searchContainer}>
              <Ionicons name="search" size={18} color={colors.gray[400]} style={styles.searchIcon} />
              <TextInput
                style={styles.searchInput}
                placeholder="Search customers by name..."
                placeholderTextColor={colors.gray[400]}
                value={search}
                onChangeText={setSearch}
              />
            </View>
          </>
        }
        ListEmptyComponent={
          loading ? (
            <ActivityIndicator size="large" color={colors.brand} style={{ marginTop: 48 }} />
          ) : (
            <EmptyState
              icon="people-outline"
              title="No customers found"
              message={search ? 'Try adjusting your search.' : 'Add your first customer to get started.'}
              actionLabel={!search ? 'Add First Customer' : undefined}
              onAction={!search ? () => router.push('/customers/add') : undefined}
            />
          )
        }
        renderItem={({ item }) => (
          <Card style={styles.customerCard}>
            <View style={styles.customerInfo}>
              <Text style={styles.customerName}>{item.name}</Text>
              <View style={styles.customerDetail}>
                <Ionicons name="location-outline" size={14} color={colors.gray[400]} />
                <Text style={styles.customerDetailText}>{item.address}</Text>
              </View>
              <View style={styles.customerDetail}>
                <Ionicons name="call-outline" size={14} color={colors.gray[400]} />
                <Text style={styles.customerDetailText}>{item.phone}</Text>
              </View>
              <View style={styles.chipsRow}>
                {item.servicesFor.map((s, i) => (
                  <ServiceChip key={i} label={s} />
                ))}
              </View>
            </View>
            <View style={styles.customerActions}>
              <TouchableOpacity
                style={styles.viewButton}
                onPress={() => router.push(`/customers/${item.id}`)}
                activeOpacity={0.7}
              >
                <Text style={styles.viewButtonText}>View</Text>
              </TouchableOpacity>
            </View>
          </Card>
        )}
      />
    </ScreenWrapper>
  );
}

const styles = StyleSheet.create({
  listContent: {
    padding: 16,
    paddingBottom: 32,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 4,
  },
  title: {
    fontSize: typography.fontSize.xl,
    fontWeight: typography.fontWeight.bold,
    color: colors.navy,
  },
  addButton: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: colors.brand,
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: borderRadius.lg,
  },
  addButtonText: {
    color: colors.white,
    fontSize: typography.fontSize.sm,
    fontWeight: typography.fontWeight.semibold,
  },
  subtitle: {
    fontSize: typography.fontSize.sm,
    color: colors.gray[500],
    marginBottom: 16,
    marginTop: 4,
  },
  searchContainer: {
    position: 'relative',
    marginBottom: 16,
  },
  searchIcon: {
    position: 'absolute',
    left: 14,
    top: 14,
    zIndex: 1,
  },
  searchInput: {
    borderWidth: 1,
    borderColor: colors.gray[200],
    borderRadius: borderRadius.lg,
    paddingLeft: 42,
    paddingRight: 14,
    paddingVertical: 12,
    fontSize: typography.fontSize.sm,
    color: colors.gray[900],
    backgroundColor: colors.white,
  },
  customerCard: {
    marginBottom: 12,
    flexDirection: 'row',
  },
  customerInfo: {
    flex: 1,
  },
  customerName: {
    fontSize: typography.fontSize.base,
    fontWeight: typography.fontWeight.semibold,
    color: colors.navy,
    marginBottom: 8,
  },
  customerDetail: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginBottom: 4,
  },
  customerDetailText: {
    fontSize: typography.fontSize.sm,
    color: colors.gray[600],
  },
  chipsRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 6,
    marginTop: 8,
  },
  customerActions: {
    justifyContent: 'center',
    paddingLeft: 12,
  },
  viewButton: {
    backgroundColor: colors.brand,
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: borderRadius.md,
  },
  viewButtonText: {
    color: colors.white,
    fontSize: typography.fontSize.xs,
    fontWeight: typography.fontWeight.semibold,
  },
});
