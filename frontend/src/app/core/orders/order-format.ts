import type { DeliveryCost, DeliveryInfo, ImportOutcome, OrderStatus } from './order.types';

const moneyFmt = new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 2 });

const TARIFF_LABELS: Record<string, string> = {
  centre: 'Центр',
  north: 'Север',
  south: 'Юг',
  suburb: 'Пригород',
};

const dateTimeFmt = new Intl.DateTimeFormat('ru-RU', {
  day: 'numeric',
  month: 'short',
  hour: '2-digit',
  minute: '2-digit',
  timeZone: 'Europe/Moscow',
});

const dayFmt = new Intl.DateTimeFormat('ru-RU', {
  day: 'numeric',
  month: 'short',
  timeZone: 'Europe/Moscow',
});

const timeFmt = new Intl.DateTimeFormat('ru-RU', {
  hour: '2-digit',
  minute: '2-digit',
  second: '2-digit',
});

export function formatMoney(kop: number): string {
  return `${moneyFmt.format(kop / 100)} ₽`;
}

export function formatDay(date: string): string {
  return dayFmt.format(new Date(`${date}T12:00:00+03:00`)).replace('.', '');
}

export function formatDateTime(iso: string | null): string {
  if (!iso) return '—';
  return dateTimeFmt.format(new Date(iso)).replace('.', '');
}

export function formatClock(iso: string): string {
  return timeFmt.format(new Date(iso));
}

export function formatPhone(normalized: string): string {
  const m = /^\+7(\d{3})(\d{3})(\d{2})(\d{2})$/.exec(normalized);
  if (!m) return normalized;
  return `+7 ${m[1]} ${m[2]}-${m[3]}-${m[4]}`;
}

export function districtLabel(code: string): string {
  return TARIFF_LABELS[code] ?? code;
}

export function describeDelivery(d: DeliveryInfo): string {
  if (d.type === 'pickup') return 'Самовывоз';
  return `${districtLabel(d.district)} · ${formatDay(d.date)}, ${d.timeFrom}–${d.timeTo}`;
}

export function describeDeliveryCost(cost: DeliveryCost, d: DeliveryInfo): string {
  if (d.type === 'pickup') return 'самовывоз — бесплатно';
  const parts: string[] = [];
  if (cost.freeApplied && cost.freeFromKop !== null) {
    parts.push(`бесплатно от ${formatMoney(cost.freeFromKop)}`);
  } else {
    parts.push(`район ${formatMoney(cost.baseKop)}`);
  }
  if (cost.urgent) parts.push(`срочно ${formatMoney(cost.urgencyKop)}`);
  return parts.join(' + ');
}

export const STATUS_LABELS: Record<OrderStatus, string> = {
  new: 'Новый',
  accepted: 'Принят',
  delivering: 'В доставке',
  delivered: 'Доставлен',
  cancelled: 'Отменён',
};

export const STATUS_FILTER_LABELS: Record<OrderStatus | 'all', string> = {
  all: 'Все',
  new: 'Новые',
  accepted: 'Приняты',
  delivering: 'В доставке',
  delivered: 'Доставлены',
  cancelled: 'Отменены',
};

export const OUTCOME_LABELS: Record<ImportOutcome, string> = {
  created: 'Создан',
  updated: 'Обновлён',
  unchanged: 'Без изменений',
  duplicate: 'Дубль',
  rejected: 'Отклонён',
};

export const OUTCOME_PLURAL: Record<ImportOutcome, string> = {
  created: 'Создано',
  updated: 'Обновлено',
  unchanged: 'Без изменений',
  duplicate: 'Дубли',
  rejected: 'Отклонено',
};
