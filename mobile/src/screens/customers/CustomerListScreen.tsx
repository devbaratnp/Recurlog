import { useEffect, useState, useCallback, useMemo } from 'react';
import { View, Text, FlatList, TouchableOpacity, RefreshControl, StyleSheet, Alert } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import { Plus, ArrowLeft } from 'lucide-react-native';
import { customersApi } from '../../api/client';
import { SearchBar } from '../../components/SearchBar';
import { ServiceChip } from '../../components/ServiceChip';
import { EmptyState } from '../../components/EmptyState';
import { COLORS, RADIUS, SPACING, FONT_SIZES, SHADOWS } from '../../constants/theme';
import type { Customer } from '../../types';

export function CustomerListScreen() {
  const navigation = useNavigation<any>();
  const insets = useSafeAreaInsets();
  const [customers, setCustomers] = useState<Customer[]>([]);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchData = useCallback(async () => {
    try {
      const { data } = await customersApi.list();
      const list = Array.isArray(data?.data) ? data.data : [];
      setCustomers(list);
    } catch { Alert.alert('Error', 'Failed to load customers'); } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { fetchData(); }, []);

  const filtered = useMemo(() => {
    const q = search.toLowerCase().trim();
    if (!q) return customers;
    return customers.filter((c) => c.name.toLowerCase().includes(q));
  }, [search, customers]);

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchData();
    setRefreshing(false);
  };

  const renderItem = ({ item }: { item: Customer }) => (
    <TouchableOpacity
      style={[styles.card, SHADOWS.sm]}
      onPress={() => navigation.navigate('DashboardTab', { screen: 'CustomerDetail', params: { id: item.id } })}
    >
      <View style={styles.cardHeader}>
        <Text style={styles.cardName}>{item.name}</Text>
        <TouchableOpacity
          style={styles.editBtn}
          onPress={() => navigation.navigate('DashboardTab', { screen: 'CustomerAdd', params: { id: item.id } })}
        >
          <Text style={styles.editBtnText}>Edit</Text>
        </TouchableOpacity>
      </View>
      <Text style={styles.cardText}>{item.address}</Text>
      {item.area ? <Text style={styles.cardText}>{item.area}</Text> : null}
      <Text style={styles.cardText}>{item.phone}</Text>
      {item.servicesFor.length > 0 && (
        <View style={styles.chips}>
          {item.servicesFor.map((s) => <ServiceChip key={s} service={s} />)}
        </View>
      )}
      <View style={styles.cardAction}>
        <Text style={styles.viewLink}>View Details →</Text>
      </View>
    </TouchableOpacity>
  );

  return (
    <View style={styles.container}>
      <View style={[styles.header, { paddingTop: insets.top, minHeight: 56 + insets.top }]}>
        <View style={styles.headerLeft}>
          <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
            <ArrowLeft size={20} color={COLORS.neutral600} />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Customers</Text>
        </View>
        <TouchableOpacity onPress={() => navigation.navigate('DashboardTab', { screen: 'CustomerAdd' })} style={styles.addBtn}>
          <Plus size={16} color={COLORS.white} />
          <Text style={styles.addBtnText}>Add</Text>
        </TouchableOpacity>
      </View>

      <FlatList
        contentContainerStyle={styles.list}
        data={filtered}
        keyExtractor={(item) => String(item.id)}
        renderItem={renderItem}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={COLORS.primary} />}
        ListHeaderComponent={
          <View style={styles.searchContainer}>
            <SearchBar value={search} onChangeText={setSearch} placeholder="Search customers..." />
          </View>
        }
        ListEmptyComponent={!loading ? <EmptyState title="No customers found" subtitle="Try adjusting your search" /> : null}
        windowSize={10}
        removeClippedSubviews
        maxToRenderPerBatch={10}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: COLORS.neutral50 },
  header: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    paddingHorizontal: SPACING[4], backgroundColor: COLORS.white,
    borderBottomWidth: 1, borderBottomColor: COLORS.neutral200,
  },
  headerLeft: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  backBtn: { padding: 8, minWidth: 44, minHeight: 44, justifyContent: 'center' },
  headerTitle: { fontSize: FONT_SIZES.lg, fontWeight: '700', color: COLORS.navy },
  addBtn: {
    flexDirection: 'row', alignItems: 'center', gap: 6,
    paddingHorizontal: 16, paddingVertical: 8,
    backgroundColor: COLORS.primary, borderRadius: RADIUS.md,
    minHeight: 36,
  },
  addBtnText: { color: COLORS.white, fontSize: FONT_SIZES.xs, fontWeight: '600' },
  searchContainer: { paddingHorizontal: SPACING[4], paddingTop: SPACING[4], paddingBottom: SPACING[3] },
  list: { paddingBottom: 80 },
  card: {
    backgroundColor: COLORS.white, marginHorizontal: SPACING[4], marginBottom: SPACING[3],
    borderRadius: RADIUS.lg, padding: SPACING[4], borderWidth: 1, borderColor: COLORS.neutral100,
  },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 6 },
  cardName: { fontSize: FONT_SIZES.base, fontWeight: '600', color: COLORS.neutral900 },
  editBtn: { paddingHorizontal: 12, paddingVertical: 4, borderRadius: RADIUS.md, backgroundColor: COLORS.neutral100 },
  editBtnText: { fontSize: FONT_SIZES.xs, color: COLORS.neutral600, fontWeight: '500' },
  cardText: { fontSize: FONT_SIZES.sm, color: COLORS.neutral600, lineHeight: 20 },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: 4, marginTop: 8 },
  cardAction: { marginTop: 10, borderTopWidth: 1, borderTopColor: COLORS.neutral100, paddingTop: 8 },
  viewLink: { color: COLORS.primary, fontSize: FONT_SIZES.sm, fontWeight: '500' },
});
