import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { colors, borderRadius, typography } from '../theme';
import { getStatusColor, getStatusBg } from '../lib/helpers';

interface StatusPillProps {
  status: string;
}

export function StatusPill({ status }: StatusPillProps) {
  const bg = getStatusBg(status);
  const textColor = getStatusColor(status);
  const label = status.charAt(0).toUpperCase() + status.slice(1);

  return (
    <View style={[styles.pill, { backgroundColor: bg }]}>
      <Text style={[styles.text, { color: textColor }]}>{label}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  pill: {
    paddingHorizontal: 10,
    paddingVertical: 3,
    borderRadius: borderRadius.full,
    alignSelf: 'flex-start',
  },
  text: {
    fontSize: typography.fontSize.xs,
    fontWeight: typography.fontWeight.semibold,
  },
});
