import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';

import type { ImportReport, OrdersListResponse, OrderStatus } from './order.types';

@Injectable({ providedIn: 'root' })
export class OrdersApiService {
  private readonly http = inject(HttpClient);

  importOrders(payload: unknown): Promise<ImportReport> {
    return firstValueFrom(this.http.post<ImportReport>('/api/imports', payload));
  }

  listOrders(status?: OrderStatus): Promise<OrdersListResponse> {
    const options = status ? { params: { status } } : {};
    return firstValueFrom(this.http.get<OrdersListResponse>('/api/orders', options));
  }

  reset(): Promise<void> {
    return firstValueFrom(this.http.post<void>('/api/demo/reset', {}));
  }
}
