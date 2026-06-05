import { View, Text, ScrollView, TouchableOpacity, StyleSheet, Alert } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import { ArrowLeft, LogOut, Bell, Globe, Monitor, Download, Trash2 } from 'lucide-react-native';
import { useAuthStore } from '../../store/authStore';
import { COLORS, RADIUS, SPACING, FONT_SIZES, SHADOWS } from '../../constants/theme';

function SettingsRow({ icon, title, subtitle, children }: { icon: React.ReactNode; title: string; subtitle: string; children?: React.ReactNode }) {
  return (
    <View style={styles.row}>
      <View style={styles.rowLeft}>
        <View style={styles.iconContainer}>{icon}</View>
        <View>
          <Text style={styles.rowTitle}>{title}</Text>
          <Text style={styles.rowSubtitle}>{subtitle}</Text>
        </View>
      </View>
      {children}
    </View>
  );
}

export function SettingsScreen() {
  const navigation = useNavigation<any>();
  const { user, logout } = useAuthStore();

  const handleLogout = () => {
    Alert.alert('Logout', 'Are you sure you want to logout?', [
      { text: 'Cancel', style: 'cancel' },
      { text: 'Logout', style: 'destructive', onPress: logout },
    ]);
  };

  const userInitials = user?.name?.split(' ').map((p) => p[0]).join('').toUpperCase().slice(0, 2) || 'AD';

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <View style={styles.headerLeft}>
          <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
            <ArrowLeft size={20} color={COLORS.neutral600} />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Settings</Text>
        </View>
      </View>

      <ScrollView contentContainerStyle={styles.scroll}>
        <View style={[styles.profileCard, SHADOWS.sm]}>
          <View style={styles.avatarLarge}>
            <Text style={styles.avatarText}>{userInitials}</Text>
          </View>
          <Text style={styles.profileName}>{user?.name || 'Admin User'}</Text>
          <Text style={styles.profileEmail}>{user?.email}</Text>
          <TouchableOpacity style={styles.editBtn} onPress={() => Alert.alert('Edit Profile', 'Profile editing coming soon.')}>
            <Text style={styles.editBtnText}>Edit Profile</Text>
          </TouchableOpacity>
        </View>

        <View style={[styles.section, SHADOWS.sm]}>
          <Text style={styles.sectionHeader}>Preferences</Text>
          <SettingsRow icon={<Bell size={20} color={COLORS.primary} />} title="Notifications" subtitle="Daily reminders and alerts">
            <View style={styles.toggle} />
          </SettingsRow>
          <SettingsRow icon={<Globe size={20} color={COLORS.primary} />} title="Language" subtitle="Select your preferred language">
            <Text style={styles.langText}>English</Text>
          </SettingsRow>
          <SettingsRow icon={<Monitor size={20} color={COLORS.primary} />} title="Theme" subtitle="Light / Dark mode">
            <View style={styles.themeRow}>
              <View style={styles.themeActive}><Text style={styles.themeActiveText}>Light</Text></View>
              <View style={styles.themeInactive}><Text style={styles.themeInactiveText}>Dark</Text></View>
            </View>
          </SettingsRow>
        </View>

        <View style={[styles.section, SHADOWS.sm]}>
          <Text style={styles.sectionHeader}>Data</Text>
          <SettingsRow icon={<Download size={20} color={COLORS.primary} />} title="Export Data" subtitle="Download as CSV or PDF">
            <View style={styles.exportRow}>
              <TouchableOpacity style={styles.exportBtn} onPress={() => Alert.alert('Export CSV', 'CSV export coming soon.')}><Text style={styles.exportBtnText}>CSV</Text></TouchableOpacity>
              <TouchableOpacity style={styles.exportBtn} onPress={() => Alert.alert('Export PDF', 'PDF export coming soon.')}><Text style={styles.exportBtnText}>PDF</Text></TouchableOpacity>
            </View>
          </SettingsRow>
        </View>

        <View style={[styles.section, SHADOWS.sm]}>
          <Text style={styles.sectionHeader}>Account</Text>
          <SettingsRow icon={<LogOut size={20} color={COLORS.neutral500} />} title="Logout" subtitle="Sign out of your account">
            <TouchableOpacity onPress={handleLogout} style={styles.logoutBtn}>
              <Text style={styles.logoutBtnText}>Logout</Text>
            </TouchableOpacity>
          </SettingsRow>
        </View>

        <Text style={styles.footer}>Recurlog v1.0 — Field Service Management</Text>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: COLORS.neutral50 },
  header: {
    flexDirection: 'row', alignItems: 'center', paddingHorizontal: SPACING[4],
    height: 56, backgroundColor: COLORS.white, borderBottomWidth: 1, borderBottomColor: COLORS.neutral200,
  },
  headerLeft: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  backBtn: { padding: 8, minWidth: 44, minHeight: 44, justifyContent: 'center' },
  headerTitle: { fontSize: FONT_SIZES.lg, fontWeight: '700', color: COLORS.navy },
  scroll: { padding: SPACING[4] },
  profileCard: {
    backgroundColor: COLORS.white, borderRadius: RADIUS.lg, padding: SPACING[6],
    alignItems: 'center', borderWidth: 1, borderColor: COLORS.neutral100, marginBottom: SPACING[6],
  },
  avatarLarge: {
    width: 72, height: 72, borderRadius: 36, backgroundColor: COLORS.primary,
    alignItems: 'center', justifyContent: 'center', marginBottom: SPACING[3],
  },
  avatarText: { color: COLORS.white, fontSize: FONT_SIZES['2xl'], fontWeight: '700' },
  profileName: { fontSize: FONT_SIZES.lg, fontWeight: '700', color: COLORS.navy },
  profileEmail: { fontSize: FONT_SIZES.sm, color: COLORS.neutral500, marginTop: 2 },
  editBtn: {
    marginTop: SPACING[4], paddingHorizontal: 20, paddingVertical: 10,
    backgroundColor: COLORS.primary, borderRadius: RADIUS.lg,
  },
  editBtnText: { color: COLORS.white, fontSize: FONT_SIZES.sm, fontWeight: '600' },
  section: {
    backgroundColor: COLORS.white, borderRadius: RADIUS.lg, overflow: 'hidden',
    borderWidth: 1, borderColor: COLORS.neutral100, marginBottom: SPACING[4],
  },
  sectionHeader: {
    fontSize: FONT_SIZES.xs, fontWeight: '600', color: COLORS.neutral500,
    textTransform: 'uppercase', letterSpacing: 0.5,
    padding: SPACING[4], borderBottomWidth: 1, borderBottomColor: COLORS.neutral100,
  },
  row: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
    padding: SPACING[4], borderBottomWidth: 1, borderBottomColor: COLORS.neutral50,
    minHeight: 56,
  },
  rowLeft: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  iconContainer: {
    width: 36, height: 36, borderRadius: RADIUS.lg,
    backgroundColor: COLORS.primary + '10', alignItems: 'center', justifyContent: 'center',
  },
  rowTitle: { fontSize: FONT_SIZES.sm, fontWeight: '500', color: COLORS.neutral800 },
  rowSubtitle: { fontSize: FONT_SIZES.xs, color: COLORS.neutral400 },
  toggle: { width: 44, height: 24, backgroundColor: COLORS.primary, borderRadius: 12, padding: 2 },
  langText: { fontSize: FONT_SIZES.sm, color: COLORS.neutral600 },
  themeRow: { flexDirection: 'row', gap: 4 },
  themeActive: {
    paddingHorizontal: 12, paddingVertical: 6, borderRadius: RADIUS.full,
    backgroundColor: COLORS.primary,
  },
  themeActiveText: { color: COLORS.white, fontSize: FONT_SIZES.xs, fontWeight: '500' },
  themeInactive: {
    paddingHorizontal: 12, paddingVertical: 6, borderRadius: RADIUS.full,
    backgroundColor: COLORS.neutral100,
  },
  themeInactiveText: { color: COLORS.neutral500, fontSize: FONT_SIZES.xs },
  exportRow: { flexDirection: 'row', gap: 8 },
  exportBtn: {
    paddingHorizontal: 14, paddingVertical: 6, borderRadius: RADIUS.md,
    backgroundColor: COLORS.neutral100,
  },
  exportBtnText: { fontSize: FONT_SIZES.xs, fontWeight: '500', color: COLORS.neutral700 },
  logoutBtn: {
    paddingHorizontal: 16, paddingVertical: 8, borderRadius: RADIUS.md,
    backgroundColor: COLORS.neutral100,
  },
  logoutBtnText: { fontSize: FONT_SIZES.xs, fontWeight: '500', color: COLORS.neutral700 },
  footer: { textAlign: 'center', fontSize: FONT_SIZES.xs, color: COLORS.neutral400, paddingVertical: SPACING[6] },
});
