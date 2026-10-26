import { Component, input } from '@angular/core';
import { RouterLink } from '@angular/router';

import {
  describeDelivery,
  describeDeliveryCost,
  formatDateTime,
  formatMoney,
  formatPhone,
  STATUS_FILTER_LABELS,
  STATUS_LABELS,
} from '../../core/orders/order-format';
import { ORDER_STATUSES, type Order, type OrdersListResponse, type OrderStatus } from '../../core/orders/order.types';

@Component({
  selector: 'app-orders-section',
  standalone: true,
  imports: [RouterLink],
  template: `
    <section aria-labelledby="orders-title" class="mx-auto max-w-6xl px-4 pb-24 sm:px-6">
      <h2 id="orders-title" class="text-3xl font-bold tracking-tight sm:text-4xl">Мои заказы</h2>
      <p class="mt-1.5 text-muted-foreground">
        @if (data().counts.all > 0) {
          {{ data().counts.all }} в системе
          @if (data().reviewCount) {
            · {{ data().reviewCount }} нужно проверить
          }
          · время московское
        } @else {
          Здесь появятся заказы после загрузки
        }
      </p>

      @if (data().counts.all > 0) {
        <nav aria-label="Фильтр по статусу" class="no-scrollbar -mx-4 mt-5 overflow-x-auto px-4 sm:mx-0 sm:px-0">
          <ul class="flex w-max gap-2">
            @for (key of filterKeys; track key) {
              <li>
                <a
                  [routerLink]="['/']"
                  [queryParams]="key === 'all' ? { status: null } : { status: key }"
                  [class]="filterLinkClass(key)"
                  [attr.aria-current]="activeFilter() === key ? 'page' : null"
                >
                  {{ STATUS_FILTER_LABELS[key] }}
                  <span
                    class="tabular-nums"
                    [class.opacity-80]="activeFilter() === key"
                    [class.text-muted-foreground]="activeFilter() !== key"
                  >
                    {{ data().counts[key] }}
                  </span>
                </a>
              </li>
            }
          </ul>
        </nav>
      }

      @if (data().counts.all === 0) {
        <div
          class="relative mt-5 flex min-h-[200px] overflow-hidden rounded-3xl bg-tile-slate p-6 text-tile-foreground sm:min-h-[240px] sm:p-8"
        >
          <div class="relative z-10 max-w-[60%]">
            <p class="text-2xl font-semibold leading-tight sm:text-3xl">Заказов пока нет</p>
            <p class="mt-2 text-base opacity-85">Нажмите «Загрузить заказы» — они появятся здесь.</p>
          </div>
          <img
            src="assets/iris-white.png"
            alt=""
            width="768"
            height="1024"
            loading="lazy"
            class="pointer-events-none absolute -bottom-6 right-2 h-[115%] w-auto sm:right-10"
          />
        </div>
      } @else if (data().items.length === 0) {
        <div class="mt-5 rounded-3xl border border-dashed border-border p-8 text-center text-muted-foreground">
          Нет заказов со статусом «{{ status() ? STATUS_LABELS[status()!] : '' }}».
          <a routerLink="/" class="font-medium text-primary">Показать все</a>
        </div>
      } @else {
        <div class="mt-5 grid gap-3 md:hidden">
          @for (o of data().items; track o.externalId) {
            <article class="rounded-3xl border border-border bg-card p-4 shadow-card">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <h3 class="text-lg font-semibold">{{ o.externalId }}</h3>
                  <p class="text-xs text-muted-foreground">создан {{ formatDateTime(o.createdAt) }}</p>
                </div>
                <span class="rounded-full bg-primary-soft px-2 py-0.5 text-sm font-semibold text-primary">
                  {{ STATUS_LABELS[o.status] }}
                </span>
              </div>
              <div class="mt-3 space-y-1.5 text-sm">
                <p class="font-medium">{{ o.customer.name }}</p>
                <a [href]="'tel:' + o.customer.phone" class="text-primary">{{ formatPhone(o.customer.phone) }}</a>
                <p class="text-muted-foreground">{{ describeDelivery(o.delivery) }}</p>
                @if (o.delivery.type === 'delivery') {
                  <p class="text-muted-foreground">{{ o.delivery.address }}</p>
                }
              </div>
              @if (o.needsReview) {
                <p class="mt-3 rounded-xl bg-warning-soft px-3 py-2 text-sm text-warning-foreground">
                  {{ reviewText(o) }}
                </p>
              }
              <ul class="mt-3 space-y-1 text-sm">
                @for (it of o.items; track $index) {
                  <li class="flex justify-between gap-3">
                    <span class="min-w-0 truncate">{{ it.name }}</span>
                    <span class="shrink-0 tabular-nums text-muted-foreground">
                      {{ it.qty }} × {{ formatMoney(it.priceKop) }}
                    </span>
                  </li>
                }
              </ul>
              <dl class="mt-3 space-y-1.5 rounded-2xl bg-muted p-3 text-sm">
                <div class="flex justify-between">
                  <dt class="text-muted-foreground">Товары</dt>
                  <dd class="tabular-nums">{{ formatMoney(o.itemsTotalKop) }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                  <dt class="text-muted-foreground">
                    Доставка
                    <span class="block text-xs">{{ describeDeliveryCost(o.deliveryCost, o.delivery) }}</span>
                  </dt>
                  <dd class="tabular-nums">{{ formatMoney(o.deliveryCost.totalKop) }}</dd>
                </div>
                <div class="flex justify-between border-t border-border pt-2 text-base font-semibold">
                  <dt>Итого</dt>
                  <dd class="tabular-nums">{{ formatMoney(o.grandTotalKop) }}</dd>
                </div>
              </dl>
            </article>
          }
        </div>
        <div class="mt-5 hidden overflow-hidden rounded-3xl border border-border bg-card md:block">
          <table class="w-full text-sm">
            <thead class="bg-muted text-left">
              <tr>
                <th class="py-3 pl-5 font-semibold">Заказ</th>
                <th class="py-3 font-semibold">Покупатель</th>
                <th class="py-3 font-semibold">Доставка</th>
                <th class="py-3 font-semibold">Статус</th>
                <th class="py-3 text-right font-semibold">Товары</th>
                <th class="py-3 text-right font-semibold">Доставка</th>
                <th class="py-3 pr-5 text-right font-semibold">Итого</th>
              </tr>
            </thead>
            <tbody>
              @for (o of data().items; track o.externalId) {
                <tr class="border-t border-border align-top">
                  <td class="py-3.5 pl-5">
                    <div class="font-semibold">{{ o.externalId }}</div>
                    <div class="text-xs text-muted-foreground">{{ formatDateTime(o.createdAt) }}</div>
                    @if (o.needsReview) {
                      <span
                        class="mt-1.5 inline-flex items-center gap-1 rounded-full bg-warning-soft px-2 py-0.5 text-xs font-semibold text-warning-foreground"
                      >
                        Нужна проверка
                      </span>
                    }
                  </td>
                  <td class="py-3.5">
                    <div class="font-medium">{{ o.customer.name }}</div>
                    <div class="text-xs text-muted-foreground">{{ formatPhone(o.customer.phone) }}</div>
                  </td>
                  <td class="max-w-[260px] py-3.5">
                    <div>{{ describeDelivery(o.delivery) }}</div>
                    @if (o.delivery.type === 'delivery') {
                      <div class="truncate text-xs text-muted-foreground">{{ o.delivery.address }}</div>
                    }
                  </td>
                  <td class="py-3.5">
                    <span class="rounded-full bg-primary-soft px-2 py-0.5 text-xs font-semibold text-primary">
                      {{ STATUS_LABELS[o.status] }}
                    </span>
                  </td>
                  <td class="py-3.5 text-right tabular-nums">{{ formatMoney(o.itemsTotalKop) }}</td>
                  <td class="py-3.5 text-right tabular-nums">
                    <div>{{ formatMoney(o.deliveryCost.totalKop) }}</div>
                    <div class="text-xs text-muted-foreground">
                      {{ describeDeliveryCost(o.deliveryCost, o.delivery) }}
                    </div>
                  </td>
                  <td class="py-3.5 pr-5 text-right font-semibold tabular-nums">
                    {{ formatMoney(o.grandTotalKop) }}
                  </td>
                </tr>
              }
            </tbody>
          </table>
        </div>
      }
    </section>
  `,
})
export class OrdersSectionComponent {
  readonly STATUS_LABELS = STATUS_LABELS;
  readonly STATUS_FILTER_LABELS = STATUS_FILTER_LABELS;
  readonly formatDateTime = formatDateTime;
  readonly formatMoney = formatMoney;
  readonly formatPhone = formatPhone;
  readonly describeDelivery = describeDelivery;
  readonly describeDeliveryCost = describeDeliveryCost;

  readonly filterKeys: (OrderStatus | 'all')[] = ['all', ...ORDER_STATUSES];

  data = input.required<OrdersListResponse>();
  status = input<OrderStatus | undefined>();

  activeFilter(): OrderStatus | 'all' {
    return this.status() ?? 'all';
  }

  filterLinkClass(key: OrderStatus | 'all'): string {
    const active = this.activeFilter() === key;
    const base = 'inline-flex h-10 items-center gap-2 rounded-full px-4 text-sm font-semibold transition-colors';
    return active
      ? `${base} bg-primary text-primary-foreground`
      : `${base} bg-secondary text-secondary-foreground hover:bg-primary-soft`;
  }

  reviewText(o: Order): string {
    if (o.declaredTotalKop === null) return 'Нужна проверка: в файле нет суммы';
    return `Нужна проверка: в файле ${formatMoney(o.declaredTotalKop)}, по позициям ${formatMoney(o.itemsTotalKop)}`;
  }
}
