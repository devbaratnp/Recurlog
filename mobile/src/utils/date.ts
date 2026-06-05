import { format, formatDistanceToNow, isToday, isTomorrow, isYesterday, parseISO } from 'date-fns';

export function formatDate(dateStr: string): string {
  if (!dateStr) return '';
  return format(parseISO(dateStr), 'MMM d, yyyy');
}

export function formatRelative(dateStr: string): string {
  if (!dateStr) return '';
  const date = parseISO(dateStr);
  if (isToday(date)) return 'Today';
  if (isTomorrow(date)) return 'Tomorrow';
  if (isYesterday(date)) return 'Yesterday';
  const days = Math.round((date.getTime() - Date.now()) / 86400000);
  if (days > 1 && days <= 7) return `In ${days} days`;
  if (days < 0 && days >= -7) return `${Math.abs(days)} days ago`;
  return format(date, 'MMM d, yyyy');
}

export function formatRelativeTime(dateStr: string): string {
  if (!dateStr) return '';
  return formatDistanceToNow(parseISO(dateStr), { addSuffix: true });
}

export function todayISO(): string {
  return format(new Date(), 'yyyy-MM-dd');
}

export function addToDate(dateStr: string, value: number, unit: 'days' | 'weeks' | 'months' | 'years'): string {
  const d = parseISO(dateStr);
  switch (unit) {
    case 'days': d.setDate(d.getDate() + value); break;
    case 'weeks': d.setDate(d.getDate() + value * 7); break;
    case 'months': d.setMonth(d.getMonth() + value); break;
    case 'years': d.setFullYear(d.getFullYear() + value); break;
  }
  return format(d, 'yyyy-MM-dd');
}

export function getNextDueDate(
  service: { recurrence?: { value: number; unit: 'days' | 'weeks' | 'months' | 'years'; repeatFrom: string } | null },
  lastCompletedDate?: string,
  previousScheduledDate?: string
): string | null {
  if (!service?.recurrence) return null;
  const rec = service.recurrence;
  let baseDate: string;
  if (rec.repeatFrom === 'last-done' && lastCompletedDate) {
    baseDate = lastCompletedDate;
  } else if (rec.repeatFrom === 'fixed-schedule' && previousScheduledDate) {
    baseDate = previousScheduledDate;
  } else {
    baseDate = lastCompletedDate || previousScheduledDate || todayISO();
  }
  return addToDate(baseDate, rec.value, rec.unit);
}
