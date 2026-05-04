# FileProxy

Шаблон Laravel-сайту для завантаження та керування файлами. Проєкт має публічну головну сторінку з описом, реєстрацію та вхід без пароля за номером телефону з Telegram-кодом, приватний файловий кабінет, папки для групування файлів, Telegram-сховище через групи та перегляд зображень і текстових файлів без скачування.

## Стек

- Laravel 10
- PHP 8.2+
- MySQL або MariaDB
- Composer
- cPanel-хостинг з HTTPS-доменом

## Інструкція розгортання

### 1. Вимоги

Для розгортання на cPanel потрібні:

- PHP 8.2 або новіший;
- Composer на хостингу або можливість завантажити готову папку `vendor`;
- MySQL або MariaDB база даних;
- доступ до Terminal/SSH у cPanel бажаний, але не обов'язковий;
- домен з HTTPS, якщо планується Telegram webhook;
- Telegram-бот для авторизації користувачів;
- Telegram-боти та групи для файлового сховища, якщо файли будуть зберігатися в Telegram.

У PHP Extensions мають бути увімкнені мінімум:

```text
bcmath, ctype, curl, dom, fileinfo, json, mbstring, openssl, pdo_mysql, tokenizer, xml, zip
```

### 2. Підготовка проєкту

Найзручніший варіант для cPanel - розмістити Laravel-проєкт поза `public_html`, а document root домену або піддомену направити в папку `public`.

Приклад структури:

```text
/home/cpanel_user/fileproxy
/home/cpanel_user/fileproxy/app
/home/cpanel_user/fileproxy/public
/home/cpanel_user/fileproxy/storage
```

У cPanel це можна зробити через `Domains` або `Subdomains`, вказавши document root:

```text
/home/cpanel_user/fileproxy/public
```

Якщо хостинг не дозволяє вибрати `public` як document root, тоді Laravel-код залишають поза `public_html`, а в `public_html` кладуть тільки вміст папки `public` і вручну виправляють шляхи в `public_html/index.php` до `vendor/autoload.php` та `bootstrap/app.php`.

Завантажити код можна одним із способів:

- через `Git Version Control` у cPanel;
- через Terminal/SSH командою `git clone`;
- архівом через `File Manager`.

Приклад через Terminal/SSH:

```bash
git clone <repo-url> fileproxy
cd fileproxy
```

Встановіть PHP-залежності:

```bash
composer install --no-dev --optimize-autoloader
```

Якщо Composer на хостингу недоступний, виконайте цю команду локально на тій самій версії PHP, після чого завантажте на хостинг папку `vendor`.

Створіть `.env`:

```bash
cp .env.example .env
```

### 3. База даних у cPanel

У cPanel відкрийте `MySQL Databases` і створіть:

- базу даних, наприклад `cpaneluser_fileproxy`;
- користувача бази, наприклад `cpaneluser_fileproxy_user`;
- пароль користувача;
- прив'яжіть користувача до бази з правами `ALL PRIVILEGES`.

У більшості cPanel-хостингів `DB_HOST` дорівнює `localhost`. Якщо хостинг показує інший host для MySQL, використайте його.

Приклад `.env` для cPanel:

```env
APP_NAME=FileProxy
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cpaneluser_fileproxy
DB_USERNAME=cpaneluser_fileproxy_user
DB_PASSWORD=strong-password

TELEGRAM_BOT_TOKEN=123456:ABCDEF
TELEGRAM_BOT_USERNAME=your_bot_username
TELEGRAM_WEBHOOK_SECRET=random-long-secret
PHONE_AUTH_SHOW_CODE_LOCALLY=false
```

`APP_URL` має бути реальною публічною HTTPS-адресою. Це важливо для Telegram webhook, відкриття публічних посилань і коректної генерації URL.

### 4. Перший запуск

У Terminal/SSH з папки проєкту виконайте:

