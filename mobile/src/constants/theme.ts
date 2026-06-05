export const COLORS = {
  primary: '#22C55E',
  primaryDark: '#16a34a',
  success: '#16A34A',
  danger: '#EF4444',
  warning: '#F59E0B',
  info: '#3B82F6',
  navy: '#0B1E3D',
  amber: '#F59E0B',

  neutral50: '#F8FAFC',
  neutral100: '#F1F5F9',
  neutral200: '#E2E8F0',
  neutral300: '#CBD5E1',
  neutral400: '#94A3B8',
  neutral500: '#64748B',
  neutral600: '#475569',
  neutral700: '#334155',
  neutral800: '#1E293B',
  neutral900: '#0F172A',

  white: '#FFFFFF',
  black: '#000000',

  badgePendingBg: '#FEF3C7',
  badgePendingText: '#92400E',
  badgeCompletedBg: '#DCFCE7',
  badgeCompletedText: '#166534',
  badgeMissedBg: '#FEE2E2',
  badgeMissedText: '#991B1B',
  badgeInfoBg: '#DBEAFE',
  badgeInfoText: '#1E40AF',

  backdrop: 'rgba(0,0,0,0.5)',
  shadow: 'rgba(0,0,0,0.08)',
  brandGlow: 'rgba(34,197,94,0.3)',

  kpiGreen: '#22C55E',
  kpiGreenBg: '#F0FDF4',
  kpiAmber: '#F59E0B',
  kpiAmberBg: '#FFFBEB',
  kpiRed: '#EF4444',
  kpiRedBg: '#FEF2F2',
  cardBorder: '#E2E8F0',
  textSecondary: '#64748B',
};

export const FONTS = {
  regular: 'Poppins_400Regular',
  medium: 'Poppins_500Medium',
  semibold: 'Poppins_600SemiBold',
  bold: 'Poppins_700Bold',
  extrabold: 'Poppins_800ExtraBold',
};

export const FONT_SIZES = {
  xs: 12,
  sm: 14,
  base: 16,
  lg: 18,
  xl: 20,
  '2xl': 24,
  '3xl': 30,
  '4xl': 36,
};

export const SPACING = {
  1: 4,
  2: 8,
  3: 12,
  4: 16,
  5: 20,
  6: 24,
  8: 32,
  10: 40,
  12: 48,
};

export const RADIUS = {
  sm: 4,
  md: 8,
  lg: 12,
  xl: 16,
  '2xl': 20,
  '3xl': 24,
  full: 999,
};

export const SHADOWS = {
  sm: {
    shadowColor: COLORS.black,
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 1,
  },
  md: {
    shadowColor: COLORS.black,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.1,
    shadowRadius: 6,
    elevation: 3,
  },
  lg: {
    shadowColor: COLORS.black,
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.1,
    shadowRadius: 15,
    elevation: 5,
  },
};

export const SERVICE_COLORS: Record<string, string> = {
  RO: '#10B981',
  TV: '#3B82F6',
  Refrigerator: '#06B6D4',
  AC: '#F97316',
  'Washing Machine': '#A855F7',
  Other: '#6B7280',
};

export const CATEGORY_COLORS: Record<number, string> = {
  1: '#1DB954',
  2: '#0EA5E9',
  3: '#F59E0B',
  4: '#8B5CF6',
  5: '#EC4899',
  6: '#6366F1',
};
