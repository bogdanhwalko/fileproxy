# Lemon Squeezy — налаштування платних файлів

Платні файли реалізовані через Lemon Squeezy (Merchant of Record — вони беруть на себе податки, VAT, повернення).

## 1. Запустити міграції

```bash
php artisan migrate
```

Створить нові поля в `managed_files` (`is_paid`, `price_cents`, `currency`, `purchase_max_downloads`, `purchase_access_hours`) і нову таблицю `file_purchases`.

## 2. Створити акаунт на Lemon Squeezy

1. Зареєструватись на https://app.lemonsqueezy.com — приймають з України.
2. Створити **Store** (Settings → Stores → New Store).
   - Currency — рекомендую USD, конвертація на інші валюти автоматична.
   - Заповнити tax info, payout method (Wise — найзручніше для виплат в Україну).
3. Зайти у режим **Test Mode** (вмикається перемикачем у правому верхньому куті) на час налаштування — це окремі тестові ключі і "фейкові" платежі.

## 3. Створити Product + Variant

1. Products → New Product → **Single Payment**.
2. Назва: `File Access` (службова, покупець бачить назву файлу окремо).
3. Створити один Variant у цьому продукті:
   - Price: будь-яка (наприклад `1.00 USD`) — реальна ціна для кожного файлу задається динамічно через API параметр `custom_price`.
   - У налаштуваннях Variant увімкнути **"Custom price"** (Yes / Pay-what-you-want) — це обовʼязково, щоб API міг переписувати ціну.
4. Запам'ятати **Store ID** і **Variant ID** (вони цифрові — видно в URL у адресній стрічці на відповідних сторінках адмінки).

## 4. Створити API Key і Webhook

1. **API Key**: Settings → API → Create API Key → скопіювати (відображається один раз).
2. **Webhook**: Settings → Webhooks → New Webhook:
   - Callback URL: `https://fileproxy.online/webhooks/lemon-squeezy`
   - Secret: створити сильний рядок (наприклад `openssl rand -hex 32`), зберегти.
   - Events: **`order_created`** і **`order_refunded`** (мінімум). Можна додати `order_paid`, але `order_created` вже спрацьовує після успішного списання.

## 5. Додати ключі в `.env`

```env
LEMON_SQUEEZY_API_KEY=eyJ0eXAi...                # API Key з кроку 4.1
LEMON_SQUEEZY_STORE_ID=123456                    # цифровий ID магазину
LEMON_SQUEEZY_VARIANT_ID=789012                  # цифровий ID variant
LEMON_SQUEEZY_SIGNING_SECRET=ваш_secret          # з кроку 4.2
LEMON_SQUEEZY_TEST_MODE=true                     # true поки тестуєте, потім false
```

Після зміни `.env`:

```bash
php artisan config:clear
```

## 6. Тестова покупка (Test Mode)

1. У файлах (`/files`) відкрити меню "Дії" біля будь-якого файлу.
2. Створити публічний лінк (`Створити лінк`).
3. У блоці налаштувань увімкнути **"Зробити файл платним"**, вказати ціну (наприклад `0.99`), натиснути **"Зберегти налаштування"**.
4. Відкрити публічний лінк у вкладці інкогніто — побачите paywall сторінку.
5. Натиснути **"Купити за …"** — відкриється чекаут Lemon Squeezy.
6. Використати тестову картку Lemon: `4242 4242 4242 4242`, будь-який майбутній термін, будь-який CVC.
7. Після оплати — автоматичний редірект на сторінку очікування, потім на сторінку файла з кнопкою "Скачати".

## 7. Локальне тестування webhook (опційно)

Якщо запускаєте локально на `localhost` — Lemon не зможе достукатися до webhook. Варіанти:

**Варіант A — ngrok**
```bash
ngrok http 8000
```
Скопіювати HTTPS URL з ngrok (наприклад `https://abcd-1234.ngrok-free.app`) → у налаштуваннях webhook у Lemon вказати `https://abcd-1234.ngrok-free.app/webhooks/lemon-squeezy`.

**Варіант B** — задеплоїти одразу на сервер і тестувати там.