```bash
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Якщо `storage:link` на cPanel не працює через обмеження symlink, це не критично для Telegram-сховища. Для локального сховища адміністратора може знадобитися дозволити symlink у хостингу або віддавати файли тільки через контролер.

### 5. Права доступу

Папки `storage` і `bootstrap/cache` мають бути доступні для запису PHP-процесу.

Типові права:

```bash
chmod -R 775 storage bootstrap/cache
```

Якщо хостинг працює від вашого cPanel-користувача, зазвичай цього достатньо. Не ставте `777`, якщо хостинг цього прямо не вимагає.

### 6. Налаштування PHP-лімітів

У cPanel відкрийте `MultiPHP INI Editor` або `Select PHP Version` і виставте:

```ini
upload_max_filesize = 55M
post_max_size = 60M
memory_limit = 256M
max_execution_time = 120
max_input_time = 120
```

Для Telegram Bot API максимальний розмір файла через multipart upload - 50 MB, тому більші значення для звичайних користувачів не потрібні.

### 7. Cron

У cPanel відкрийте `Cron Jobs` і додайте Laravel scheduler:

```bash
* * * * * cd /home/cpanel_user/fileproxy && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

Шлях до PHP може відрізнятися. Перевірити його можна в Terminal:

```bash
which php
```

Якщо на хостингу немає Terminal, шлях до PHP зазвичай можна побачити в документації хостингу або в `MultiPHP Manager`.

### 8. Telegram авторизація

Для cPanel краще використовувати webhook, а не polling. Після налаштування `.env` виконайте:

```bash
php artisan optimize:clear
composer dump-autoload -o
php artisan telegram:webhook set
```

Команда автоматично візьме `TELEGRAM_BOT_TOKEN`, `TELEGRAM_WEBHOOK_SECRET` і `APP_URL`, після чого встановить webhook на адресу:

```text
https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/setWebhook?url=https://your-domain.com/api/telegram/webhook/<TELEGRAM_WEBHOOK_SECRET>
```

Перевірити webhook:

```bash
php artisan telegram:webhook info
```

Видалити webhook, якщо потрібно повернутися до polling або перевстановити його:

```bash
php artisan telegram:webhook delete
```

Також можна перевірити напряму в браузері:

```text
https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/getWebhookInfo
```

Якщо `getWebhookInfo` показує помилку, перевірте:

- `APP_URL` починається з `https://`;
- SSL-сертифікат домену активний;
- маршрут `/api/telegram/webhook/<secret>` відкривається з інтернету;
- `TELEGRAM_WEBHOOK_SECRET` у URL збігається зі значенням у `.env`;
- після зміни `.env` виконано `php artisan config:clear` або `php artisan config:cache`.

### 9. Telegram-сховище

Для Telegram-сховища домен також має бути публічним і працювати через HTTPS. Коли користувач додає token свого бота в розділі `Telegram-сховище`, система намагається автоматично встановити webhook для команд сховища.

Порядок підключення сховища:

1. Користувач створює бота через BotFather.
2. Додає token бота на сторінці `Telegram-сховище`.
3. Додає бота в Telegram-групу.
4. Пише в групі команду `/storage`.
5. Група автоматично додається до списку сховищ користувача.

Якщо група не додається, перевірте:

- token бота правильний;
- бот доданий у групу;
- webhook для цього бота встановився без помилок;
- група не забороняє повідомлення від бота.

### 10. Перший адміністратор

Перший зареєстрований користувач автоматично отримує `is_admin=true`, якщо у базі ще немає адміністратора.

Призначити адміністратора вручну можна з Terminal/SSH:

```bash
php artisan user:set-admin +380501234567
```

Зняти права адміністратора:

```bash
php artisan user:set-admin +380501234567 --remove
```

Команда приймає `id`, номер телефону або email користувача.

Якщо Terminal/SSH недоступний, адміністратора можна виставити через phpMyAdmin: у таблиці `users` знайдіть потрібного користувача і встановіть `is_admin = 1`.

### 11. Оновлення проєкту

Типовий порядок оновлення через Terminal/SSH:

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Якщо код оновлюється через архів, перед завантаженням зробіть backup `.env`, `storage` і бази даних.

### 12. Backup і відновлення

Базу даних можна експортувати через `phpMyAdmin` у cPanel або через Terminal:

```bash
mysqldump -u cpaneluser_fileproxy_user -p cpaneluser_fileproxy > backup.sql
```

Відновлення:

