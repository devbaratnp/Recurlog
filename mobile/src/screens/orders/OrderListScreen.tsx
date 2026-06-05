import { useEffect, useState, useCallback, useMemo } from 'react';
import { View, Text, FlatList, TouchableOpacity, RefreshControl, StyleSheet, Alert } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation } from '@react-navigation/native';
import { ArrowLeft, Calendar, User, Plus } from 'lucide-react-native';
import { COLORS, RADIUS, SPACING, FONT_SIZES, SHADOWS } from '../../constants/theme';
import { formatRelative } from '../../utils/date';
import type { Order } from '../../types';
import { ordersApi } from '../../api/client';
import { useAuthStore } from '../../store/authStore';
import { StatusBadge } from '../../components/StatusBadge';
import { PriorityBadge } from '../../components/PriorityBadge';
import { SearchBar } from '../../components/SearchBar';
import { EmptyState } from '../../components/EmptyState';

export function OrderListScreen() {
  const navigation = useNavigation<any>();
  const insets = useSafeAreaInsets();
  const user = useAuthStore((s) => s.user);
  const isStaff = user?.role === 'staff';
  const [orders, setOrders] = useState<Order[]>([]);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const fetchOrders = useCallback(async () => {
    try {
      const params: any = {};
      if (isStaff && user?.staffId) params.assigned_to = String(user.staffId);
      const { data } = await ordersApi.list(params);
      const list = Array.isArray(data?.data) ? data.data : [];
      setOrders(list);
    } catch { Alert.alert('Error', 'Failed to load orders'); } finally { setLoading(false); }
  }, [isStaff, user?.staffId]);

  useEffect(() => { fetchOrders(); }, [fetchOrders]);

  const filtered = useMemo(() => {
    const q = search.toLowerCase().trim();
    if (!q) return orders;
    return orders.filter((o) => o.customerName.toLowerCase().includes(q) || o.serviceFor.toLowerCase().includes(q));
  }, [search, orders]);

  const onRefresh = async () => { setRefreshing(true); await fetchOrders(); setRefreshing(false); };

  const renderOrder = ({ item }: { item: Order }) => (
    <TouchableOpacity style={[styles.card, SHADOWS.sm]} onPress={() => navigation.navigate('OrderDetail', { id: item.id })}>
      <View style={styles.cardRow}>
        <Text style={styles.customerName}>{item.customerName}</Text>
        <PriorityBadge priority={item.priority} />
      </View>
      <Text style={styles.serviceFor}>{item.serviceFor}</Text>
      <Text style={styles.problem} numberOfLines={2}>{item.problem}</Text>
      <View style={styles.cardMeta}>
        <View style={styles.metaItem}>
          <Calendar size={12} color={COLORS.neutral500} />
          <Text style={styles.metaText}>{item.scheduledDate ? formatRelative(item.scheduledDate) : '—'}</Text>
        </View>
        {item.assignedStaffName && (
          <View style={styles.metaItem}>
            <User size={12} color={COLORS.neutral500} />
            <Text style={styles.metaText}>{item.assignedStaffName}</Text>
          </View>
        )}
      </View>
      <StatusBadge status={item.status} />
    </TouchableOpacity>
  );

  return (
    <View style={styles.container}>
      <View style={[styles.header, { paddingTop: insets.top, minHeight: 56 + insets.top }]}>
        <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
          <ArrowLeft size={20} color={COLORS.neutral600} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Orders</Text>
      </View>

      <FlatList
        data={filtered}
        keyExtractor={(item) => String(item.id)}
        renderItem={renderOrder}
        contentContainerStyle={styles.list}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={COLORS.primary} />}
        ListHeaderComponent={
          <View style={styles.searchContainer}>
            <SearchBar value={search} onChangeText={setSearch} placeholder="Search orders..." />
          </View>
        }
        ListEmptyComponent={!loading ? <EmptyState title="No orders found" /> : null}
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
    flexDirection: 'row', alignItems: 'center', paddingHorizontal: SPACING[4],
    backgroundColor: COLORS.white, borderBottomWidth: 1, borderBottomColor: COLORS.neutral200,
  },
  backBtn: { padding: 8, minWidth: 44, minHeight: 44, justifyContent: 'center' },
  headerTitle: { fontSize: FONT_SIZES.lg, fontWeight: '700', color: COLORS.navy, marginLeft: 4 },
  searchContainer: { paddingHorizontal: SPACING[4], paddingTop: SPACING[4], paddingBottom: SPACING[3] },
  list: { padding: SPACING[4], paddingBottom: 80 },
  card: {
    backgroundColor: COLORS.white, borderRadius: RADIUS.lg, padding: SPACING[4],
    borderWidth: 1, borderColor: COLORS.neutral100, marginBottom: SPACING[3],
  },
  cardRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  customerName: { fontSize: FONT_SIZES.sm, fontWeight: '600', color: COLORS.neutral900 },
  serviceFor: { fontSize: FONT_SIZES.xs, color: COLORS.primary, fontWeight: '500', marginTop: 2 },
  problem: { fontSize: FONT_SIZES.sm, color: COLORS.neutral600, marginTop: 4, lineHeight: 18 },
  cardMeta: { flexDirection: 'row', gap: 12, marginTop: 6 },
  metaItem: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  metaText: { fontSize: FONT_SIZES.xs, color: COLORS.neutral500 },
});
