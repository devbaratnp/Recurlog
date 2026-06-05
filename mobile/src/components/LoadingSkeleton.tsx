import { View, StyleSheet, Animated, Easing } from 'react-native';
import { useEffect, useRef } from 'react';
import { COLORS, RADIUS, SPACING } from '../constants/theme';

function ShimmerBlock({ width = '100%', height = 16, style }: { width?: number | string; height?: number; style?: any }) {
  const animValue = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    const animation = Animated.loop(
      Animated.sequence([
        Animated.timing(animValue, {
          toValue: 1,
          duration: 1500,
          easing: Easing.linear,
          useNativeDriver: true,
        }),
        Animated.timing(animValue, {
          toValue: 0,
          duration: 1500,
          easing: Easing.linear,
          useNativeDriver: true,
        }),
      ])
    );
    animation.start();
    return () => animation.stop();
  }, []);

  const opacity = animValue.interpolate({
    inputRange: [0, 1],
    outputRange: [0.3, 0.7],
  });

  return (
    <Animated.View
      style={[
        styles.shimmer,
        { width: width as any, height, opacity },
        style,
      ]}
    />
  );
}

export function DashboardSkeleton() {
  return (
    <View style={styles.skeletonContainer}>
      <View style={styles.grid}>
        {[1, 2, 3, 4].map((i) => (
          <ShimmerBlock key={i} height={80} style={{ flex: 1, marginHorizontal: 4 }} />
        ))}
      </View>
      <View style={{ marginTop: SPACING[6] }}>
        {[1, 2, 3].map((i) => (
          <ShimmerBlock key={i} height={60} style={{ marginBottom: SPACING[3] }} />
        ))}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  shimmer: {
    backgroundColor: COLORS.neutral200,
    borderRadius: RADIUS.md,
  },
  skeletonContainer: {
    padding: SPACING[4],
  },
  grid: {
    flexDirection: 'row',
    gap: SPACING[2],
  },
});
