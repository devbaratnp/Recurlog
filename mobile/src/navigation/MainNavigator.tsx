import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { useState, useEffect, useRef } from 'react';
import { View, Text, TouchableOpacity, StyleSheet, AppState } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { LayoutDashboard, Users, Calendar, BookOpen, Menu, Briefcase } from 'lucide-react-native';
import { COLORS, FONT_SIZES, SPACING } from '../constants/theme';
import { useNotificationStore } from '../store/notificationStore';
import { useAuthStore } from '../store/authStore';

import { DashboardScreen } from '../screens/dashboard/DashboardScreen';
import { StaffDashboardScreen } from '../screens/staff/StaffDashboardScreen';
import { CustomerListScreen } from '../screens/customers/CustomerListScreen';
import { CustomerAddScreen } from '../screens/customers/CustomerAddScreen';
import { CustomerDetailScreen } from '../screens/customers/CustomerDetailScreen';
import { TaskListScreen } from '../screens/tasks/TaskListScreen';
import { TaskAddScreen } from '../screens/tasks/TaskAddScreen';
import { TaskDetailScreen } from '../screens/tasks/TaskDetailScreen';
import { TaskEditScreen } from '../screens/tasks/TaskEditScreen';
import { OrderListScreen } from '../screens/orders/OrderListScreen';
import { OrderAddScreen } from '../screens/orders/OrderAddScreen';
import { OrderDetailScreen } from '../screens/orders/OrderDetailScreen';
import { StaffListScreen } from '../screens/staff/StaffListScreen';
import { StaffDetailScreen } from '../screens/staff/StaffDetailScreen';
import { NotificationScreen } from '../screens/notifications/NotificationScreen';
import { SettingsScreen } from '../screens/settings/SettingsScreen';
import { ReportsScreen } from '../screens/reports/ReportsScreen';
import { DaybookScreen } from '../screens/daybook/DaybookScreen';
import { Sidebar } from '../components/Sidebar';

export type MainStackParamList = {
  Dashboard: undefined;
  CustomerList: undefined;
  CustomerAdd: { id?: number } | undefined;
  CustomerDetail: { id: number };
  TaskList: { status?: string; filter?: any; type?: 'onetime' | 'recurring' } | undefined;
  RecurringTaskList: { status?: string; filter?: any; type?: 'onetime' | 'recurring' } | undefined;
  TaskAdd: { type?: 'onetime' | 'recurring' } | undefined;
  TaskDetail: { id: number };
  TaskEdit: { id: number };
  OrderList: { filter?: string } | undefined;
  OrderAdd: undefined;
  OrderDetail: { id: number };
  StaffList: undefined;
  StaffDetail: { id: number };
  Daybook: undefined;
  Notifications: undefined;
  Settings: undefined;
  Reports: undefined;
};

const Tab = createBottomTabNavigator();

const adminTabs = [
  { icon: LayoutDashboard, label: 'Dashboard', key: 'DashboardTab' },
  { icon: Users, label: 'Customers', key: 'CustomersTab' },
  { icon: BookOpen, label: 'Daybook', key: 'DaybookTab' },
  { icon: Calendar, label: 'Tasks', key: 'TasksTab' },
  { icon: Menu, label: 'More', key: 'MoreTab', isMore: true },
];

const staffTabs = [
  { icon: LayoutDashboard, label: 'Dashboard', key: 'DashboardTab' },
  { icon: Calendar, label: 'Tasks', key: 'TasksTab' },
  { icon: Briefcase, label: 'Orders', key: 'OrdersStaffTab' },
  { icon: BookOpen, label: 'Daybook', key: 'DaybookTab' },
  { icon: Menu, label: 'More', key: 'MoreTab', isMore: true },
];

