# Импорт заказов маркетплейса (тестовое на должность Senior Fullstack компании Посифлора ([https://posiflora.com](https://posiflora.com/)))

Мини-модуль для цветочного магазина: загрузка пачки заказов из фикстуры, расчёт доставки, список без дублей через идемподентность. Стек: **Symfony** (API), **Angular** (UI), **MySQL 8**.

Решения и допущения — в [DECISIONS.md](DECISIONS.md).

## Требования

- [Docker](https://www.docker.com/) с Docker Compose

## Запуск

Из корня репозитория:

```bash
docker compose up --build
```

Первый запуск может занять несколько минут: собираются образы nginx/php, ставятся зависимости Composer, собирается фронтенд, накатываются миграции на `posiflora` и `posiflora_test`.

Приложение: **http://localhost:8080**

- Кнопка «Загрузить заказы» отправляет `marketplace-orders.json` с фронта.
- «Сбросить» очищает заказы демо-магазина (`SHOP_ID=demo`).

Остановка: `Ctrl+C` или `docker compose down` (данные MySQL сохраняются в volume `db_data`).

## Тесты

PHPUnit, только внутри контейнера `php` (нужна поднятая БД):

```bash
docker compose exec php php bin/phpunit
```

Интеграционные тесты используют базу `posiflora_test`. Она создаётся при старте `php` (см. `docker/php/entrypoint.sh` и `backend/bin/ensure-test-database.php`).

Проверка API вручную:

```bash
curl http://localhost:8080/api/health
```

## Структура

| Путь | Назначение |
|------|------------|
| `backend/` | Symfony, API `/api/*` |
| `frontend/` | Angular (в production отдаётся через nginx) |
| `compose.yaml` | MySQL, php-fpm, nginx |
| `DECISIONS.md` | журнал решений по заданию |
