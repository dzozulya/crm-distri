# CRM Lead Distribution

Тестове завдання для автоматичного розподілу лідів між активними менеджерами.

## Стек

- PHP 8.4
- Laravel 11+
- PostgreSQL 17
- Redis 7
- Docker Compose
- PHPUnit / Pest

## Функціональність

Реалізовано API:

```http
POST /api/leads/distribute
```

Endpoint розподіляє всі нові (`NEW`) ліди між активними менеджерами.

Під час розподілу враховується поточне навантаження менеджера:

- `NEW`
- `IN_PROGRESS`

Неактивні менеджери не беруть участі в розподілі.

Для кожного розподіленого ліда:

1. призначається менеджер;
2. статус змінюється на `IN_PROGRESS`;
3. створюється запис в історії;
4. генерується `LeadAssigned`;
5. listener ставить Job у Redis Queue;
6. queue worker обробляє Job.

Повторний виклик API не обробляє вже розподілені ліди.

## Запуск

Клонувати репозиторій та перейти до його каталогу:

```bash
git clone <repository-url>
cd <project-directory>
```

Створити `.env`:

```bash
cp .env.example .env
```

Основні параметри:

```dotenv
APP_NAME=CRM
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=crm
DB_USERNAME=crm
DB_PASSWORD=crm

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

QUEUE_CONNECTION=redis
```

Збудувати  та запустити контейнери:

```bash
docker compose build --no-cache
docker compose up -d
```

Встановити залежності Composer

````bash
docker compose exec app composer install
````

Створити application key:

```bash
docker compose exec app php artisan key:generate
```

Запустити міграції та seed:

```bash
docker compose exec app php artisan migrate --seed
```

API буде доступний за адресою:

```text
http://localhost:8080
```

## Перевірка

Перевірити список маршрутів:

```bash
docker compose exec app php artisan route:list
```

Очікуваний endpoint:

```text
POST  api/leads/distribute
```

Виклик API:

```bash
curl -X POST http://localhost:8080/api/leads/distribute
```

При успішному розподілі:

```json
{
    "distributed": 9
}
```

Якщо нових лідів немає або всі вони вже розподілені:

```json
{
    "distributed": 0
}
```

## Queue

Queue worker запускається окремим Docker-контейнером:

```yaml
command: php artisan queue:work redis --sleep=3 --tries=3
```

Перевірити його стан:

```bash
docker compose ps
```

Перевірити Redis:

```bash
docker compose exec redis redis-cli ping
```

Очікувана відповідь:

```text
PONG
```

Логи worker:


## Архітектура

Основний flow:

```text
POST /api/leads/distribute
            |
            v
LeadDistributionController
            |
            v
LeadDistributionService
            |
            v
DistributionStrategy
            |
            v
LeastLoadedStrategy
            |
            v
PostgreSQL transaction
       /           \
      v             v
   Lead        LeadHistory
      |
      v
LeadAssigned event
      |
      v
QueueLeadAssignedNotification
      |
      v
SendLeadAssignedNotification
      |
      v
     Redis
      |
      v
 queue worker
```

### Основні компоненти

```text
app/
├── Domain/
│   └── LeadDistribution/
│       ├── Contracts/
│       │   └── DistributionStrategy.php
│       ├── Strategies/
│       │   └── LeastLoadedStrategy.php
│       └── ManagerLoad.php
│
├── Enums/
│   └── LeadStatus.php
│
├── Events/
│   └── LeadAssigned.php
│
├── Jobs/
│   └── SendLeadAssignedNotification.php
│
├── Listeners/
│   └── QueueLeadAssignedNotification.php
│
├── Http/
│   ├── Controllers/
│   │   └── LeadDistributionController.php
│   └── Requests/
│       └── DistributeLeadsRequest.php
│
├── Models/
│   ├── Manager.php
│   ├── Lead.php
│   └── LeadHistory.php
│
├── Providers/
│   └── LeadDistributionServiceProvider.php
│
└── Services/
    └── LeadDistributionService.php
```

## Алгоритм розподілу

Використовується алгоритм **Least Loaded**.

Для кожного активного менеджера визначається поточне навантаження:

```text
load = NEW + IN_PROGRESS
```

Новий лід передається менеджеру з найменшим навантаженням.