function BottomNavBar({ state, navigation }: any) {
  const insets = useSafeAreaInsets();
  const [sidebarVisible, setSidebarVisible] = useState(false);
  const [sidebarActiveScreen, setSidebarActiveScreen] = useState('Dashboard');
  const user = useAuthStore((s) => s.user);
  const isStaff = user?.role === 'staff';
  const tabs = isStaff ? staffTabs : adminTabs;

  const currentRouteName = state?.routeNames?.[state.index] || 'DashboardTab';

  const navigateTo = (screen: string) => {
    setSidebarActiveScreen(screen);
    if (screen === 'Dashboard' || screen === 'CustomerList' || screen === 'Daybook' || screen === 'TaskList' || screen === 'RecurringTaskList') {
      const tabMap: Record<string, string> = {
        Dashboard: 'DashboardTab',
        CustomerList: 'CustomersTab',
        Daybook: 'DaybookTab',
        TaskList: 'TasksTab',
        RecurringTaskList: 'TasksTab',
      };
      navigation.navigate(tabMap[screen] || 'DashboardTab', { screen });
    } else {
      navigation.navigate('DashboardTab', { screen });
    }
    setSidebarVisible(false);
  };

  return (
    <>
      <View style={[styles.tabBar, { paddingBottom: insets.bottom }]}>
        {tabs.map((tab) => {
          const isActive = currentRouteName === tab.key;
          const Icon = tab.icon;
          return (
            <TouchableOpacity
              key={tab.key}
              style={styles.tabItem}
              onPress={() => {
                if (tab.isMore) {
                  setSidebarVisible(true);
                } else {
                  navigation.navigate(tab.key);
                }
              }}
            >
              <Icon size={20} color={isActive ? COLORS.primary : COLORS.neutral400} />
              <Text
                style={[
                  styles.tabLabel,
                  { color: isActive ? COLORS.primary : COLORS.neutral400 },
                ]}
                numberOfLines={1}
              >
                {tab.label}
              </Text>
            </TouchableOpacity>
          );
        })}
      </View>

      <Sidebar
        visible={sidebarVisible}
        currentScreen={sidebarActiveScreen}
        onNavigate={navigateTo}
        onClose={() => setSidebarVisible(false)}
      />
    </>
  );
}

function DashboardStack() {
  return (
    <Stack.Navigator screenOptions={{ headerShown: false }}>
      <Stack.Screen name="Dashboard" component={DashboardContent} />
      <Stack.Screen name="CustomerList" component={CustomerListScreen} />
      <Stack.Screen name="CustomerAdd" component={CustomerAddScreen} />
      <Stack.Screen name="CustomerDetail" component={CustomerDetailScreen} />
      <Stack.Screen name="TaskList" component={TaskListScreen} />
      <Stack.Screen name="RecurringTaskList" component={TaskListScreen} />
      <Stack.Screen name="TaskDetail" component={TaskDetailScreen} />
      <Stack.Screen name="TaskEdit" component={TaskEditScreen} />
      <Stack.Screen name="TaskAdd" component={TaskAddScreen} />
      <Stack.Screen name="OrderList" component={OrderListScreen} />
      <Stack.Screen name="OrderAdd" component={OrderAddScreen} />
      <Stack.Screen name="OrderDetail" component={OrderDetailScreen} />
      <Stack.Screen name="StaffList" component={StaffListScreen} />
      <Stack.Screen name="StaffDetail" component={StaffDetailScreen} />
      <Stack.Screen name="Daybook" component={DaybookScreen} />
      <Stack.Screen name="Notifications" component={NotificationScreen} />
      <Stack.Screen name="Settings" component={SettingsScreen} />
      <Stack.Screen name="Reports" component={ReportsScreen} />
    </Stack.Navigator>
  );
}

const Stack = createNativeStackNavigator<MainStackParamList>();

function DashboardContent() {
  const user = useAuthStore((s) => s.user);
  if (user?.role === 'staff') return <StaffDashboardScreen />;
  return <DashboardScreen />;
}

export function MainNavigator() {
  const { fetchNotifications, startPolling, stopPolling } = useNotificationStore();
  const appState = useRef(AppState.currentState);

  useEffect(() => {
    fetchNotifications();
    startPolling(30000);
    const sub = AppState.addEventListener('change', (nextState) => {
      if (appState.current.match(/inactive|background/) && nextState === 'active') {
        fetchNotifications();
      }
      appState.current = nextState;
    });
    return () => {
      stopPolling();
      sub.remove();
    };
  }, []);

  return (
    <Tab.Navigator
      tabBar={(props) => <BottomNavBar {...props} />}
      screenOptions={{ headerShown: false }}
    >
      <Tab.Screen name="DashboardTab" component={DashboardStack} />
      <Tab.Screen name="CustomersTab" component={CustomerListScreen} />
      <Tab.Screen name="DaybookTab" component={DaybookScreen} />
      <Tab.Screen name="OrdersStaffTab" component={OrderListScreen} />
      <Tab.Screen name="TasksTab" component={TaskListScreen} />
      <Tab.Screen name="MoreTab" component={DashboardStack} listeners={({ navigation }) => ({ tabPress: (e) => { e.preventDefault(); navigation.navigate('DashboardTab'); } })} />
    </Tab.Navigator>
  );
}

const styles = StyleSheet.create({
  tabBar: {
    flexDirection: 'row',
    backgroundColor: COLORS.white,
    borderTopWidth: 1,
    borderTopColor: COLORS.neutral200,
    paddingTop: 4,
  },
  tabItem: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 4,
    gap: 2,
  },
  tabLabel: {
    fontSize: 10,
    fontWeight: '500',
    textAlign: 'center',
  },
});
