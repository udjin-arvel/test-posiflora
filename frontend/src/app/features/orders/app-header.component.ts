import { Component, input, output, signal } from '@angular/core';

@Component({
  selector: 'app-header',
  standalone: true,
  template: `
    <header class="sticky top-0 z-30 bg-ink">
      <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:h-[72px] sm:px-6">
        <div class="flex items-center gap-2.5">
          <svg viewBox="0 0 32 32" class="size-8 -rotate-90" aria-hidden="true">
            <circle
              cx="16"
              cy="16"
              r="11"
              fill="none"
              stroke-width="5"
              pathLength="100"
              class="stroke-brand-teal"
              stroke-dasharray="46 54"
            />
            <circle
              cx="16"
              cy="16"
              r="11"
              fill="none"
              stroke-width="5"
              pathLength="100"
              class="stroke-brand-coral"
              stroke-dasharray="24 76"
              stroke-dashoffset="-50"
            />
            <circle
              cx="16"
              cy="16"
              r="11"
              fill="none"
              stroke-width="5"
              pathLength="100"
              class="stroke-brand-sun"
              stroke-dasharray="16 84"
              stroke-dashoffset="-78"
            />
          </svg>
          <span class="text-lg font-semibold tracking-[0.28em] text-ink-foreground">ФЛОРА</span>
        </div>

        @if (dialogOpen()) {
          <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="reset-title"
          >
            <div class="w-full max-w-md rounded-3xl bg-card p-6 shadow-card">
              <h2 id="reset-title" class="text-lg font-semibold">Удалить все загруженные заказы?</h2>
              <p class="mt-2 text-sm text-muted-foreground">
                Список очистится, и импорт можно будет пройти с нуля. Это нужно только для проверки
                сценариев.
              </p>
              <div class="mt-6 flex justify-end gap-2">
                <button
                  type="button"
                  class="rounded-xl px-4 py-2 text-sm font-semibold hover:bg-secondary"
                  (click)="dialogOpen.set(false)"
                >
                  Отмена
                </button>
                <button
                  type="button"
                  class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground"
                  (click)="confirmReset()"
                >
                  Удалить
                </button>
              </div>
            </div>
          </div>
        }

        <button
          type="button"
          class="inline-flex h-10 items-center gap-2 rounded-full bg-white/10 px-4 text-sm font-semibold text-ink-foreground transition-colors hover:bg-white/15 disabled:opacity-40"
          [disabled]="!canReset()"
          (click)="dialogOpen.set(true)"
        >
          <span aria-hidden="true">↺</span>
          Сбросить
        </button>
      </div>
    </header>
  `,
})
export class AppHeaderComponent {
  canReset = input(false);
  reset = output<void>();

  dialogOpen = signal(false);

  confirmReset(): void {
    this.dialogOpen.set(false);
    this.reset.emit();
  }
}