Після призначення ліда його навантаження збільшується в пам'яті алгоритму. Таким чином, наступний лід може бути призначений іншому менеджеру.

Для однакового навантаження використовується `manager_id` як стабільний tie-breaker.

Наприклад:

```text
Manager A = 12
Manager B = 4
Manager C = 8
```

При 9 нових лідах очікується:

```text
Manager A = 12
Manager B = 11
Manager C = 10
```

## Strategy Pattern

Алгоритм розподілу винесений за інтерфейс:

```php
interface DistributionStrategy
{
    public function distribute(
        Collection $managers,
        Collection $leadIds,
    ): array;
}
```

Конкретна стратегія:

```php
LeastLoadedStrategy
```

`LeadDistributionService` залежить від абстракції `DistributionStrategy`, а не від конкретного алгоритму.

Binding виконується у:

```text
app/Providers/LeadDistributionServiceProvider.php
```

```php
$this->app->bind(
    DistributionStrategy::class,
    LeastLoadedStrategy::class,
);
```

Тому для додавання, наприклад, `RoundRobinStrategy` достатньо створити нову реалізацію:

```text
app/Domain/LeadDistribution/Strategies/RoundRobinStrategy.php
```

і змінити binding:

```php
$this->app->bind(
    DistributionStrategy::class,
    RoundRobinStrategy::class,
);
```
### Репозиторії

Роботу з persistence layer винесено в окремі репозиторії:

* `LeadRepository`
* `ManagerRepository`
* `LeadHistoryRepository`

Сервіс розподілу лідів не працює з Eloquent безпосередньо. Він залежить від інтерфейсів репозиторіїв:

```text
LeadDistributionService
        |
        +-- LeadRepositoryInterface
        +-- ManagerRepositoryInterface
        +-- LeadHistoryRepositoryInterface
        |
        +-- DistributionStrategy
```

Конкретні реалізації репозиторіїв використовують Eloquent та реєструються в Laravel Container через Dependency Injection.

Таке розділення дозволяє ізолювати persistence layer від бізнес-логіки та за необхідності замінити реалізацію репозиторію без зміни `LeadDistributionService`.

Наприклад, `LeadRepositoryInterface` може бути реалізований не тільки через Eloquent, але й через інший ORM, raw SQL або зовнішнє джерело даних:

```php
$this->app->bind(
    LeadRepositoryInterface::class,
    LeadRepository::class,
);
```

Таким чином, `LeadDistributionService` залежить від абстракції, а не від конкретної реалізації persistence layer, що відповідає принципу Dependency Inversion Principle.

Репозиторії містять лише операції доступу та зміни даних. Правила розподілу лідів, вибір менеджера та алгоритм балансування залишаються у service/domain layer.


Controller та `LeadDistributionService` при цьому не змінюються.

Це дозволяє дотримуватися Strategy Pattern, Dependency Inversion Principle та Open/Closed Principle.

## PostgreSQL та конкурентний доступ

Для вибірки нових лідів використовується:

```sql
FOR UPDATE SKIP LOCKED
```

Це дозволяє уникати повторної обробки одних і тих самих лідів при паралельних запитах до endpoint.

Основна операція розподілу виконується в PostgreSQL transaction.

Зміна ліда та запис в `lead_histories` виконуються атомарно.

Event створюється після успішного commit транзакції, щоб не відправляти подію для операції, яка була відкотилася.

`SKIP LOCKED` не гарантує ідеально глобального балансування при великій кількості одночасних distributor-процесів. Для високого навантаження можливі додаткові механізми синхронізації, наприклад PostgreSQL advisory lock або централізована черга розподілу.

Для поточного завдання використаний простіший варіант.

## Індекси

Для пошуку нерозподілених нових лідів створено partial index:

```sql
CREATE INDEX idx_leads_unassigned_new
ON leads (id)
WHERE status = 'NEW'
  AND manager_id IS NULL;
```

Для підрахунку відкритих лідів менеджера:

```sql
CREATE INDEX idx_leads_manager_open
ON leads (manager_id)
WHERE status IN ('NEW', 'IN_PROGRESS');
```

Partial indexes зменшують обсяг індексованих даних та дозволяють PostgreSQL ефективніше виконувати запити, пов'язані з розподілом.

