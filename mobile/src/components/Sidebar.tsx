import { useEffect, useCallback } from 'react';
import {
  View, Text, ScrollView, TouchableOpacity, StyleSheet, Animated, Dimensions,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import {
  LayoutDashboard, Users, ClipboardList, CalendarCheck, Repeat, Briefcase,
  BookOpen, BarChart3, Bell, Settings, Wrench, LogOut, X, PanelLeftClose,
} from 'lucide-react-native';
import { useNotificationStore } from '../store/notificationStore';
import { useAuthStore } from '../store/authStore';
import { COLORS, RADIUS, SPACING, FONT_SIZES } from '../constants/theme';

const ADMIN_NAV_ITEMS: { icon: any; label: string; screen: string }[] = [
  { icon: LayoutDashboard, label: 'Dashboard', screen: 'Dashboard' },
  { icon: Users, label: 'Customer', screen: 'CustomerList' },
  { icon: ClipboardList, label: 'Order', screen: 'OrderList' },
  { icon: CalendarCheck, label: 'Onetime Task', screen: 'TaskList' },
  { icon: Repeat, label: 'Recurring Task', screen: 'RecurringTaskList' },
  { icon: Briefcase, label: 'Staff', screen: 'StaffList' },
  { icon: BookOpen, label: 'Daybook', screen: 'Daybook' },
  { icon: BarChart3, label: 'Report', screen: 'Reports' },
  { icon: Bell, label: 'Notification', screen: 'Notifications' },
  { icon: Settings, label: 'Setting', screen: 'Settings' },
];

const STAFF_NAV_ITEMS: { icon: any; label: string; screen: string }[] = [
  { icon: LayoutDashboard, label: 'Dashboard', screen: 'Dashboard' },
  { icon: Briefcase, label: 'My Tasks', screen: 'TaskList' },
  { icon: ClipboardList, label: 'Orders', screen: 'OrderList' },
  { icon: BookOpen, label: 'Daybook', screen: 'Daybook' },
  { icon: Bell, label: 'Notification', screen: 'Notifications' },
];

const DRAWER_WIDTH = 280;
const SCREEN_WIDTH = Dimensions.get('window').width;

interface SidebarProps {
  visible: boolean;
  currentScreen: string;
  onNavigate: (screen: string) => void;
  onClose: () => void;
}

function getInitials(name: string): string {
  const parts = name.split(' ');
  let initials = '';
  for (const p of parts) { if (p[0]) initials += p[0].toUpperCase(); }
  return initials.slice(0, 2);
}

export function Sidebar({ visible, currentScreen, onNavigate, onClose }: SidebarProps) {
  const insets = useSafeAreaInsets();
  const anim = new Animated.Value(visible ? 1 : 0);
  const { unreadCount, fetchNotifications } = useNotificationStore();
  const { user, logout } = useAuthStore();

  const navigateAndClose = useCallback((screen: string) => {
    onNavigate(screen);
    onClose();
  }, [onNavigate, onClose]);

  useEffect(() => {
    Animated.timing(anim, {
      toValue: visible ? 1 : 0,
      duration: 250,
      useNativeDriver: true,
    }).start();
  }, [visible]);

  useEffect(() => {
    fetchNotifications();
  }, []);

  const isStaff = user?.role === 'staff';
  const navItems = isStaff ? STAFF_NAV_ITEMS : ADMIN_NAV_ITEMS;

  const backdropOpacity = anim.interpolate({ inputRange: [0, 1], outputRange: [0, 0.5] });
  const translateX = anim.interpolate({ inputRange: [0, 1], outputRange: [-DRAWER_WIDTH, 0] });

  if (!visible) return null;

  return (
    <View style={StyleSheet.absoluteFill} pointerEvents="box-none">
      <Animated.View
        style={[styles.backdrop, { opacity: backdropOpacity }]}
        pointerEvents="auto"
      >
        <TouchableOpacity
          style={StyleSheet.absoluteFill}
          activeOpacity={1}
          onPress={onClose}
        />
      </Animated.View>

      <Animated.View
        style={[
          styles.drawer,
          {
            width: DRAWER_WIDTH,
            transform: [{ translateX }],
            paddingTop: insets.top,
          },
        ]}
      >
        {/* Brand Header */}
        <View style={styles.brandHeader}>
          <TouchableOpacity
            style={styles.brandLink}
            onPress={() => navigateAndClose('Dashboard')}
          >
            <View style={styles.brandIcon}>
              <Wrench size={20} color={COLORS.white} />
            </View>
            <Text style={styles.brandName}>Recurlog</Text>
          </TouchableOpacity>
          <View style={styles.headerButtons}>
            <TouchableOpacity onPress={onClose} style={styles.closeBtn}>
              <PanelLeftClose size={16} color="rgba(255,255,255,0.6)" />
            </TouchableOpacity>
            <TouchableOpacity onPress={onClose} style={styles.closeBtn}>
              <X size={20} color="rgba(255,255,255,0.6)" />
            </TouchableOpacity>
          </View>
        </View>

        {/* Nav Items */}
        <ScrollView style={styles.navScroll} showsVerticalScrollIndicator={false}>
          {navItems.map((item) => {
            const isActive = currentScreen === item.screen;
            const Icon = item.icon;
            return (
              <TouchableOpacity
                key={item.screen}
                style={[styles.navLink, isActive && styles.navLinkActive]}
                onPress={() => navigateAndClose(item.screen)}
              >
                <Icon
                  size={18}
                  color={isActive ? COLORS.primary : 'rgba(255,255,255,0.65)'}
                />
                <Text
                  style={[
                    styles.navLinkText,
                    isActive && styles.navLinkTextActive,
                  ]}
                >
                  {item.label}
                </Text>
                {item.label === 'Notification' && unreadCount > 0 && (
                  <View style={styles.badge}>
                    <Text style={styles.badgeText}>
                      {unreadCount > 99 ? '99+' : unreadCount}
                    </Text>
                  </View>
                )}
              </TouchableOpacity>
            );
          })}
        </ScrollView>

        {/* User Profile + Logout */}
        <View style={[styles.userSection, { paddingBottom: Math.max(insets.bottom, 12) }]}>
          <View style={styles.userRow}>
            <View style={styles.avatar}>
              <Text style={styles.avatarText}>
                {user ? getInitials(user.name) : 'A'}
              </Text>
            </View>
            <View style={styles.userInfo}>
              <Text style={styles.userName} numberOfLines={1}>
                {user?.name || 'Admin User'}
              </Text>
              <Text style={styles.userRole}>
                {user?.role ? user.role.charAt(0).toUpperCase() + user.role.slice(1) : 'Admin'}
              </Text>
            </View>
          </View>
          <TouchableOpacity
            style={styles.logoutBtn}
            onPress={async () => {
              await logout();
              onClose();
            }}
          >
            <LogOut size={16} color="rgba(255,255,255,0.5)" />
            <Text style={styles.logoutText}>Logout</Text>
          </TouchableOpacity>
        </View>
      </Animated.View>
    </View>
  );
}

const styles = StyleSheet.create({
  backdrop: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: COLORS.black,
  },
  drawer: {
    position: 'absolute',
    top: 0,
    left: 0,
    bottom: 0,
    backgroundColor: COLORS.navy,
    zIndex: 10,
    elevation: 20,
  },
  brandHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: SPACING[5],
    paddingVertical: 16,
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(255,255,255,0.1)',
  },
  brandLink: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  brandIcon: {
    width: 36,
    height: 36,
    borderRadius: RADIUS.xl,
    backgroundColor: COLORS.primary,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: COLORS.primary,
    shadowOpacity: 0.4,
    shadowRadius: 8,
    elevation: 4,
  },
  brandName: {
    fontSize: FONT_SIZES.lg,
    fontWeight: '800',
    color: COLORS.white,
    letterSpacing: -0.5,
  },
  headerButtons: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  closeBtn: {
    padding: 6,
  },
  navScroll: {
    flex: 1,
    paddingVertical: 8,
  },
  navLink: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    paddingVertical: 12,
    paddingHorizontal: SPACING[5],
    marginHorizontal: 8,
    marginVertical: 1,
    borderRadius: RADIUS.md,
  },
  navLinkActive: {
    backgroundColor: 'rgba(29,185,84,0.12)',
  },
  navLinkText: {
    fontSize: FONT_SIZES.sm,
    fontWeight: '500',
    color: 'rgba(255,255,255,0.65)',
    flex: 1,
  },
  navLinkTextActive: {
    color: COLORS.primary,
    fontWeight: '600',
  },
  badge: {
    backgroundColor: COLORS.danger,
    minWidth: 20,
    height: 20,
    borderRadius: RADIUS.full,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 5,
  },
  badgeText: {
    color: COLORS.white,
    fontSize: 10,
    fontWeight: '700',
  },
  userSection: {
    padding: SPACING[4],
    borderTopWidth: 1,
    borderTopColor: 'rgba(255,255,255,0.1)',
  },
  userRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  avatar: {
    width: 32,
    height: 32,
    borderRadius: RADIUS.full,
    backgroundColor: COLORS.primary,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: {
    color: COLORS.white,
    fontSize: 11,
    fontWeight: '700',
  },
  userInfo: {
    flex: 1,
  },
  userName: {
    fontSize: FONT_SIZES.sm,
    fontWeight: '600',
    color: COLORS.white,
  },
  userRole: {
    fontSize: 11,
    color: 'rgba(255,255,255,0.4)',
  },
  logoutBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingVertical: 10,
    marginTop: 4,
  },
  logoutText: {
    fontSize: FONT_SIZES.sm,
    fontWeight: '500',
    color: 'rgba(255,255,255,0.5)',
  },
});
