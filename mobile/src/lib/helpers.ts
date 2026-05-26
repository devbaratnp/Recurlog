export function formatDate(dateString: string): string {
  if (!dateString) return '—';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}

export function formatRelative(dateString: string): string {
  if (!dateString) return '';
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);

  if (diffMins < 1) return 'Just now';
  if (diffMins < 60) return `${diffMins}m ago`;
  if (diffHours < 24) return `${diffHours}h ago`;
  if (diffDays < 7) return `${diffDays}d ago`;
  return formatDate(dateString);
}

export function getStatusColor(status: string): string {
  switch (status) {
    case 'pending': return '#F59E0B';
    case 'completed': return '#1DB954';
    case 'missed': return '#EF4444';
    default: return '#6B7280';
  }
}

export function getStatusBg(status: string): string {
  switch (status) {
    case 'pending': return '#FEF3C7';
    case 'completed': return '#DCFCE7';
    case 'missed': return '#FEE2E2';
    default: return '#F3F4F6';
  }
}

export function todayISO(): string {
  const now = new Date();
  const y = now.getFullYear();
  const m = String(now.getMonth() + 1).padStart(2, '0');
  const d = String(now.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

export function getNotificationIcon(type: string): string {
  switch (type) {
    case 'task_completed': return 'check-circle';
    case 'task_missed': return 'alert-circle';
    case 'service_added': return 'wrench';
    case 'customer_added': return 'person-add';
    default: return 'information-circle';
  }
}

export function getNotificationColor(type: string): string {
  switch (type) {
    case 'task_completed': return '#1DB954';
    case 'task_missed': return '#EF4444';
    case 'service_added': return '#3B82F6';
    case 'customer_added': return '#1DB954';
    default: return '#6B7280';
  }
}

export const serviceTypes = ['RO', 'TV', 'Refrigerator', 'AC', 'Washing Machine', 'Other'];

export function getCategoryColor(service: string): string {
  const map: Record<string, { bg: string; text: string }> = {
    'RO': { bg: '#D1FAE5', text: '#065F46' },
    'TV': { bg: '#DBEAFE', text: '#1E40AF' },
    'Refrigerator': { bg: '#CFFAFE', text: '#155E75' },
    'AC': { bg: '#FFEDD5', text: '#9A3412' },
    'Washing Machine': { bg: '#F3E8FF', text: '#6B21A8' },
    'Other': { bg: '#F3F4F6', text: '#374151' },
  };
  return map[service]?.bg || '#F3F4F6';
}

export function getCategoryTextColor(service: string): string {
  const map: Record<string, { bg: string; text: string }> = {
    'RO': { bg: '#D1FAE5', text: '#065F46' },
    'TV': { bg: '#DBEAFE', text: '#1E40AF' },
    'Refrigerator': { bg: '#CFFAFE', text: '#155E75' },
    'AC': { bg: '#FFEDD5', text: '#9A3412' },
    'Washing Machine': { bg: '#F3E8FF', text: '#6B21A8' },
    'Other': { bg: '#F3F4F6', text: '#374151' },
  };
  return map[service]?.text || '#374151';
}