## Історія

Для кожної зміни під час автоматичного розподілу створюється запис у:

```text
lead_histories
```

Зберігаються:

- lead;
- попередній менеджер;
- новий менеджер;
- попередній статус;
- новий статус;
- timestamp зміни.

Це дозволяє мати audit trail розподілу.

## Raw SQL звіт

Завдання також містить статистичний SQL-запит без використання ORM.

Запит знаходиться у:

```text
database/sql/manager_statistics.sql
```

Він повертає:

- менеджера;
- кількість відкритих лідів;
- середній час у роботі;
- кількість завершених лідів за останні 30 днів.

Запит використовує PostgreSQL `FILTER` та `EXTRACT`.

### Обмеження розрахунку часу

У поточній моделі немає окремих `started_at` та `completed_at`.

Тому точний час перебування ліда в `IN_PROGRESS` неможливо визначити лише з `updated_at`.

Поточний розрахунок є наближеним. Для production-рішення доцільно використовувати історію переходів статусів або окремі timestamps:

```text
started_at
completed_at
```

## Тестування

Запустити всі тести:

```bash
docker compose exec app php artisan test
```



Unit-тест не використовує базу даних, оскільки перевіряє чисту бізнес-логіку алгоритму.

Feature-тести перевіряють інтеграцію з Laravel, базою даних, event та queue.


## Повторний виклик

Після успішного розподілу всі оброблені ліди мають:

```text
status = IN_PROGRESS
manager_id IS NOT NULL
```

Тому повторний:

```http
POST /api/leads/distribute
```

не обробляє їх повторно.

При відсутності нових лідів API повертає:

```json
{
    "distributed": 0
}
```

## Технічні рішення та trade-offs

### Чому Eloquent використовується для основного domain flow

Eloquent зручний для моделей, relationships та транзакційного application flow.

При цьому вимога щодо SQL без ORM виконана окремим raw SQL-запитом для статистичного звіту.

### Чому не використовується Redis для самого алгоритму

Redis використовується як Queue backend.

Поточний алгоритм отримує актуальне навантаження безпосередньо з PostgreSQL, що є source of truth для leads.

Винесення counters у Redis може бути корисним при дуже високому навантаженні, але додає проблему синхронізації між Redis та PostgreSQL.

### Чому немає складної мікросервісної інфраструктури

Завдання виконується як Laravel application з окремим queue worker.

Такий підхід достатній для поточного навантаження та залишає можливість подальшого горизонтального масштабування.

## Docker

Сервіси:

```text
app       Laravel + PHP-FPM
nginx     HTTP server
postgres  PostgreSQL
redis     Redis
queue     Laravel queue worker
```

Запуск:

```bash
docker compose up -d
```

Зупинка:

```bash
docker compose down
```

Перегляд контейнерів:

```bash
docker compose ps
```

Перегляд логів application:

```bash
docker compose logs -f app
```

Перегляд логів queue:

```bash
docker compose logs -f queue
```

## API приклад

Request:

```http
POST /api/leads/distribute
```

Body не потрібен.

Response:

```json
{
    "distributed": 9
}
```

## Postman

Для ручної перевірки можна використовувати Postman або будь-який HTTP client.
Postman коллекція знаходится в корні проекту
```text
crm-disribution.postman_collection.json
```

Request:

```text
POST http://localhost:8080/api/leads/distribute
```

Headers:

```text
Accept: application/json
```

Body:

```text
none
```

## Структура бази даних

### managers

```text
id
name
is_active
created_at
updated_at
```

### leads

```text
id
manager_id
status
created_at
updated_at
```

### lead_histories

```text
id
lead_id
old_manager_id
new_manager_id
old_status
new_status
created_at
```

## Підсумок

Рішення побудоване навколо окремого application service та Strategy Pattern.

Основні властивості:

- балансування за поточним навантаженням;
- підтримка активних/неактивних менеджерів;
- транзакційність;
- захист від повторної обробки;
- `FOR UPDATE SKIP LOCKED` для конкурентного доступу;
- audit history;
- Event + Listener + Queue Job;
- Redis queue;
- raw PostgreSQL SQL для статистики;
- partial indexes;
- unit та feature tests;
- можливість додавання нових алгоритмів без зміни controller та business service.