Поки webhook не дійшов — користувач бачить сторінку "Обробляємо ваш платіж…" з лічильником, який пінгує `share.access.status` кожні 3 секунди.

## 8. Перехід у production

1. Вимкнути Test Mode у Lemon Squeezy (перемикач у правому верхньому куті).
2. У production створити **окремий** API Key і webhook (тестові і реальні живуть окремо).
3. Оновити `.env`:
   ```env
   LEMON_SQUEEZY_TEST_MODE=false
   LEMON_SQUEEZY_API_KEY=...                # production key
   LEMON_SQUEEZY_SIGNING_SECRET=...         # production secret
   LEMON_SQUEEZY_STORE_ID=...               # той же магазин
   LEMON_SQUEEZY_VARIANT_ID=...             # production Variant (потрібно створити заново в production режимі або переключити Test → Live для існуючого, якщо це той самий продукт)
   ```
4. `php artisan config:clear`.

## 9. Як це працює (для розуміння)

```
Власник файлу → налаштовує ціну в "Дії"
                ↓
Покупець відкриває publi-лінк → редірект на /share/files/{token}/buy (paywall)
                ↓
Натискає "Купити"   → POST /share/files/{token}/buy
                ↓
Створюється FilePurchase (status=pending, access_token) → Checkout API запит
                ↓
Редірект покупця на Lemon Squeezy сторінку оплати
                ↓
Оплата успішна → Lemon шле webhook order_created → /webhooks/lemon-squeezy
                  ↓
                  Перевіряється підпис → FilePurchase оновлюється на status=paid
                ↓
Lemon одночасно редіректить покупця на /share/access/{access_token} (processing)
                ↓
Сторінка polling → status=paid → редірект на /share/access/{access_token}/view
                ↓
Покупець бачить файл + кнопку Скачати/Прямий лінк
                ↓
На скачуванні інкрементиться downloads_count, якщо є ліміт — після вичерпання 410
```

## 10. Що зберігається в БД

**`managed_files`** — нові колонки:
- `is_paid` (boolean)
- `price_cents` (int, в копійках/центах)
- `currency` (USD / EUR)
- `purchase_max_downloads` (nullable — null = без ліміту)
- `purchase_access_hours` (nullable — null = безстроково)

**`file_purchases`** — кожна спроба покупки створює запис:
- `access_token` (унікальний 48-символьний — для URL після оплати)
- `status` (pending / paid / refunded)
- `buyer_email` (приходить з Lemon, можете використовувати для маркетингу / повторних звʼязків)
- `lemon_order_id` (для звірки з Lemon dashboard)
- `amount_cents`, `currency`
- `downloads_count`, `max_downloads`, `expires_at`
- `paid_at`, timestamps

## 11. Поширені питання

**Що з повернен­нями?** Lemon має кнопку "Refund" у dashboard. Коли натискаєте — приходить webhook `order_refunded` → ми ставимо `status=refunded` → доступ закривається.

**Чи бачить покупець мій email?** Так, Lemon показує email магазину в чеках. Можна налаштувати "store contact" окремо (Settings → Store).

**Комісія?** ~5% + $0.50 з кожної транзакції (станом на 2026). Lemon показує точну розбивку для кожного замовлення.

**Виплати на українську картку?** Прямо — ні. Lemon виплачує через Wise / Payoneer / банк (SWIFT). Wise — найдешевший шлях.

**Як перевірити підпис webhook вручну?** `hash_hmac('sha256', $rawBody, $secret)` має дорівнювати заголовку `X-Signature`. Реалізовано в `LemonSqueezyService::verifySignature()`.

## 12. Файли коду (для довідки)

- `app/Services/LemonSqueezyService.php` — створення чекаутів + перевірка підписів
- `app/Http/Controllers/FilePurchaseController.php` — paywall, checkout, processing, доступ до купленого файла
- `app/Http/Controllers/LemonSqueezyWebhookController.php` — обробка webhook'ів від Lemon
- `app/Models/FilePurchase.php` — модель покупки
- `resources/views/share/paywall.blade.php` — сторінка оплати
- `resources/views/share/processing.blade.php` — "Обробляємо платіж…"
- `resources/views/share/access.blade.php` — сторінка купленого файла
- `public/css/paywall.css` — стилі paywall
