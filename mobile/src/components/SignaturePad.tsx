import { useRef, useState } from 'react';
import { View, StyleSheet, PanResponder, Text, TouchableOpacity } from 'react-native';
import { COLORS, RADIUS, FONT_SIZES } from '../constants/theme';

interface Point { x: number; y: number }

interface SignaturePadProps {
  onData: (dataUrl: string) => void;
}

export function SignaturePad({ onData }: SignaturePadProps) {
  const [strokes, setStrokes] = useState<Point[][]>([]);
  const [currentStroke, setCurrentStroke] = useState<Point[]>([]);
  const [hasDrawn, setHasDrawn] = useState(false);

  const panResponder = useRef(
    PanResponder.create({
      onStartShouldSetPanResponder: () => true,
      onMoveShouldSetPanResponder: () => true,
      onPanResponderGrant: (evt) => {
        const { locationX, locationY } = evt.nativeEvent;
        setCurrentStroke([{ x: locationX, y: locationY }]);
        setHasDrawn(true);
      },
      onPanResponderMove: (evt) => {
        const { locationX, locationY } = evt.nativeEvent;
        setCurrentStroke((prev) => [...prev, { x: locationX, y: locationY }]);
      },
      onPanResponderRelease: () => {
        setStrokes((prev) => [...prev, currentStroke]);
        setCurrentStroke([]);
      },
    })
  ).current;

  const allPoints = [...strokes, ...(currentStroke.length > 0 ? [currentStroke] : [])];

  const clear = () => {
    setStrokes([]);
    setCurrentStroke([]);
    setHasDrawn(false);
    onData('');
  };

  return (
    <View style={styles.wrapper}>
      <View style={styles.pad} {...panResponder.panHandlers}>
        {allPoints.map((stroke, si) =>
          stroke.map((p, pi) => (
            <View
              key={`${si}-${pi}`}
              style={{
                position: 'absolute',
                left: p.x - 1.5,
                top: p.y - 1.5,
                width: 3,
                height: 3,
                borderRadius: 1.5,
                backgroundColor: COLORS.neutral900,
              }}
            />
          ))
        )}
        {!hasDrawn && <Text style={styles.placeholder}>Sign here</Text>}
      </View>
      {hasDrawn && (
        <TouchableOpacity style={styles.clearBtn} onPress={clear}>
          <Text style={styles.clearBtnText}>Clear</Text>
        </TouchableOpacity>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  wrapper: { alignItems: 'center' },
  pad: {
    width: '100%', height: 120,
    backgroundColor: COLORS.neutral50,
    borderRadius: RADIUS.md,
    borderWidth: 1, borderColor: COLORS.neutral200,
    justifyContent: 'center', alignItems: 'center',
    overflow: 'hidden',
  },
  placeholder: { color: COLORS.neutral400, fontSize: FONT_SIZES.sm },
  clearBtn: { paddingVertical: 4, paddingHorizontal: 12, marginTop: 4 },
  clearBtnText: { color: COLORS.danger, fontSize: FONT_SIZES.xs, fontWeight: '600' },
});