```bash
mysql -u cpaneluser_fileproxy_user -p cpaneluser_fileproxy < backup.sql
```

Також варто регулярно зберігати:

- `.env`;
- папку `storage`;
- дамп бази даних.

### 13. Типові проблеми

`500 Server Error`:

- перевірте `storage/logs/laravel.log`;
- переконайтеся, що `APP_KEY` створений;
- перевірте доступи на `storage` і `bootstrap/cache`;
- після зміни `.env` виконайте `php artisan config:clear`.

Сторінка відкриває список файлів Laravel замість сайту:

- document root домену має вести саме в `fileproxy/public`;
- якщо використовується `public_html`, у ньому має бути вміст папки `public`, а не весь Laravel-проєкт.

Файл не завантажується через розмір:

- Laravel-ліміт у коді: 50 MB для Telegram Bot API;
- PHP: `upload_max_filesize=55M`, `post_max_size=60M`;
- перевірте ліміти хостингу на розмір HTTP-запиту.

Помилка `Specified key was too long; max key length is 1000 bytes` під час міграцій:

- це обмеження старих MySQL/MariaDB/InnoDB на деяких cPanel-хостингах;
- у проєкті встановлено `Schema::defaultStringLength(191)`, щоб індекси з `utf8mb4` не перевищували ліміт;
- після оновлення файлів виконайте:

```bash
php artisan optimize:clear
php artisan migrate --force
```

- якщо команда впала посеред міграцій і таблиця була створена частково, видаліть порожню проблемну таблицю в phpMyAdmin і повторіть міграцію.

Telegram webhook не працює:

- перевірте `APP_URL`;
- перевірте HTTPS;
- перевірте `TELEGRAM_WEBHOOK_SECRET`;
- виконайте `getWebhookInfo`;
- переконайтеся, що cPanel не блокує outbound-запити до `api.telegram.org`.

Помилка `Unsupported operand types: string + int` під час `php artisan migrate --force`:

- у проєкті додано захист, який приводить `batch` міграцій до числа перед обчисленням наступного номера;
- якщо помилка все одно з'являється, найчастіше причина в тому, що таблиця `migrations` була створена неправильно або в колонці `batch` є нечислове значення;
- перевірте структуру таблиці в phpMyAdmin:

```sql
SHOW CREATE TABLE migrations;
SELECT id, migration, batch FROM migrations ORDER BY id;
```

- у коректній таблиці `batch` має бути числовою колонкою, наприклад `int`;
- якщо це нове встановлення без важливих даних, найпростіше видалити всі таблиці проєкту і повторно виконати:

```bash
php artisan migrate --force
```

- якщо дані вже є, спочатку зробіть backup бази, після чого виправте колонку:

```sql
UPDATE migrations
SET batch = 1
WHERE batch IS NULL OR batch = '' OR batch REGEXP '[^0-9]';

ALTER TABLE migrations
MODIFY batch INT NOT NULL;
```

## Налаштування файлів і сховища

Файли можуть зберігатися приватно в `storage/app/uploads` або в Telegram-групі, яку користувач підключив у розділі `Telegram-сховище`. Метадані зберігаються в таблиці `managed_files`. Кожен запис має `user_id`, тому користувач бачить і керує тільки власними файлами.

Правила завантаження:

- звичайні користувачі можуть завантажувати файли тільки в Telegram-групу;
- локальне сховище доступне тільки адміністраторам;
- якщо користувач ще не додав власного бота і групу, він може завантажити до 100 файлів у системне Telegram-сховище, якщо адміністратор його налаштував;
- адміністратор може позначити кілька своїх Telegram-груп як системні, і файли користувачів без власного сховища будуть розподілятися між ними;
- максимальний розмір одного файла - 50 MB, відповідно до ліміту Telegram Bot API для multipart upload;
- якщо Telegram-група ще не підключена, відкрийте сторінку `Прив’язка Telegram` з файлового кабінету.

Для Telegram-файлів у базі додатково зберігаються:

- `storage_driver=telegram`
- `telegram_bot_token_id`
- `telegram_storage_group_id`
- `telegram_chat_id`
- `telegram_message_id`
- `telegram_file_id`
- `telegram_file_unique_id`
- повна відповідь Telegram API в `telegram_response`

