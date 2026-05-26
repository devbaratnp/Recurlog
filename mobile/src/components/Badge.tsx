import React from 'react';
import { View, Text, StyleSheet, ViewStyle } from 'react-native';
import { colors, borderRadius, typography } from '../theme';

interface BadgeProps {
  label: string;
  variant?: 'brand' | 'info' | 'amber' | 'gray';
  size?: 'sm' | 'md';
  style?: ViewStyle;
}

export function Badge({ label, variant = 'brand', size = 'sm', style }: BadgeProps) {
  const variantStyles = {
    brand: { bg: '#DCFCE7', text: '#166534' },
    info: { bg: '#DBEAFE', text: '#1E40AF' },
    amber: { bg: '#FEF3C7', text: '#92400E' },
    gray: { bg: '#F3F4F6', text: '#374151' },
  };

  const v = variantStyles[variant];

  return (
    <View style={[styles.badge, { backgroundColor: v.bg }, size === 'md' && styles.md, style]}>
      <Text style={[styles.text, { color: v.text }, size === 'md' && styles.mdText]}>{label}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  badge: {
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: borderRadius.full,
    alignSelf: 'flex-start',
  },
  md: {
    paddingHorizontal: 10,
    paddingVertical: 4,
  },
  text: {
    fontSize: typography.fontSize.xs,
    fontWeight: typography.fontWeight.medium,
  },
  mdText: {
    fontSize: typography.fontSize.sm,
  },
});
