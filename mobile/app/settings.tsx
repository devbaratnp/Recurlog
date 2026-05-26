import { useState } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  ScrollView,
  Switch,
  StyleSheet,
  Modal,
} from 'react-native';
import { useRouter } from 'expo-router';
import { Ionicons } from '@expo/vector-icons';
import { ScreenWrapper } from '../src/components/ScreenWrapper';
import { Card } from '../src/components/Card';
import { Button } from '../src/components/Button';
import { useAuth } from '../src/lib/auth';
import { useToast } from '../src/components/Toast';
import { colors, borderRadius, typography } from '../src/theme';

export default function SettingsScreen() {
  const router = useRouter();
  const { user, logout } = useAuth();
  const { showToast } = useToast();
  const [notificationsEnabled, setNotificationsEnabled] = useState(true);
  const [resetModalVisible, setResetModalVisible] = useState(false);

  const handleLogout = () => {
    logout();
    showToast('Logged out successfully', 'success');
    router.replace('/login');
  };

  const handleResetData = () => {
    setResetModalVisible(false);
    showToast('Demo data reset. Reloading...', 'success');
  };

  return (
    <ScreenWrapper>
      <ScrollView contentContainerStyle={styles.scrollContent}>
        <View style={styles.header}>
          <Text style={styles.title}>Settings</Text>
        </View>

        {/* Profile */}
        <Card style={styles.profileCard}>
          <View style={styles.profileContent}>
            <View style={styles.avatar}>
              <Text style={styles.avatarText}>
                {(user?.name || 'Admin User').split(' ').map(n => n[0]).join('')}
              </Text>
            </View>
            <Text style={styles.profileName}>{user?.name || 'Admin User'}</Text>
            <Text style={styles.profileEmail}>{user?.email || 'admin@recurlog.com'}</Text>
            <Button title="Edit Profile" onPress={() => showToast('Edit profile is coming soon.', 'info')} variant="primary" size="sm" />
          </View>
        </Card>

        {/* Preferences */}
        <Card style={styles.sectionCard}>
          <Text style={styles.sectionTitle}>Preferences</Text>
          <View style={styles.preferenceItem}>
            <View style={styles.prefLeft}>
              <View style={[styles.prefIcon, { backgroundColor: colors.brand + '15' }]}>
                <Ionicons name="notifications" size={20} color={colors.brand} />
              </View>
              <View>
                <Text style={styles.prefLabel}>Notifications</Text>
                <Text style={styles.prefDesc}>Daily reminders and alerts</Text>
              </View>
            </View>
            <Switch
              value={notificationsEnabled}
              onValueChange={setNotificationsEnabled}
              trackColor={{ false: colors.gray[200], true: colors.brand }}
              thumbColor={colors.white}
            />
          </View>
          <View style={styles.preferenceItem}>
            <View style={styles.prefLeft}>
              <View style={[styles.prefIcon, { backgroundColor: colors.brand + '15' }]}>
                <Ionicons name="globe" size={20} color={colors.brand} />
              </View>
              <View>
                <Text style={styles.prefLabel}>Language</Text>
                <Text style={styles.prefDesc}>Select your preferred language</Text>
              </View>
            </View>
            <Text style={styles.prefValue}>English</Text>
          </View>
          <View style={styles.preferenceItem}>
            <View style={styles.prefLeft}>
              <View style={[styles.prefIcon, { backgroundColor: colors.brand + '15' }]}>
                <Ionicons name="desktop" size={20} color={colors.brand} />
              </View>
              <View>
                <Text style={styles.prefLabel}>Theme</Text>
                <Text style={styles.prefDesc}>Light / Dark mode</Text>
              </View>
            </View>
            <View style={styles.themeToggle}>
              <TouchableOpacity style={styles.themeBtnActive} activeOpacity={0.7}>
                <Text style={styles.themeBtnActiveText}>Light</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.themeBtn} activeOpacity={0.7}>
                <Text style={styles.themeBtnText}>Dark</Text>
              </TouchableOpacity>
            </View>
          </View>
        </Card>

        {/* Data */}
        <Card style={styles.sectionCard}>
          <Text style={styles.sectionTitle}>Data</Text>
          <View style={styles.preferenceItem}>
            <View style={styles.prefLeft}>
              <View style={[styles.prefIcon, { backgroundColor: colors.brand + '15' }]}>
                <Ionicons name="download" size={20} color={colors.brand} />
              </View>
              <View>
                <Text style={styles.prefLabel}>Export Data</Text>
                <Text style={styles.prefDesc}>Download as CSV or PDF</Text>
              </View>
            </View>
            <View style={styles.exportBtns}>
              <TouchableOpacity style={styles.exportBtn} onPress={() => showToast('CSV export coming soon.', 'info')} activeOpacity={0.7}>
                <Text style={styles.exportBtnText}>CSV</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.exportBtn} onPress={() => showToast('PDF export coming soon.', 'info')} activeOpacity={0.7}>
                <Text style={styles.exportBtnText}>PDF</Text>
              </TouchableOpacity>
            </View>
          </View>
          <View style={styles.preferenceItem}>
            <View style={styles.prefLeft}>
              <View style={[styles.prefIcon, { backgroundColor: colors.danger + '15' }]}>
                <Ionicons name="trash" size={20} color={colors.danger} />
              </View>
              <View>
                <Text style={[styles.prefLabel, { color: colors.danger }]}>Reset Demo Data</Text>
                <Text style={styles.prefDesc}>Clear all data and restart fresh</Text>
              </View>
            </View>
            <TouchableOpacity
              style={styles.resetBtn}
              onPress={() => setResetModalVisible(true)}
              activeOpacity={0.7}
            >
              <Text style={styles.resetBtnText}>Reset</Text>
            </TouchableOpacity>
          </View>
        </Card>

        {/* Account */}
        <Card style={styles.sectionCard}>
          <Text style={styles.sectionTitle}>Account</Text>
          <View style={styles.preferenceItem}>
            <View style={styles.prefLeft}>
              <View style={[styles.prefIcon, { backgroundColor: colors.gray[100] }]}>
                <Ionicons name="log-out" size={20} color={colors.gray[500]} />
              </View>
              <View>
                <Text style={styles.prefLabel}>Logout</Text>
                <Text style={styles.prefDesc}>Sign out of your account</Text>
              </View>
            </View>
            <Button title="Logout" onPress={handleLogout} variant="secondary" size="sm" />
          </View>
        </Card>

        {/* Footer */}
        <Text style={styles.footer}>Recurlog v1.0 — Field Service Management</Text>
      </ScrollView>

      {/* Reset Modal */}
      <Modal
        visible={resetModalVisible}
        transparent
        animationType="fade"
        onRequestClose={() => setResetModalVisible(false)}
      >
        <TouchableOpacity
          style={styles.modalOverlay}
          activeOpacity={1}
          onPress={() => setResetModalVisible(false)}
        >
          <View style={styles.modalContent}>
            <View style={styles.modalIcon}>
              <Ionicons name="warning" size={28} color={colors.danger} />
            </View>
            <Text style={styles.modalTitle}>Reset Demo Data?</Text>
            <Text style={styles.modalText}>
              This will clear all demo data including customers, tasks, and settings. This cannot be undone.
            </Text>
            <View style={styles.modalActions}>
              <Button
                title="Cancel"
                onPress={() => setResetModalVisible(false)}
                variant="secondary"
                style={{ flex: 1 }}
              />
              <Button
                title="Reset"
                onPress={handleResetData}
                variant="danger"
                style={{ flex: 1 }}
              />
            </View>
          </View>
        </TouchableOpacity>
      </Modal>
    </ScreenWrapper>
  );
}

