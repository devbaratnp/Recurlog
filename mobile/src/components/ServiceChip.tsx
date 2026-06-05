import { View, Text, StyleSheet } from 'react-native';
import { RADIUS, FONT_SIZES, SERVICE_COLORS } from '../constants/theme';

interface ServiceChipProps {
  service: string;
}

export function ServiceChip({ service }: ServiceChipProps) {
  const color = SERVICE_COLORS[service] || '#6B7280';
  return (
    <View style={[styles.chip, { backgroundColor: color + '20' }]}>
      <Text style={[styles.text, { color }]}>{service}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  chip: {
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: RADIUS.full,
  },
  text: {
    fontSize: FONT_SIZES.xs,
    fontWeight: '500',
  },
});
