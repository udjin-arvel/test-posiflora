import { Component, input, output } from '@angular/core';

import { FIXTURE_RECORD_COUNT, FIXTURES, type FixtureVariant } from '../../core/orders/fixtures';

@Component({
  selector: 'app-import-hero',
  standalone: true,
  template: `
    <section class="mx-auto max-w-2xl px-4 pt-10 pb-10 text-center sm:px-6 sm:pt-16 sm:pb-14">
      <h1 class="text-[2.6rem] leading-[1.06] font-bold tracking-tight text-balance sm:text-6xl">
        Заказы с маркетплейса
      </h1>

      <div
        class="mt-5 inline-flex items-center gap-1.5 rounded-full bg-primary-soft px-4 py-2 text-base font-medium text-primary-soft-foreground"
      >
        <span aria-hidden="true">JSON</span>
        {{ recordCount }} записей в файле
      </div>

      <p class="mx-auto mt-5 max-w-xl text-left text-lg leading-relaxed text-muted-foreground sm:text-center">
        Загрузите пачку заказов — сохраним их, посчитаем доставку и покажем, что случилось с каждым.
        Повторная загрузка не создаёт дублей.
      </p>

      <div
        role="radiogroup"
        aria-label="Какой файл отправить"
        class="mx-auto mt-7 grid max-w-md grid-cols-2 gap-1 rounded-2xl bg-secondary p-1"
      >
        @for (key of variants; track key) {
          <button
            type="button"
            role="radio"
            [attr.aria-checked]="key === variant()"
            class="cursor-pointer rounded-xl px-3 py-2.5 text-left transition-colors"
            [class.bg-card]="key === variant()"
            [class.shadow-card]="key === variant()"
            [class.text-muted-foreground]="key !== variant()"
            (click)="variantChange.emit(key)"
          >
            <span class="block text-sm font-semibold">{{ fixtures[key].label }}</span>
            <span class="block text-xs leading-snug text-muted-foreground">{{ fixtures[key].hint }}</span>
          </button>
        }
      </div>

      <button
        type="button"
        class="cursor-pointer mt-5 inline-flex h-14 w-full items-center justify-center gap-2 rounded-2xl bg-primary px-8 text-lg font-semibold text-primary-foreground shadow-cta transition-transform active:scale-[0.98] disabled:opacity-60 sm:w-auto sm:min-w-[340px]"
        [disabled]="pending()"
        [attr.aria-busy]="pending()"
        (click)="import.emit()"
      >
        @if (pending()) {
          <span class="inline-block size-5 animate-spin rounded-full border-2 border-primary-foreground/30 border-t-primary-foreground"></span>
          Загружаем…
        } @else {
          Загрузить заказы
        }
      </button>

      <div class="mt-3">
        <button
          type="button"
          class="cursor-pointer text-sm font-medium text-primary disabled:opacity-50"
          [disabled]="pending()"
          (click)="parallelImport.emit()"
        >
          ⚡ Проверить: два импорта одновременно
        </button>
      </div>

      @if (error()) {
        <div
          role="alert"
          class="mx-auto mt-5 flex max-w-md items-start gap-3 rounded-2xl bg-danger-soft p-4 text-left"
        >
          <span class="mt-0.5 text-destructive" aria-hidden="true">!</span>
          <div class="flex-1">
            <p class="font-semibold text-destructive">Не удалось загрузить заказы</p>
            <p class="mt-0.5 text-sm text-muted-foreground">{{ error() }}</p>
          </div>
          <button
            type="button"
            class="rounded-lg p-2 hover:bg-white/50"
            aria-label="Повторить"
            (click)="import.emit()"
          >
            ↻
          </button>
        </div>
      }
    </section>
  `,
})
export class ImportHeroComponent {
  readonly recordCount = FIXTURE_RECORD_COUNT;
  readonly fixtures = FIXTURES;
  readonly variants: FixtureVariant[] = ['original', 'updated'];

  variant = input<FixtureVariant>('original');
  pending = input(false);
  error = input<string | null>(null);

  variantChange = output<FixtureVariant>();
  import = output<void>();
  parallelImport = output<void>();
}