const styles = StyleSheet.create({
  scrollContent: {
    padding: 16,
    paddingBottom: 32,
  },
  header: {
    marginBottom: 20,
  },
  title: {
    fontSize: typography.fontSize.xl,
    fontWeight: typography.fontWeight.bold,
    color: colors.navy,
  },
  profileCard: {
    marginBottom: 16,
  },
  profileContent: {
    alignItems: 'center',
    gap: 8,
  },
  avatar: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: colors.brand,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 4,
    borderColor: colors.brand + '33',
  },
  avatarText: {
    fontSize: typography.fontSize['3xl'],
    fontWeight: typography.fontWeight.bold,
    color: colors.white,
  },
  profileName: {
    fontSize: typography.fontSize.lg,
    fontWeight: typography.fontWeight.bold,
    color: colors.navy,
    marginTop: 4,
  },
  profileEmail: {
    fontSize: typography.fontSize.sm,
    color: colors.gray[500],
  },
  sectionCard: {
    marginBottom: 16,
  },
  sectionTitle: {
    fontSize: typography.fontSize.xs,
    fontWeight: typography.fontWeight.semibold,
    color: colors.gray[500],
    textTransform: 'uppercase',
    letterSpacing: 0.5,
    marginBottom: 8,
  },
  preferenceItem: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: colors.gray[100],
  },
  prefLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    flex: 1,
  },
  prefIcon: {
    width: 36,
    height: 36,
    borderRadius: borderRadius.md,
    alignItems: 'center',
    justifyContent: 'center',
  },
  prefLabel: {
    fontSize: typography.fontSize.sm,
    fontWeight: typography.fontWeight.medium,
    color: colors.navy,
  },
  prefDesc: {
    fontSize: typography.fontSize.xs,
    color: colors.gray[400],
    marginTop: 1,
  },
  prefValue: {
    fontSize: typography.fontSize.sm,
    color: colors.gray[600],
    fontWeight: typography.fontWeight.medium,
  },
  themeToggle: {
    flexDirection: 'row',
    gap: 4,
  },
  themeBtn: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: borderRadius.full,
    backgroundColor: colors.gray[100],
  },
  themeBtnActive: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: borderRadius.full,
    backgroundColor: colors.brand,
  },
  themeBtnText: {
    fontSize: typography.fontSize.xs,
    fontWeight: typography.fontWeight.medium,
    color: colors.gray[500],
  },
  themeBtnActiveText: {
    fontSize: typography.fontSize.xs,
    fontWeight: typography.fontWeight.medium,
    color: colors.white,
  },
  exportBtns: {
    flexDirection: 'row',
    gap: 8,
  },
  exportBtn: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: borderRadius.md,
    borderWidth: 1,
    borderColor: colors.gray[200],
  },
  exportBtnText: {
    fontSize: typography.fontSize.xs,
    fontWeight: typography.fontWeight.medium,
    color: colors.gray[600],
  },
  resetBtn: {
    paddingHorizontal: 14,
    paddingVertical: 6,
    borderRadius: borderRadius.md,
    backgroundColor: colors.danger,
  },
  resetBtnText: {
    fontSize: typography.fontSize.xs,
    fontWeight: typography.fontWeight.semibold,
    color: colors.white,
  },
  footer: {
    textAlign: 'center',
    fontSize: typography.fontSize.xs,
    color: colors.gray[400],
    paddingVertical: 16,
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 24,
  },
  modalContent: {
    backgroundColor: colors.white,
    borderRadius: borderRadius.xl,
    padding: 24,
    width: '100%',
    maxWidth: 340,
    alignItems: 'center',
  },
  modalIcon: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: colors.danger + '15',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 16,
  },
  modalTitle: {
    fontSize: typography.fontSize.lg,
    fontWeight: typography.fontWeight.bold,
    color: colors.navy,
    marginBottom: 8,
  },
  modalText: {
    fontSize: typography.fontSize.sm,
    color: colors.gray[500],
    textAlign: 'center',
    lineHeight: 20,
    marginBottom: 24,
  },
  modalActions: {
    flexDirection: 'row',
    gap: 12,
    width: '100%',
  },
});
