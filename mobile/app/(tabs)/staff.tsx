import { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  FlatList,
  RefreshControl,
  ActivityIndicator,
  TouchableOpacity,
  StyleSheet,
  Dimensions,
} from 'react-native';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { ScreenWrapper } from '../../src/components/ScreenWrapper';
import { Card } from '../../src/components/Card';
import { EmptyState } from '../../src/components/EmptyState';
import { colors, borderRadius, typography } from '../../src/theme';

interface StaffMember {
  id: number;
  name: string;
  phone: string;
  avatar: string;
  activeTasks: number;
  completed: number;
  missed: number;
  completionRate: number;
}

export default function StaffScreen() {
  const router = useRouter();
  const [staff, setStaff] = useState<StaffMember[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const screenWidth = Dimensions.get('window').width;
  const numColumns = screenWidth > 500 ? 2 : 1;

  const fetchStaff = useCallback(async () => {
    try {
      const res = await fetch('https://api.recurlog.com/staff');
      if (!res.ok) throw new Error('Failed');
      const json = await res.json();
      setStaff(json);
    } catch {
      setStaff([
        { id: 1, name: 'Rajesh Yadav', phone: '+977-9812345678', avatar: 'https://ui-avatars.com/api/?name=Rajesh+Yadav&background=1DB954&color=fff&size=128', activeTasks: 8, completed: 42, missed: 2, completionRate: 95 },
        { id: 2, name: 'Sita Thapa', phone: '+977-9845678901', avatar: 'https://ui-avatars.com/api/?name=Sita+Thapa&background=F59E0B&color=fff&size=128', activeTasks: 6, completed: 35, missed: 1, completionRate: 97 },
        { id: 3, name: 'Bhim Singh', phone: '+977-9876543210', avatar: 'https://ui-avatars.com/api/?name=Bhim+Singh&background=EF4444&color=fff&size=128', activeTasks: 5, completed: 28, missed: 4, completionRate: 87 },
        { id: 4, name: 'Gita Sharma', phone: '+977-9865432109', avatar: 'https://ui-avatars.com/api/?name=Gita+Sharma&background=3B82F6&color=fff&size=128', activeTasks: 4, completed: 22, missed: 0, completionRate: 100 },
        { id: 5, name: 'Mohan Gupta', phone: '+977-9854321098', avatar: 'https://ui-avatars.com/api/?name=Mohan+Gupta&background=8B5CF6&color=fff&size=128', activeTasks: 7, completed: 31, missed: 3, completionRate: 91 },
        { id: 6, name: 'Laxmi Devi', phone: '+977-9843210987', avatar: 'https://ui-avatars.com/api/?name=Laxmi+Devi&background=EC4899&color=fff&size=128', activeTasks: 3, completed: 18, missed: 1, completionRate: 95 },
        { id: 7, name: 'Krishna Rai', phone: '+977-9832109876', avatar: 'https://ui-avatars.com/api/?name=Krishna+Rai&background=14B8A6&color=fff&size=128', activeTasks: 5, completed: 24, missed: 2, completionRate: 92 },
        { id: 8, name: 'Prakash KC', phone: '+977-9821098765', avatar: 'https://ui-avatars.com/api/?name=Prakash+KC&background=6366F1&color=fff&size=128', activeTasks: 6, completed: 29, missed: 0, completionRate: 100 },
      ]);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchStaff();
  }, [fetchStaff]);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await fetchStaff();
    setRefreshing(false);
  }, [fetchStaff]);

  return (
    <ScreenWrapper>
      <FlatList
        data={staff}
        keyExtractor={(item) => item.id.toString()}
        numColumns={numColumns}
        key={`cols-${numColumns}`}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.brand} />}
        contentContainerStyle={styles.listContent}
        columnWrapperStyle={numColumns > 1 ? styles.columnWrapper : undefined}
        ListHeaderComponent={
          <Text style={styles.title}>Staff</Text>
        }
        ListEmptyComponent={
          loading ? (
            <ActivityIndicator size="large" color={colors.brand} style={{ marginTop: 48 }} />
          ) : (
            <EmptyState icon="people-outline" message="No staff members found" />
          )
        }
        renderItem={({ item }) => (
          <Card style={[styles.staffCard, numColumns > 1 ? { width: '100%' } : undefined]}>
            <View style={styles.staffHeader}>
              <View style={styles.avatar}>
                <Text style={styles.avatarText}>
                  {item.name.split(' ').map(n => n[0]).join('')}
                </Text>
              </View>
              <View style={styles.staffInfo}>
                <Text style={styles.staffName}>{item.name}</Text>
                <Text style={styles.staffPhone}>{item.phone}</Text>
              </View>
            </View>
            <View style={styles.statsRow}>
              <View style={styles.stat}>
                <Ionicons name="clipboard-outline" size={14} color={colors.gray[400]} />
                <Text style={styles.statValue}>{item.activeTasks}</Text>
                <Text style={styles.statLabel}>Active</Text>
              </View>
              <View style={styles.stat}>
                <Ionicons name="checkmark-circle" size={14} color={colors.brand} />
                <Text style={styles.statValue}>{item.completed}</Text>
                <Text style={styles.statLabel}>Done</Text>
              </View>
              <View style={styles.stat}>
                <Ionicons name="alert-circle" size={14} color={colors.danger} />
                <Text style={styles.statValue}>{item.missed}</Text>
                <Text style={styles.statLabel}>Missed</Text>
              </View>
            </View>
            <View style={styles.progressContainer}>
              <View style={styles.progressBg}>
                <View style={[styles.progressFill, { width: `${item.completionRate}%` }]} />
              </View>
              <Text style={styles.progressText}>{item.completionRate}%</Text>
            </View>
            <TouchableOpacity
              style={styles.profileLink}
              onPress={() => router.push(`/staff/${item.id}`)}
              activeOpacity={0.7}
            >
              <Text style={styles.profileLinkText}>View Profile</Text>
              <Ionicons name="chevron-forward" size={14} color={colors.brand} />
            </TouchableOpacity>
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
  columnWrapper: {
    gap: 12,
  },
  title: {
    fontSize: typography.fontSize.xl,
    fontWeight: typography.fontWeight.bold,
    color: colors.navy,
    marginBottom: 16,
  },
  staffCard: {
    marginBottom: 12,
    flex: 1,
  },
  staffHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 12,
  },
  avatar: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: colors.brand,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: {
    fontSize: typography.fontSize.base,
    fontWeight: typography.fontWeight.bold,
    color: colors.white,
  },
  staffInfo: {
    flex: 1,
  },
  staffName: {
    fontSize: typography.fontSize.base,
    fontWeight: typography.fontWeight.semibold,
    color: colors.navy,
  },
  staffPhone: {
    fontSize: typography.fontSize.sm,
    color: colors.gray[500],
    marginTop: 2,
  },
  statsRow: {
    flexDirection: 'row',
    gap: 16,
    marginBottom: 12,
  },
  stat: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  statValue: {
    fontSize: typography.fontSize.sm,
    fontWeight: typography.fontWeight.semibold,
    color: colors.navy,
  },
  statLabel: {
    fontSize: typography.fontSize.xs,
    color: colors.gray[500],
  },
  progressContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginBottom: 8,
  },
  progressBg: {
    flex: 1,
    height: 8,
    backgroundColor: colors.gray[100],
    borderRadius: 4,
    overflow: 'hidden',
  },
  progressFill: {
    height: 8,
    backgroundColor: colors.brand,
    borderRadius: 4,
  },
  progressText: {
    fontSize: typography.fontSize.xs,
    fontWeight: typography.fontWeight.semibold,
    color: colors.navy,
  },
  profileLink: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  profileLinkText: {
    fontSize: typography.fontSize.sm,
    fontWeight: typography.fontWeight.medium,
    color: colors.brand,
  },
});
