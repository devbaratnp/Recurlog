import { memo, useRef } from 'react';
import { View, Text, TouchableOpacity, Animated, StyleSheet } from 'react-native';
import { MapPin, ClipboardList, Users, BookOpen } from 'lucide-react-native';
import { COLORS, RADIUS, SPACING, FONT_SIZES } from '../constants/theme';

interface ActionItem {
  icon: any;
  label: string;
  onPress: () => void;
}

interface QuickActionsProps {
  onOrders: () => void;
  onCustomers: () => void;
  onDaybook: () => void;
}

function ActionButton({ icon: Icon, label, onPress }: ActionItem) {
  const scaleAnim = useRef(new Animated.Value(1)).current;

  const handlePressIn = () => {
    Animated.spring(scaleAnim, { toValue: 0.92, useNativeDriver: true }).start();
  };
  const handlePressOut = () => {
    Animated.spring(scaleAnim, { toValue: 1, useNativeDriver: true }).start();
  };

  return (
    <Animated.View style={{ transform: [{ scale: scaleAnim }], flex: 1 }}>
      <TouchableOpacity
        style={styles.btn}
        onPress={onPress}
        onPressIn={handlePressIn}
        onPressOut={handlePressOut}
        activeOpacity={0.7}
        accessibilityLabel={label}
        accessibilityRole="button"
      >
        <View style={styles.iconWrap}>
          <Icon size={22} color={COLORS.primary} />
        </View>
        <Text style={styles.label}>{label}</Text>
      </TouchableOpacity>
    </Animated.View>
  );
}

export const QuickActions = memo(function QuickActions({ onOrders, onCustomers, onDaybook }: QuickActionsProps) {
  return (
    <View style={styles.row}>
      <ActionButton icon={MapPin} label="Check In" onPress={() => {}} />
      <ActionButton icon={ClipboardList} label="Orders" onPress={onOrders} />
      <ActionButton icon={Users} label="Customers" onPress={onCustomers} />
      <ActionButton icon={BookOpen} label="Daybook" onPress={onDaybook} />
    </View>
  );
});

const styles = StyleSheet.create({
  row: { flexDirection: 'row', gap: SPACING[3] },
  btn: {
    alignItems: 'center',
    gap: 6,
    paddingVertical: SPACING[3],
    backgroundColor: COLORS.white,
    borderRadius: RADIUS.xl,
  },
  iconWrap: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: 'rgba(34,197,94,0.1)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  label: { fontSize: 11, fontWeight: '600', color: COLORS.neutral600 },
});
