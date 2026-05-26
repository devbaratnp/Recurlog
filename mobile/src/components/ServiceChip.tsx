import React from 'react';
import { TouchableOpacity, Text, StyleSheet, ViewStyle } from 'react-native';
import { colors, borderRadius, typography } from '../theme';
import { getCategoryColor, getCategoryTextColor } from '../lib/helpers';

interface ServiceChipProps {
  label: string;
  selected?: boolean;
  onPress?: () => void;
  size?: 'sm' | 'md';
  style?: ViewStyle;
}

export function ServiceChip({ label, selected, onPress, size = 'sm', style }: ServiceChipProps) {
  const bg = selected ? colors.brand : getCategoryColor(label);
  const textColor = selected ? colors.white : getCategoryTextColor(label);

  return (
    <TouchableOpacity
      onPress={onPress}
      disabled={!onPress}
      activeOpacity={onPress ? 0.7 : 1}
      style={[
        styles.chip,
        { backgroundColor: bg },
        size === 'md' && styles.md,
        style,
      ]}
    >
      <Text style={[styles.text, { color: textColor }, size === 'md' && styles.mdText]}>
        {label}
      </Text>
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  chip: {
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: borderRadius.full,
    alignSelf: 'flex-start',
  },
  md: {
    paddingHorizontal: 16,
    paddingVertical: 8,
  },
  text: {
    fontSize: typography.fontSize.xs,
    fontWeight: typography.fontWeight.medium,
  },
  mdText: {
    fontSize: typography.fontSize.sm,
  },
});
