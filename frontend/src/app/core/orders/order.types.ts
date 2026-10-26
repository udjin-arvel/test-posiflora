export const ORDER_STATUSES = ['new', 'accepted', 'delivering', 'delivered', 'cancelled'] as const;
export type OrderStatus = (typeof ORDER_STATUSES)[number];

export const IMPORT_OUTCOMES = ['created', 'updated', 'unchanged', 'duplicate', 'rejected'] as const;
export type ImportOutcome = (typeof IMPORT_OUTCOMES)[number];

export interface ImportIssue {
  code: string;
  message: string;
}

export type DeliveryInfo =
  | { type: 'pickup' }
  | {
      type: 'delivery';
      district: string;
      address: string;
      date: string;
      timeFrom: string;
      timeTo: string;
    };

export interface DeliveryCost {
  baseKop: number;
  freeFromKop: number | null;
  freeApplied: boolean;
  urgent: boolean;
  urgencyKop: number;
  totalKop: number;
}

export interface OrderItem {
  sku: string | null;
  name: string;
  qty: number;
  priceKop: number;
}

export interface Order {
  shopId: string;
  externalId: string;
  status: OrderStatus;
  createdAt: string | null;
  customer: { name: string; phone: string };
  delivery: DeliveryInfo;
  items: OrderItem[];
  itemsTotalKop: number;
  declaredTotalKop: number | null;
  needsReview: boolean;
  deliveryCost: DeliveryCost;
  grandTotalKop: number;
  importedAt: string;
  statusUpdatedAt: string;
}

export interface ImportRecordResult {
  index: number;
  externalId: string | null;
  outcome: ImportOutcome;
  issues: ImportIssue[];
  status?: OrderStatus;
  previousStatus?: OrderStatus;
  needsReview?: boolean;
}

export interface ImportReport {
  id: string;
  finishedAt: string;
  total: number;
  counts: Record<ImportOutcome, number>;
  records: ImportRecordResult[];
}

export interface OrdersListResponse {
  items: Order[];
  counts: Record<OrderStatus | 'all', number>;
  reviewCount: number;
}

export interface ImportRun {
  reports: ImportReport[];
  parallel: boolean;
}

export function emptyOrdersList(): OrdersListResponse {
  const counts = {
    all: 0,
    new: 0,
    accepted: 0,
    delivering: 0,
    delivered: 0,
    cancelled: 0,
  };
  return { items: [], counts, reviewCount: 0 };
}

export function isOrderStatus(value: string | null | undefined): value is OrderStatus {
  return value != null && (ORDER_STATUSES as readonly string[]).includes(value);
}