Під час перегляду або скачування Telegram-файл тимчасово завантажується в `storage/app/telegram-temp`, віддається користувачу і видаляється після відповіді, якщо це inline/download файл.

Папки зберігаються в таблиці `file_folders`. Файл може мати `folder_id` або залишатися в розділі "Без папки". Якщо папку видалити, її файли не видаляються, а переносяться до розділу без папки.

Для файлів з MIME `image/*` або текстових розширень у кабінеті з'являється дія "Переглянути". Зображення відкриваються inline, а текстові файли показуються на окремій сторінці перегляду; для великих текстових файлів відображається перший 1 MB.

Список файлів у кабінеті має два режими: компактна таблиця та плитки. Початково показується 20 файлів, наступні сторінки підвантажуються кнопкою `Завантажити ще`.

## Telegram авторизація

Реєстрація використовує нікнейм, номер телефону у форматі `+380XXXXXXXXX` і 6-значний код з Telegram-бота. Вхід використовує тільки номер телефону та Telegram-код. Пароль користувачу не потрібен. Налаштуйте змінні в `.env`:

```env
TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_BOT_USERNAME=your_bot_username
TELEGRAM_WEBHOOK_SECRET=fileproxy-local-secret
PHONE_AUTH_SHOW_CODE_LOCALLY=false
```

На cPanel використовуйте Telegram webhook:

```bash
php artisan optimize:clear
composer dump-autoload -o
php artisan telegram:webhook set
php artisan telegram:webhook info
```

Якщо `TELEGRAM_BOT_USERNAME` не заданий, форма покаже команду `/start ...`, яку потрібно вручну надіслати боту.
Команда має формат `/start phone_380XXXXXXXXX`; бот знайде активний запит підтвердження для цього номера і згенерує 6-значний код.

Для локальної розробки можна показувати 6-значний код прямо у формі без переходу в Telegram:

```env
PHONE_AUTH_SHOW_CODE_LOCALLY=true
```

Цей режим не працює в `APP_ENV=production`.

## Telegram-сховище файлів

Після входу відкрийте `Telegram-сховище` у верхньому меню файлового кабінету.

1. Додайте один або кілька bot token.
2. Додайте бота в Telegram-групу, яка буде сховищем файлів.
3. Напишіть у групі команду `/storage`.
4. Група автоматично з'явиться у списку сховищ користувача.
5. У формі завантаження файлів виберіть потрібну Telegram-групу в полі `Сховище`.

Якщо Telegram-групу не вибрано, файл зберігається локально в Laravel storage тільки для адміністратора.

У розділі `Telegram-сховище` можна окремо змінювати основного бота і основну групу кнопками `Основний` / `Основна`. Основна група автоматично підставляється у формі завантаження файлів.

Для звичайних користувачів Telegram-група обов'язкова. Локальне сховище доступне лише адміністраторам.

Адміністратор може зробити власну групу системною кнопкою `Системна` на сторінці `Telegram-сховище`. Системних груп може бути кілька; FileProxy бере їх по черзі для користувачів, які ще не мають власного Telegram-сховища. Для такого режиму діє ліміт 100 файлів на користувача.

Сторінка з інструкцією підключення:

```text
https://your-domain.com/telegram/setup
```

## Адмінка

У користувачів є прапорці `is_admin` та `is_blocked`. Перший користувач автоматично отримує `is_admin=true`, якщо в системі ще немає адміністратора.

Адмінка доступна за адресою:

```text
https://your-domain.com/admin
```

Адміністратор може:

- переглядати список користувачів;
- фільтрувати активних і заблокованих користувачів;
- відкривати сторінку користувача зі списком його файлів та Telegram-метаданими;
- блокувати і розблоковувати користувачів.

Заблокований користувач не може увійти, а якщо він уже був авторизований, приватні маршрути викинуть його на сторінку входу.

Призначити або зняти адміністратора можна командою:

```bash
php artisan user:set-admin +380501234567
php artisan user:set-admin +380501234567 --remove
```

Команда приймає `id`, номер телефону або email користувача.
