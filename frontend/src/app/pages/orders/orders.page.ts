import { Component, inject, signal, computed } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { ActivatedRoute } from '@angular/router';
import { map } from 'rxjs';

import { FIXTURES, type FixtureVariant } from '../../core/orders/fixtures';
import { OrdersApiService } from '../../core/orders/orders-api.service';
import {
  emptyOrdersList,
  isOrderStatus,
  type ImportRun,
  type OrdersListResponse,
  type OrderStatus,
} from '../../core/orders/order.types';
import { AppHeaderComponent } from '../../features/orders/app-header.component';
import { ImportHeroComponent } from '../../features/orders/import-hero.component';
import { ImportReportViewComponent } from '../../features/orders/import-report-view.component';
import { OrdersSectionComponent } from '../../features/orders/orders-section.component';

@Component({
  selector: 'app-orders-page',
  standalone: true,
  imports: [
    AppHeaderComponent,
    ImportHeroComponent,
    ImportReportViewComponent,
    OrdersSectionComponent,
  ],
  template: `
    <div class="min-h-screen bg-background">
      <app-header [canReset]="canReset()" (reset)="onReset()" />
      <main>
        <app-import-hero
          [variant]="variant()"
          [pending]="importPending()"
          [error]="importError()"
          (variantChange)="variant.set($event)"
          (import)="runImport(false)"
          (parallelImport)="runImport(true)"
        />
        @if (importRun()) {
          <app-import-report-view [run]="importRun()" (close)="importRun.set(null)" />
        }
        <app-orders-section [data]="ordersData()" [status]="statusFilter()" />
      </main>
    </div>
  `,
})
export class OrdersPage {
  private readonly api = inject(OrdersApiService);
  private readonly route = inject(ActivatedRoute);
  variant = signal<FixtureVariant>('original');
  importPending = signal(false);
  importError = signal<string | null>(null);
  importRun = signal<ImportRun | null>(null);
  ordersData = signal<OrdersListResponse>(emptyOrdersList());

  private readonly statusFromRoute = toSignal(
    this.route.queryParamMap.pipe(
      map((params) => {
        const raw = params.get('status');
        return isOrderStatus(raw) ? raw : undefined;
      }),
    ),
    { initialValue: undefined as OrderStatus | undefined },
  );

  statusFilter = computed(() => this.statusFromRoute());

  canReset = computed(() => this.ordersData().counts.all > 0 || this.importRun() !== null);

  constructor() {
    void this.reloadOrders();
    this.route.queryParamMap.subscribe(() => void this.reloadOrders());
  }

  async runImport(parallel: boolean): Promise<void> {
    this.importPending.set(true);
    this.importError.set(null);
    try {
      const payload = FIXTURES[this.variant()].payload;
      const reports = parallel
        ? await Promise.all([this.api.importOrders(payload), this.api.importOrders(payload)])
        : [await this.api.importOrders(payload)];
      this.importRun.set({ reports, parallel });
      await this.reloadOrders();
      requestAnimationFrame(() =>
        document.getElementById('report-title')?.scrollIntoView({ behavior: 'smooth', block: 'start' }),
      );
    } catch {
      this.importError.set('Сервер не ответил. Проверьте соединение и попробуйте ещё раз.');
    } finally {
      this.importPending.set(false);
    }
  }

  async onReset(): Promise<void> {
    try {
      await this.api.reset();
      this.importRun.set(null);
      this.importError.set(null);
      await this.reloadOrders();
    } catch {
      this.importError.set('Сервер не ответил. Проверьте соединение и попробуйте ещё раз.');
    }
  }

  private async reloadOrders(): Promise<void> {
    try {
      const status = this.statusFilter();
      this.ordersData.set(await this.api.listOrders(status));
    } catch {
      this.ordersData.set(emptyOrdersList());
    }
  }
}
