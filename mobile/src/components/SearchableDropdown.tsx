import { useState, useMemo, useEffect } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, ScrollView } from 'react-native';
import { Check } from 'lucide-react-native';
import { COLORS, RADIUS, SPACING, FONT_SIZES } from '../constants/theme';

export interface SearchableDropdownItem {
  id: number | string;
  name: string;
  [key: string]: any;
}

interface SearchableDropdownProps {
  items: SearchableDropdownItem[];
  selectedId: number | string | null;
  onSelect: (item: SearchableDropdownItem | null) => void;
  placeholder?: string;
  emptyText?: string;
  allowClear?: boolean;
}

export function SearchableDropdown({
  items,
  selectedId,
  onSelect,
  placeholder = 'Type to search...',
  emptyText = 'No items found',
  allowClear = false,
}: SearchableDropdownProps) {
  const [query, setQuery] = useState('');
  const [open, setOpen] = useState(false);
  const [isFocused, setIsFocused] = useState(false);

  const selectedItem = useMemo(
    () => items.find((i) => i.id === selectedId),
    [items, selectedId]
  );

  useEffect(() => {
    if (selectedItem && !open) {
      setQuery(selectedItem.name);
    }
  }, [selectedItem, open]);

  const filtered = useMemo(() => {
    const q = query.toLowerCase().trim();
    if (!q) return items;
    return items.filter((i) => i.name?.toLowerCase().includes(q));
  }, [query, items]);

  const handleChangeText = (text: string) => {
    setQuery(text);
    if (text.length > 0 || isFocused) {
      setOpen(true);
    }
    if (!text && allowClear && selectedId) {
      onSelect(null);
    }
  };

  const handleSelect = (item: SearchableDropdownItem) => {
    onSelect(item);
    setQuery(item.name);
    setOpen(false);
  };

  return (
    <View>
      <TextInput
        style={styles.input}
        value={query}
        onChangeText={handleChangeText}
        onFocus={() => { setIsFocused(true); setOpen(true); }}
        onBlur={() => { setIsFocused(false); }}
        placeholder={placeholder}
        placeholderTextColor={COLORS.neutral400}
      />
      {open && (
        <View style={styles.dropdown}>
          <ScrollView
            style={styles.list}
            keyboardShouldPersistTaps="handled"
            nestedScrollEnabled={true}
          >
            {filtered.map((item) => (
              <TouchableOpacity
                key={item.id}
                style={[
                  styles.item,
                  selectedId === item.id && styles.itemActive,
                ]}
                onPress={() => handleSelect(item)}
              >
                <Text
                  style={[
                    styles.itemText,
                    selectedId === item.id && styles.itemTextActive,
                  ]}
                >
                  {item.name}
                </Text>
                {selectedId === item.id && (
                  <Check size={16} color={COLORS.primary} />
                )}
              </TouchableOpacity>
            ))}
            {filtered.length === 0 && (
              <Text style={styles.emptyText}>{emptyText}</Text>
            )}
          </ScrollView>
        </View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  input: {
    height: 44,
    borderWidth: 1,
    borderColor: COLORS.neutral200,
    borderRadius: RADIUS.lg,
    paddingHorizontal: SPACING[4],
    fontSize: FONT_SIZES.sm,
    color: COLORS.neutral900,
    backgroundColor: COLORS.white,
  },
  dropdown: {
    marginTop: 4,
    borderWidth: 1,
    borderColor: COLORS.neutral200,
    borderRadius: RADIUS.lg,
    backgroundColor: COLORS.white,
    maxHeight: 200,
  },
  list: { maxHeight: 200, flexGrow: 0 },
  item: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: SPACING[4],
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.neutral50,
  },
  itemActive: { backgroundColor: COLORS.primary + '10' },
  itemText: { fontSize: FONT_SIZES.sm, color: COLORS.neutral800 },
  itemTextActive: { fontWeight: '600' },
  emptyText: {
    padding: SPACING[4],
    fontSize: FONT_SIZES.sm,
    color: COLORS.neutral400,
    textAlign: 'center',
  },
});
