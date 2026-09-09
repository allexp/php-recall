<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Создаёт схему учебных материалов и добавляет карточки Laravel. */
final class Version20260909173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Добавляет раздел «Фреймворки», категорию Laravel и учебные карточки';
    }

    public function up(Schema $schema): void
    {
        $this->createMaterialSchema();
        $this->addSql("INSERT INTO material_types (code, title) VALUES ('framework', 'Фреймворки') ON CONFLICT(code) DO UPDATE SET title = excluded.title");
        $this->addSql("INSERT INTO categories (type_id, code, title, position) VALUES ((SELECT id FROM material_types WHERE code = 'framework'), 'laravel', 'Laravel', 0) ON CONFLICT(type_id, code) DO UPDATE SET title = excluded.title, position = excluded.position");

        $now = (new \DateTimeImmutable())->format(DATE_ATOM);
        foreach ($this->cards() as $position => $item) {
            $material = $item['material'];
            $this->addSql(
                "INSERT INTO materials (type_id, category_id, title, slug, definition, short_description, full_description, content_format, source_url, position, created_at, updated_at)
                 VALUES ((SELECT id FROM material_types WHERE code = 'framework'), (SELECT categories.id FROM categories JOIN material_types ON material_types.id = categories.type_id WHERE material_types.code = 'framework' AND categories.code = 'laravel'), :title, :slug, :definition, :short_description, :full_description, 'html', :source_url, :position, :created_at, :updated_at)
                 ON CONFLICT(type_id, slug) DO UPDATE SET category_id = excluded.category_id, title = excluded.title, definition = excluded.definition, short_description = excluded.short_description, full_description = excluded.full_description, content_format = excluded.content_format, source_url = excluded.source_url, position = excluded.position, updated_at = excluded.updated_at",
                [
                    'title' => $material['title'],
                    'slug' => $material['slug'],
                    'definition' => $material['definition'],
                    'short_description' => $material['short_description'],
                    'full_description' => $material['full_description'],
                    'source_url' => $material['source_url'],
                    'position' => $position,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
            $this->addSql(
                "DELETE FROM code_examples WHERE material_id = (SELECT materials.id FROM materials JOIN material_types ON material_types.id = materials.type_id WHERE material_types.code = 'framework' AND materials.slug = :slug)",
                ['slug' => $material['slug']],
            );
            foreach ($item['examples'] ?? [] as $examplePosition => $example) {
                $this->addSql(
                    "INSERT INTO code_examples (material_id, title, language, code, position) VALUES ((SELECT materials.id FROM materials JOIN material_types ON material_types.id = materials.type_id WHERE material_types.code = 'framework' AND materials.slug = :slug), :title, :language, :code, :position)",
                    [
                        'slug' => $material['slug'],
                        'title' => $example['title'],
                        'language' => $example['language'],
                        'code' => $example['code'],
                        'position' => $examplePosition,
                    ],
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM code_examples WHERE material_id IN (SELECT materials.id FROM materials JOIN material_types ON material_types.id = materials.type_id WHERE material_types.code = 'framework')");
        $this->addSql("DELETE FROM materials WHERE type_id = (SELECT id FROM material_types WHERE code = 'framework')");
        $this->addSql("DELETE FROM categories WHERE type_id = (SELECT id FROM material_types WHERE code = 'framework')");
        $this->addSql("DELETE FROM material_types WHERE code = 'framework'");
    }

    private function createMaterialSchema(): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS material_types (id INTEGER PRIMARY KEY AUTOINCREMENT, code TEXT NOT NULL UNIQUE, title TEXT NOT NULL)');
        $this->addSql('CREATE TABLE IF NOT EXISTS categories (id INTEGER PRIMARY KEY AUTOINCREMENT, type_id INTEGER NOT NULL, code TEXT NOT NULL, title TEXT NOT NULL, position INTEGER NOT NULL DEFAULT 0, UNIQUE (type_id, code), FOREIGN KEY (type_id) REFERENCES material_types(id) ON DELETE CASCADE)');
        $this->addSql("CREATE TABLE IF NOT EXISTS materials (id INTEGER PRIMARY KEY AUTOINCREMENT, type_id INTEGER NOT NULL, category_id INTEGER, title TEXT NOT NULL, slug TEXT NOT NULL, definition TEXT NOT NULL DEFAULT '', short_description TEXT NOT NULL DEFAULT '', full_description TEXT NOT NULL DEFAULT '', content_format TEXT NOT NULL DEFAULT 'html' CHECK (content_format IN ('html', 'markdown', 'text')), source_url TEXT, position INTEGER NOT NULL DEFAULT 0, created_at TEXT NOT NULL, updated_at TEXT NOT NULL, UNIQUE (type_id, slug), FOREIGN KEY (type_id) REFERENCES material_types(id) ON DELETE CASCADE, FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL)");
        $this->addSql("CREATE TABLE IF NOT EXISTS material_sections (id INTEGER PRIMARY KEY AUTOINCREMENT, material_id INTEGER NOT NULL, title TEXT NOT NULL, slug TEXT NOT NULL, description TEXT NOT NULL DEFAULT '', position INTEGER NOT NULL DEFAULT 0, UNIQUE (material_id, slug), FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE)");
        $this->addSql("CREATE TABLE IF NOT EXISTS code_examples (id INTEGER PRIMARY KEY AUTOINCREMENT, material_id INTEGER NOT NULL, section_id INTEGER, title TEXT NOT NULL DEFAULT '', language TEXT NOT NULL, code TEXT NOT NULL, position INTEGER NOT NULL DEFAULT 0, FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE, FOREIGN KEY (section_id) REFERENCES material_sections(id) ON DELETE CASCADE)");
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_categories_type ON categories(type_id, position)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_materials_category ON materials(category_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_materials_type_title ON materials(type_id, title)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_code_examples_material ON code_examples(material_id, position)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_material_sections_material ON material_sections(material_id, position)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_code_examples_section ON code_examples(section_id, position)');
    }

    /** Возвращает карточки Laravel, записываемые миграцией. */
    private function cards(): array
    {
        $card = static function (
            string $slug,
            string $question,
            string $answer,
            string $details,
            string $source,
            array $examples = [],
        ): array {
            $item = [
                'material' => [
                    'title' => $question,
                    'slug' => $slug,
                    'definition' => $answer,
                    'short_description' => $details,
                    'full_description' => '<p>'.$answer.'</p><p>'.$details.'</p>',
                    'source_url' => $source,
                ],
            ];
            if ($examples !== []) {
                $item['examples'] = $examples;
            }

            return $item;
        };

        return [
    $card('app-directory', 'Какую роль выполняет каталог app?',
        'Каталог app содержит основной прикладной код проекта.',
        'В нём обычно находятся модели, контроллеры, middleware, задания очереди, события, политики и собственные сервисы. Большинство классов этого каталога принадлежит пространству имён App.',
        'https://laravel.com/docs/13.x/structure#the-app-directory'),
    $card('bootstrap-directory', 'Для чего предназначен каталог bootstrap?',
        'Каталог bootstrap содержит файл запуска приложения и файлы кэша фреймворка.',
        'Файл bootstrap/app.php создаёт и настраивает экземпляр приложения, включая маршруты, middleware и обработку исключений. Подкаталог cache ускоряет загрузку и обычно формируется командами Laravel.',
        'https://laravel.com/docs/13.x/structure#the-bootstrap-directory'),
    $card('config-directory', 'Что хранится в каталоге config?',
        'В config находятся конфигурационные файлы приложения и его сервисов.',
        'Код получает значения через функцию config(). Настройки могут опираться на переменные окружения, а в рабочей среде конфигурацию обычно объединяют в кэш.',
        'https://laravel.com/docs/13.x/configuration'),
    $card('database-directory', 'Для чего используется каталог database?',
        'Каталог database объединяет миграции, фабрики моделей и классы наполнения базы.',
        'Миграции описывают схему, factories создают тестовые модели, а seeders наполняют базу исходными или демонстрационными данными.',
        'https://laravel.com/docs/13.x/structure#the-database-directory'),
    $card('public-resources-storage', 'Чем отличаются каталоги public, resources и storage?',
        'public доступен веб-серверу, resources хранит исходные ресурсы, а storage — создаваемые приложением файлы.',
        'В public расположена точка входа index.php и опубликованные assets. В resources находятся шаблоны и исходники фронтенда. В storage сохраняются логи, кэши, скомпилированные шаблоны и пользовательские файлы.',
        'https://laravel.com/docs/13.x/structure#the-root-directory'),
    $card('routes-directory', 'Для чего предназначен каталог routes?',
        'Каталог routes содержит определения входных точек приложения.',
        'В web.php обычно размещают браузерные маршруты с cookies, сессией и CSRF-защитой. Дополнительные файлы могут описывать консольные команды, broadcasting и API-маршруты.',
        'https://laravel.com/docs/13.x/structure#the-routes-directory'),
    $card('environment-file', 'Какие данные находятся в .env и почему файл нельзя публиковать?',
        '.env хранит настройки конкретного окружения, включая потенциальные секреты.',
        'Ключ приложения, пароли баз данных и токены сервисов отличаются между окружениями. Публикация файла раскрывает секреты, поэтому в репозитории оставляют только безопасный шаблон .env.example.',
        'https://laravel.com/docs/13.x/configuration#environment-configuration'),
    $card('request-lifecycle', 'Как проходит HTTP-запрос через приложение Laravel?',
        'Запрос входит через public/index.php, приложение загружается, проходит middleware, попадает в маршрут и формирует ответ.',
        'После создания приложения запускаются сервис-провайдеры, Router подбирает маршрут, а pipeline выполняет middleware до и после контроллера. Затем ответ возвращается через тот же pipeline клиенту.',
        'https://laravel.com/docs/13.x/lifecycle'),
    $card('entry-point', 'Что представляет собой точка входа Laravel?',
        'Точкой входа для HTTP-запросов служит public/index.php.',
        'Веб-сервер должен направлять запросы в этот файл. Он подключает автозагрузчик Composer, получает приложение из bootstrap/app.php и передаёт ему текущий запрос.',
        'https://laravel.com/docs/13.x/lifecycle#first-steps'),
    $card('service-container', 'Что такое Service Container?',
        'Service Container создаёт объекты и управляет их зависимостями.',
        'Контейнер связывает абстракции с реализациями, автоматически разрешает классы и позволяет централизованно управлять временем жизни сервисов.',
        'https://laravel.com/docs/13.x/container'),
    $card('dependency-injection', 'Как Laravel выполняет автоматическое внедрение зависимостей?',
        'Контейнер анализирует типы параметров конструктора или вызываемого метода и создаёт нужные объекты.',
        'Конкретные классы без дополнительных настроек разрешаются через Reflection. Для интерфейса контейнеру заранее указывают соответствующую реализацию.',
        'https://laravel.com/docs/13.x/container#zero-configuration-resolution'),
    $card('service-provider', 'Что такое Service Provider?',
        'Service Provider — центральное место регистрации и начальной настройки сервисов приложения.',
        'Провайдеры связывают зависимости в контейнере, регистрируют события, маршруты и другие компоненты. Пользовательские провайдеры обычно перечисляются в bootstrap/providers.php.',
        'https://laravel.com/docs/13.x/providers'),
    $card('register-and-boot', 'Чем отличаются методы register() и boot() сервис-провайдера?',
        'register() регистрирует зависимости, а boot() выполняет настройку после регистрации всех провайдеров.',
        'В register() не следует полагаться на сервисы других провайдеров. В boot() они уже доступны, поэтому там можно подключать listeners, view composers и другую интеграционную настройку.',
        'https://laravel.com/docs/13.x/providers#the-register-method'),
    $card('container-bindings', 'Чем отличаются bind(), singleton() и scoped()?',
        'bind() создаёт экземпляр при каждом разрешении, singleton() повторно использует один экземпляр, scoped() — один экземпляр в пределах жизненного цикла запроса или задания.',
        'scoped() особенно важен для долгоживущих workers, где singleton мог бы случайно переносить состояние между запросами или заданиями.',
        'https://laravel.com/docs/13.x/container#binding-basics'),
    $card('facades', 'Что такое Facade в Laravel?',
        'Facade предоставляет короткий статически выглядящий доступ к объекту из Service Container.',
        'Вызов обрабатывает базовый класс Facade и направляет его экземпляру сервиса. Это позволяет заменять сервис fake-объектом в тестах, но чрезмерное использование может скрывать зависимости класса.',
        'https://laravel.com/docs/13.x/facades'),
    $card('contracts', 'Что такое контракт Laravel?',
        'Контракт — интерфейс, описывающий возможность, предоставляемую фреймворком.',
        'Зависимость от контракта уменьшает связь с конкретной реализацией. Service Container выбирает объект, который реализует нужный интерфейс.',
        'https://laravel.com/docs/13.x/contracts'),
    $card('register-route', 'Как зарегистрировать маршрут в Laravel?',
        'Маршрут объявляют методом Route, соответствующим HTTP-глаголу, и передают URI с обработчиком.',
        'Обработчиком может быть замыкание или метод контроллера. Для одного URI можно регистрировать разные действия для GET, POST, PUT, PATCH и DELETE.',
        'https://laravel.com/docs/13.x/routing#basic-routing', [
            ['title' => 'Маршрут контроллера', 'language' => 'php', 'code' => "Route::get('/users/{user}', [UserController::class, 'show']);"],
        ]),
    $card('route-parameters', 'Как работают параметры и имена маршрутов?',
        'Параметры извлекают значения из URI, а имя позволяет обращаться к маршруту независимо от его адреса.',
        'Обязательный параметр записывается как {user}, необязательный — как {page?}. Метод name() задаёт имя, которое используют функции route() и redirect()->route().',
        'https://laravel.com/docs/13.x/routing#route-parameters'),
    $card('route-groups', 'Как объединять маршруты по middleware, префиксу и имени?',
        'Общие атрибуты задают группе маршрутов, чтобы не повторять их в каждом определении.',
        'Методы middleware(), prefix() и name() можно объединять в цепочку перед group(). Вложенные группы наследуют и объединяют настройки родительских.',
        'https://laravel.com/docs/13.x/routing#route-groups'),
    $card('route-model-binding', 'Для чего используется Route Model Binding?',
        'Route Model Binding автоматически превращает параметр маршрута в модель Eloquent.',
        'При неявной привязке имя аргумента совпадает с параметром URI, а тип указывает модель. Если запись не найдена, Laravel автоматически возвращает ответ 404.',
        'https://laravel.com/docs/13.x/routing#route-model-binding'),
    $card('resource-routing', 'Что создают resource-маршрут и resource-контроллер?',
        'Они задают стандартный набор CRUD-маршрутов и согласованных методов контроллера.',
        'Одна декларация Route::resource() связывает index, create, store, show, edit, update и destroy с URI ресурса. Ненужные действия можно ограничить через only() или except().',
        'https://laravel.com/docs/13.x/controllers#resource-controllers'),
    $card('web-and-api-routes', 'Чем маршруты web отличаются от API-маршрутов?',
        'Web-маршруты рассчитаны на состояние браузерной сессии, а API-маршруты обычно остаются stateless.',
        'Группа web включает cookies, сессию и защиту от подделки запросов. API использует отдельный префикс и механизмы ограничения частоты; её можно подключить командой install:api.',
        'https://laravel.com/docs/13.x/routing#the-default-route-files'),
    $card('middleware', 'Что такое middleware и где он выполняется?',
        'Middleware фильтрует или дополняет запрос до обработчика и ответ после него.',
        'С его помощью проверяют аутентификацию, ограничивают частоту запросов, добавляют заголовки и выполняют другую сквозную логику. Middleware можно применять глобально, к группе или отдельному маршруту.',
        'https://laravel.com/docs/13.x/middleware'),
    $card('requests-and-responses', 'Как Laravel предоставляет данные запроса и формирует ответы?',
        'Объект Request даёт доступ к input, файлам и заголовкам, а обработчик возвращает строку, массив или объект Response.',
        'Laravel преобразует массивы и модели в JSON, поддерживает redirects, downloads, streams и позволяет задавать статус и заголовки ответа.',
        'https://laravel.com/docs/13.x/requests'),
    $card('csrf-protection', 'Для чего нужна защита от подделки запросов?',
        'Она не позволяет стороннему сайту выполнить изменяющий запрос от имени пользователя с активной сессией.',
        'Laravel проверяет секретный токен или допустимое происхождение запроса. В HTML-форму Blade добавляют директиву @csrf, которая формирует скрытое поле.',
        'https://laravel.com/docs/13.x/csrf'),
    $card('validation', 'Как выполняется валидация входных данных?',
        'Правила передают методу validate() запроса или отдельному Validator.',
        'При ошибке обычного web-запроса Laravel возвращает пользователя назад с сообщениями и введёнными данными, а для JSON-запроса формирует ответ 422 со структурированными ошибками.',
        'https://laravel.com/docs/13.x/validation#quick-displaying-the-validation-errors'),
    $card('form-requests', 'Для чего используются Form Request-классы?',
        'Form Request объединяет правила валидации и авторизации конкретного запроса.',
        'Такой класс внедряют в метод контроллера, и он проверяется до выполнения метода. Доверенные данные получают через validated() или safe().',
        'https://laravel.com/docs/13.x/validation#form-request-validation'),
    $card('eloquent', 'Что такое Eloquent ORM?',
        'Eloquent представляет таблицы базы данных PHP-моделями и предоставляет API для запросов и связей.',
        'Модель умеет находить, создавать, обновлять и удалять строки, преобразовывать атрибуты и описывать отношения с другими моделями.',
        'https://laravel.com/docs/13.x/eloquent'),
    $card('eloquent-conventions', 'Какие соглашения связывают модель Eloquent с таблицей?',
        'По умолчанию имя таблицы — snake_case во множественном числе от имени модели, а первичный ключ называется id.',
        'Eloquent также ожидает столбцы created_at и updated_at. Эти соглашения можно изменить свойствами table, primaryKey, keyType, incrementing и timestamps.',
        'https://laravel.com/docs/13.x/eloquent#eloquent-model-conventions'),
    $card('mass-assignment', 'Что такое Mass Assignment и как его ограничить?',
        'Mass Assignment заполняет несколько атрибутов модели из массива и требует явной защиты разрешённых полей.',
        'Свойство fillable задаёт разрешённые атрибуты, а guarded — запрещённые. Нельзя бездумно передавать в create() все пользовательские данные, иначе клиент сможет изменить служебные поля.',
        'https://laravel.com/docs/13.x/eloquent#mass-assignment'),
    $card('eloquent-retrieval', 'Чем отличаются get(), first(), find() и firstOrFail()?',
        'get() возвращает коллекцию, first() — первую запись или null, find() ищет по первичному ключу, а firstOrFail() выбрасывает исключение при отсутствии записи.',
        'В HTTP-контексте исключение ModelNotFoundException обычно преобразуется в ответ 404. Для поиска по ключу также существует findOrFail().',
        'https://laravel.com/docs/13.x/eloquent#retrieving-single-models-aggregates'),
    $card('casts-accessors-scopes', 'Для чего модели нужны casts, accessors, mutators и scopes?',
        'Casts преобразуют тип атрибута, accessors и mutators меняют чтение и запись, а scopes переиспользуют условия запросов.',
        'Эти механизмы удерживают правила представления данных и типовые выборки рядом с моделью. Scope изменяет Builder и не должен незаметно выполнять запрос.',
        'https://laravel.com/docs/13.x/eloquent-mutators'),
    $card('n-plus-one', 'Что такое проблема N+1 и как её устраняет eager loading?',
        'N+1 возникает, когда после одного запроса списка выполняется отдельный запрос связи для каждого элемента.',
        'Жадная загрузка через with() получает связи заранее небольшим числом запросов. load() добавляет связи уже полученной коллекции, а preventLazyLoading() помогает обнаруживать случайную ленивую загрузку.',
        'https://laravel.com/docs/13.x/eloquent-relationships#eager-loading'),
    $card('eloquent-relationships', 'Как Eloquent описывает связи между моделями?',
        'Методы модели возвращают объекты отношений, например hasOne, hasMany, belongsTo и belongsToMany.',
        'Отношение одновременно описывает ключи и служит построителем запроса. Связь многие ко многим использует промежуточную таблицу, дополнительные поля которой доступны через pivot.',
        'https://laravel.com/docs/13.x/eloquent-relationships'),
    $card('migrations', 'Для чего нужны миграции и методы up() и down()?',
        'Миграции версионируют изменения схемы базы данных вместе с исходным кодом.',
        'up() применяет изменение, а down() отменяет его. Команда migrate запускает новые миграции по времени их создания, а rollback откатывает последнюю группу.',
        'https://laravel.com/docs/13.x/migrations'),
    $card('factories-and-seeders', 'Чем Factory отличается от Seeder?',
        'Factory описывает создание моделей с правдоподобными данными, Seeder координирует наполнение базы.',
        'Seeder может вызывать другие seeders и использовать factories для больших наборов записей и связей. В тестах factories помогают лаконично готовить нужное состояние.',
        'https://laravel.com/docs/13.x/seeding'),
    $card('blade-layouts', 'Как Blade строит layouts, секции и компоненты?',
        'Layouts задают общий каркас, секции подставляют содержимое страниц, а компоненты инкапсулируют повторяемую разметку.',
        'Шаблон может наследовать layout через @extends и заполнять @section. Компоненты вызываются тегами x-*, принимают props и slots и бывают анонимными или связанными с PHP-классом.',
        'https://laravel.com/docs/13.x/blade'),
    $card('blade-escaping', 'Как Blade защищает вывод и чем опасна конструкция {!! !!}?',
        'Выражение {{ }} экранирует HTML, а {!! !!} выводит значение без обработки.',
        'Неэкранированный вывод пользовательских данных создаёт риск XSS. Его применяют только для уже очищенного доверенного HTML, когда обычное текстовое представление не подходит.',
        'https://laravel.com/docs/13.x/blade#displaying-data'),
    $card('authentication-authorization', 'Чем аутентификация отличается от авторизации?',
        'Аутентификация устанавливает личность пользователя, авторизация проверяет право выполнить действие.',
        'Guard определяет способ аутентификации для запроса, provider получает пользователя из хранилища. Gates и policies выражают правила доступа к операциям и моделям.',
        'https://laravel.com/docs/13.x/authentication'),
    $card('gates-and-policies', 'Чем Policy отличается от Gate?',
        'Gate удобно описывает отдельную проверку, а Policy группирует действия вокруг модели или ресурса.',
        'Policy содержит методы view, create, update, delete и другие проверки предметной области. Laravel может автоматически сопоставить policy с моделью по соглашениям.',
        'https://laravel.com/docs/13.x/authorization#gates'),
    $card('events-and-queues', 'Чем отличаются события, слушатели и задания очереди?',
        'Событие сообщает о факте, слушатель реагирует на него, а Job описывает работу, которую можно отправить в очередь.',
        'События уменьшают связанность между частями приложения. Слушатель может выполняться синхронно или быть поставлен в очередь, а отдельный Job удобно повторять, ограничивать и направлять в нужную очередь.',
        'https://laravel.com/docs/13.x/events'),
    $card('queue-retries', 'Что происходит при неудачном выполнении Job?',
        'Worker повторяет задание согласно числу попыток и задержкам, после исчерпания попыток оно становится failed job.',
        'Поведение задают параметрами worker или свойствами и методами задания: tries, backoff, retryUntil и failed. Обработчик должен учитывать возможность повторного запуска.',
        'https://laravel.com/docs/13.x/queues#dealing-with-failed-jobs'),
    $card('notifications', 'Для чего используются Notifications?',
        'Notifications описывают одно уведомление и способы его доставки по разным каналам.',
        'Один класс может подготовить сообщение для mail, database, broadcast, Slack или пользовательского канала. Интерфейс ShouldQueue переносит доставку в очередь.',
        'https://laravel.com/docs/13.x/notifications'),
    $card('cache-remember', 'Для чего используется Cache::remember()?',
        'Cache::remember() возвращает сохранённое значение или вычисляет и кэширует его на заданное время.',
        'Метод объединяет чтение, вычисление и запись в одну операцию. При изменении исходных данных соответствующий ключ нужно удалить или обновить, чтобы не показывать устаревший результат.',
        'https://laravel.com/docs/13.x/cache#retrieve-store'),
    $card('filesystem-disks', 'Что такое Filesystem Disk?',
        'Disk — именованная конфигурация файлового хранилища с единым API Laravel.',
        'Один и тот же код может работать с локальным диском, SFTP или S3-совместимым сервисом. Публичные локальные файлы обычно связывают с public/storage командой storage:link.',
        'https://laravel.com/docs/13.x/filesystem'),
    $card('task-scheduling', 'Чем планировщик Laravel отличается от системного cron?',
        'Laravel хранит расписание задач в коде, а cron обычно запускает планировщик каждую минуту.',
        'Расписание поддерживает интервалы, окружения, блокировки, фоновые процессы и предотвращение пересечений. Команда schedule:run определяет, какие задачи пора выполнить.',
        'https://laravel.com/docs/13.x/scheduling'),
    $card('artisan-and-config-cache', 'Что такое Artisan и зачем кэшировать конфигурацию?',
        'Artisan — консольный интерфейс Laravel, а config:cache объединяет конфигурацию для быстрой загрузки в production.',
        'После кэширования env() следует использовать только внутри config-файлов: в прикладном коде значения получают через config(). Команда optimize:clear очищает созданные оптимизационные кэши.',
        'https://laravel.com/docs/13.x/artisan'),
    $card('testing', 'Чем Unit-тест отличается от Feature-теста в Laravel?',
        'Unit-тест проверяет небольшой изолированный фрагмент, Feature-тест может загружать приложение и взаимодействовать с его подсистемами.',
        'Feature-тесты отправляют HTTP-запросы, проверяют базу и используют сервисы контейнера. Facades для HTTP, событий, очередей, почты и уведомлений предоставляют fake-режимы без реальных побочных действий.',
        'https://laravel.com/docs/13.x/testing'),
    $card('api-resources', 'Зачем использовать API Resources вместо прямой выдачи модели?',
        'API Resource явно задаёт публичное JSON-представление модели.',
        'Он отделяет контракт API от структуры таблицы, скрывает служебные поля, условно добавляет связи и метаданные и единообразно преобразует коллекции с пагинацией.',
        'https://laravel.com/docs/13.x/eloquent-resources'),
        ];
    }
}
