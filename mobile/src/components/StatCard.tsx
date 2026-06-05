import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import { COLORS, RADIUS, SPACING, FONT_SIZES, SHADOWS } from '../constants/theme';

interface StatCardProps {
  label: string;
  value: string | number;
  color?: string;
  sublabel?: string;
  onPress?: () => void;
}

export function StatCard({ label, value, color = COLORS.neutral900, sublabel, onPress }: StatCardProps) {
  const Container = onPress ? TouchableOpacity : View;
  return (
    <Container onPress={onPress} style={[styles.card, SHADOWS.sm]}>
      <Text style={styles.label}>{label}</Text>
      {sublabel ? <Text style={styles.sublabel}>{sublabel}</Text> : null}
      <Text style={[styles.value, { color }]}>{value}</Text>
    </Container>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: COLORS.white,
    borderRadius: RADIUS.lg,
    padding: SPACING[3],
    alignItems: 'center',
    borderWidth: 1,
    borderColor: COLORS.neutral100,
  },
  label: {
    fontSize: 11,
    fontWeight: '500',
    color: COLORS.neutral400,
    textTransform: 'uppercase',
    letterSpacing: 0.3,
  },
  sublabel: {
    fontSize: 11,
    fontWeight: '500',
    color: COLORS.neutral400,
    marginBottom: 2,
  },
  value: {
    fontSize: FONT_SIZES.xl,
    fontWeight: '700',
  },
});
