import { Component, input, output, signal, computed } from '@angular/core';

import {
  formatClock,
  OUTCOME_LABELS,
  OUTCOME_PLURAL,
  STATUS_LABELS,
} from '../../core/orders/order-format';
import { IMPORT_OUTCOMES, type ImportOutcome, type ImportRun } from '../../core/orders/order.types';

const TILE: Record<ImportOutcome, string> = {
  created: 'bg-tile-blue',
  updated: 'bg-tile-indigo',
  unchanged: 'bg-tile-slate',
  duplicate: 'bg-tile-periwinkle',
  rejected: 'bg-tile-coral',
};

@Component({
  selector: 'app-import-report-view',
  standalone: true,
  template: `
    @if (run(); as r) {
      <section aria-labelledby="report-title" class="mx-auto max-w-6xl px-4 pb-12 sm:px-6">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h2 id="report-title" class="scroll-mt-24 text-3xl font-bold tracking-tight sm:text-4xl">
              Результат импорта
            </h2>
            <p class="mt-1.5 text-muted-foreground">
              {{ report().total }} записей · завершён в {{ formatClock(report().finishedAt) }}
            </p>
          </div>
          <button
            type="button"
            aria-label="Скрыть результат"
            class="rounded-full p-2 text-muted-foreground transition-colors hover:bg-secondary hover:text-foreground"
            (click)="close.emit()"
          >
            ✕
          </button>
        </div>

        @if (r.parallel) {
          <div class="mt-5 rounded-2xl bg-primary-soft p-4">
            <p class="text-sm text-primary-soft-foreground">
              Оба запроса ушли одновременно. Второй дождался первого и не создал ни одного дубля.
            </p>
            <div role="tablist" class="mt-3 inline-flex gap-1 rounded-xl bg-card p-1">
              @for (rep of r.reports; track rep.id; let i = $index) {
                <button
                  type="button"
                  role="tab"
                  [attr.aria-selected]="i === activeTab()"
                  class="rounded-lg px-3 py-1.5 text-sm font-semibold transition-colors"
                  [class.bg-primary]="i === activeTab()"
                  [class.text-primary-foreground]="i === activeTab()"
                  [class.text-muted-foreground]="i !== activeTab()"
                  (click)="activeTab.set(i)"
                >
                  Запрос {{ i + 1 }} · создано {{ rep.counts.created }}
                </button>
              }
            </div>
          </div>
        }

        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
          @for (o of outcomes; track o; let i = $index) {
            <button
              type="button"
              [attr.aria-pressed]="outcomeFilter() === o"
              class="relative flex min-h-[112px] flex-col justify-between rounded-3xl p-4 text-left text-tile-foreground transition-transform active:scale-[0.98]"
              [class]="tileClass(o, i)"
              (click)="toggleOutcome(o)"
            >
              <span class="text-base font-medium leading-tight">{{ OUTCOME_PLURAL[o] }}</span>
              <span class="text-4xl font-bold tabular-nums">{{ report().counts[o] }}</span>
            </button>
          }
        </div>

        <div class="mt-6 overflow-hidden rounded-3xl border border-border bg-card">
          <div class="flex items-center justify-between border-b border-border px-4 py-3 sm:px-5">
            <p class="text-sm font-semibold">
              @if (outcomeFilter(); as f) {
                {{ OUTCOME_PLURAL[f] }}: {{ filteredRecords().length }}
              } @else {
                Все записи файла
              }
            </p>
            @if (outcomeFilter()) {
              <button type="button" class="text-sm font-medium text-primary" (click)="outcomeFilter.set(null)">
                Показать все
              </button>
            }
          </div>
          <ol class="divide-y divide-border">
            @for (rec of filteredRecords(); track rec.index) {
              <li class="flex gap-3 px-4 py-3.5 sm:px-5">
                <span
                  class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full bg-secondary text-xs font-semibold text-muted-foreground"
                >
                  {{ rec.index }}
                </span>
                <div class="min-w-0 flex-1">
                  <div class="flex flex-wrap items-center gap-2">
                    <span class="font-semibold">{{ rec.externalId ?? 'без id' }}</span>
                    <span class="rounded-full bg-secondary px-2 py-0.5 text-xs font-semibold">
                      {{ OUTCOME_LABELS[rec.outcome] }}
                    </span>
                    @if (rec.status && rec.outcome !== 'unchanged') {
                      <span class="rounded-full bg-primary-soft px-2 py-0.5 text-xs font-semibold text-primary">
                        {{ STATUS_LABELS[rec.status] }}
                      </span>
                    }
                    @if (rec.needsReview && rec.outcome === 'created') {
                      <span class="rounded-full bg-warning-soft px-2 py-0.5 text-xs font-semibold text-warning-foreground">
                        Нужна проверка
                      </span>
                    }
                  </div>
                  @if (rec.issues.length > 0) {
                    <ul class="mt-1 space-y-0.5">
                      @for (issue of rec.issues; track issue.code + issue.message) {
                        <li
                          class="text-sm leading-snug"
                          [class.text-destructive]="rec.outcome === 'rejected'"
                          [class.text-muted-foreground]="rec.outcome !== 'rejected'"
                        >
                          {{ issue.message }}
                          @if (rec.outcome === 'rejected') {
                            <code class="ml-1.5 rounded bg-danger-soft px-1 py-px text-[11px]">{{ issue.code }}</code>
                          }
                        </li>
                      }
                    </ul>
                  }
                </div>
              </li>
            }
          </ol>
        </div>
      </section>
    }
  `,
})
export class ImportReportViewComponent {
  readonly outcomes = IMPORT_OUTCOMES;
  readonly OUTCOME_LABELS = OUTCOME_LABELS;
  readonly OUTCOME_PLURAL = OUTCOME_PLURAL;
  readonly STATUS_LABELS = STATUS_LABELS;
  readonly formatClock = formatClock;

  run = input<ImportRun | null>(null);
  close = output<void>();

  activeTab = signal(0);
  outcomeFilter = signal<ImportOutcome | null>(null);

  report = computed(() => {
    const r = this.run();
    if (!r || r.reports.length === 0) {
      return { total: 0, finishedAt: new Date().toISOString(), counts: {} as Record<ImportOutcome, number>, records: [] };
    }
    const idx = Math.min(this.activeTab(), r.reports.length - 1);
    return r.reports[idx]!;
  });

  filteredRecords = computed(() => {
    const rep = this.report();
    const f = this.outcomeFilter();
    return f ? rep.records.filter((r) => r.outcome === f) : rep.records;
  });

  tileClass(o: ImportOutcome, index: number): string {
    const base = TILE[o];
    const pressed = this.outcomeFilter() === o;
    const dim = this.outcomeFilter() && !pressed;
    const span = index === 0 ? 'col-span-2 sm:col-span-1' : '';
    const ring = pressed ? 'ring-4 ring-ring/30' : '';
    const opacity = dim ? 'opacity-45' : '';
    return `${base} ${span} ${ring} ${opacity}`.trim();
  }

  toggleOutcome(o: ImportOutcome): void {
    this.outcomeFilter.update((cur) => (cur === o ? null : o));
  }
}
