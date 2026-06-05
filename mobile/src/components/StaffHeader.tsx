import { memo, useMemo } from 'react';
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';
import { Bell } from 'lucide-react-native';
import { COLORS, RADIUS, SPACING, FONT_SIZES } from '../constants/theme';

interface StaffHeaderProps {
  name: string;
  unreadCount: number;
  onNotificationPress: () => void;
}

function getGreeting(): string {
  const h = new Date().getHours();
  if (h < 12) return 'Good Morning';
  if (h < 17) return 'Good Afternoon';
  if (h < 21) return 'Good Evening';
  return 'Hello';
}

function getInitials(name: string): string {
  const parts = name.split(' ');
  return parts.map((p) => p[0]?.toUpperCase()).join('').slice(0, 2);
}

export const StaffHeader = memo(function StaffHeader({ name, unreadCount, onNotificationPress }: StaffHeaderProps) {
  const greeting = useMemo(() => getGreeting(), []);
  const initials = useMemo(() => getInitials(name), [name]);

  return (
    <View style={styles.container}>
      <View style={styles.left}>
        <View style={styles.avatar}>
          <Text style={styles.avatarText}>{initials}</Text>
        </View>
        <View>
          <Text style={styles.greeting}>{greeting}</Text>
          <Text style={styles.name}>{name}</Text>
        </View>
      </View>
      <TouchableOpacity
        style={styles.bellBtn}
        onPress={onNotificationPress}
        accessibilityLabel="Notifications"
        accessibilityRole="button"
      >
        <Bell size={20} color={COLORS.neutral500} />
        {unreadCount > 0 && (
          <View style={styles.badge}>
            <Text style={styles.badgeText}>{unreadCount > 9 ? '9+' : unreadCount}</Text>
          </View>
        )}
      </TouchableOpacity>
    </View>
  );
});

const styles = StyleSheet.create({
  container: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: SPACING[4],
    height: 56,
    backgroundColor: COLORS.white,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.neutral100,
  },
  left: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  avatar: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: COLORS.primary,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: { color: COLORS.white, fontSize: 13, fontWeight: '700' },
  greeting: { fontSize: 12, color: COLORS.neutral500, fontWeight: '500' },
  name: { fontSize: FONT_SIZES.base, fontWeight: '700', color: COLORS.neutral900, marginTop: 1 },
  bellBtn: { position: 'relative', padding: 8, minWidth: 44, minHeight: 44, alignItems: 'center', justifyContent: 'center' },
  badge: {
    position: 'absolute', top: 4, right: 4,
    backgroundColor: COLORS.danger, width: 16, height: 16,
    borderRadius: 8, alignItems: 'center', justifyContent: 'center',
  },
  badgeText: { color: COLORS.white, fontSize: 10, fontWeight: '700' },
});
