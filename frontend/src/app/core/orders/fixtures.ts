import marketplaceOrders from '../../../assets/marketplace-orders.json';

export type FixtureVariant = 'original' | 'updated';

const DEMO_STATUS_CHANGES: Record<string, string> = {
  'MP-1001': 'ACCEPTED',
  'MP-1002': 'DONE',
  'MP-1003': 'IN_DELIVERY',
  'MP-1005': 'NEW',
  'MP-1010': 'NEW',
};

function withStatuses(source: typeof marketplaceOrders, changes: Record<string, string>) {
  const copy = structuredClone(source);
  for (const o of copy.orders) {
    const next = changes[o.id];
    if (next) o.status = next;
  }
  return copy;
}

export const FIXTURES: Record<
  FixtureVariant,
  { label: string; hint: string; payload: unknown }
> = {
  original: {
    label: 'Исходный файл',
    hint: 'marketplace-orders.json из задания',
    payload: marketplaceOrders,
  },
  updated: {
    label: 'Новые статусы',
    hint: 'тот же файл, у 5 заказов сменился статус',
    payload: withStatuses(marketplaceOrders, DEMO_STATUS_CHANGES),
  },
};

export const FIXTURE_RECORD_COUNT = marketplaceOrders.orders.length;
