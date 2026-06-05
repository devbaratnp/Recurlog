import { View, Text, StyleSheet } from 'react-native';
import { COLORS, FONT_SIZES, SPACING } from '../constants/theme';

interface EmptyStateProps {
  title: string;
  subtitle?: string;
}

export function EmptyState({ title, subtitle }: EmptyStateProps) {
  return (
    <View style={styles.container}>
      <Text style={styles.title}>{title}</Text>
      {subtitle && <Text style={styles.subtitle}>{subtitle}</Text>}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: SPACING[12],
    paddingHorizontal: SPACING[4],
  },
  title: {
    fontSize: FONT_SIZES.base,
    fontWeight: '500',
    color: COLORS.neutral400,
    marginTop: SPACING[3],
  },
  subtitle: {
    fontSize: FONT_SIZES.sm,
    color: COLORS.neutral400,
    marginTop: SPACING[1],
  },
});
