<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\Migrations\AbstractMigration;

/** Создаёт таблицу практических задач и наполняет её исходными заданиями. */
final class Version20260909182000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Добавляет практические задачи всех уровней сложности';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof SQLitePlatform,
            'Миграция поддерживает только SQLite.',
        );

        $sql = file_get_contents(__FILE__, false, null, __COMPILER_HALT_OFFSET__);
        if (!is_string($sql)) {
            throw new \RuntimeException('Не удалось прочитать SQL-содержимое миграции задач.');
        }

        $source = new \PDO('sqlite::memory:');
        if ($source->exec($sql) === false) {
            throw new \RuntimeException('Не удалось выполнить SQL-содержимое миграции задач.');
        }

        $this->addSql(
            "CREATE TABLE IF NOT EXISTS tasks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                difficulty TEXT NOT NULL CHECK (difficulty IN ('easy', 'medium', 'hard')),
                title TEXT NOT NULL,
                description TEXT NOT NULL,
                starter_code TEXT NOT NULL,
                solution_code TEXT NOT NULL,
                UNIQUE (difficulty, title)
            )",
        );
        $tasks = $source->query(
            'SELECT difficulty, title, description, starter_code, solution_code FROM tasks ORDER BY id',
        )->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($tasks as $task) {
            $this->addSql(
                'INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
                 VALUES (:difficulty, :title, :description, :starter_code, :solution_code)',
                $task,
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS tasks');
    }
}

__halt_compiler();

CREATE TABLE IF NOT EXISTS tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    difficulty TEXT NOT NULL CHECK (difficulty IN ('easy', 'medium', 'hard')),
    title TEXT NOT NULL,
    description TEXT NOT NULL,
    starter_code TEXT NOT NULL,
    solution_code TEXT NOT NULL,
    UNIQUE (difficulty, title)
);

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('easy', 'Сумма чётных чисел', 'Дан массив целых чисел. Выведите сумму только чётных элементов.', '<?php

$numbers = [3, 8, 12, 5, 7, 4];', '<?php

$numbers = [3, 8, 12, 5, 7, 4];
$sum = array_sum(array_filter(
    $numbers,
    fn (int $number): bool => $number % 2 === 0,
));

echo $sum;');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('easy', 'Разворот слов', 'Разверните порядок слов в строке и выведите результат.', '<?php

$text = ''PHP нравится разработчикам'';', '<?php

$text = ''PHP нравится разработчикам'';
$words = explode('' '', $text);

echo implode('' '', array_reverse($words));');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('medium', 'Группировка пользователей', 'Сгруппируйте имена пользователей по полю role и выведите результат через print_r.', '<?php

$users = [
    [''name'' => ''Анна'', ''role'' => ''admin''],
    [''name'' => ''Борис'', ''role'' => ''user''],
    [''name'' => ''Вера'', ''role'' => ''admin''],
];', '<?php

$users = [
    [''name'' => ''Анна'', ''role'' => ''admin''],
    [''name'' => ''Борис'', ''role'' => ''user''],
    [''name'' => ''Вера'', ''role'' => ''admin''],
];
$grouped = [];

foreach ($users as $user) {
    $grouped[$user[''role'']][] = $user[''name''];
}

print_r($grouped);');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('medium', 'Частота значений', 'Подсчитайте частоту слов без учёта регистра и отсортируйте результат по убыванию.', '<?php

$words = [''PHP'', ''code'', ''php'', ''Test'', ''code'', ''php''];', '<?php

$words = [''PHP'', ''code'', ''php'', ''Test'', ''code'', ''php''];
$normalizedWords = array_map(''strtolower'', $words);
$frequency = array_count_values($normalizedWords);
arsort($frequency);

print_r($frequency);');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('hard', 'Слияние интервалов', 'Объедините пересекающиеся интервалы и выведите итоговый массив.', '<?php

$intervals = [[1, 3], [2, 6], [8, 10], [9, 12], [15, 18]];', '<?php

$intervals = [[1, 3], [2, 6], [8, 10], [9, 12], [15, 18]];
usort(
    $intervals,
    fn (array $left, array $right): int => $left[0] <=> $right[0],
);
$merged = [];

foreach ($intervals as $interval) {
    $lastIndex = array_key_last($merged);

    if ($lastIndex === null || $merged[$lastIndex][1] < $interval[0]) {
        $merged[] = $interval;
        continue;
    }

    $merged[$lastIndex][1] = max($merged[$lastIndex][1], $interval[1]);
}

print_r($merged);');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('hard', 'Обход дерева', 'Получите плоский список названий узлов в порядке обхода дерева в глубину.', '<?php

$tree = [
    [
        ''name'' => ''A'',
        ''children'' => [
            [''name'' => ''B'', ''children'' => []],
            [''name'' => ''C'', ''children'' => []],
        ],
    ],
];', '<?php

$tree = [
    [
        ''name'' => ''A'',
        ''children'' => [
            [''name'' => ''B'', ''children'' => []],
            [''name'' => ''C'', ''children'' => []],
        ],
    ],
];
$walk = function (array $nodes) use (&$walk): array {
    $result = [];

    foreach ($nodes as $node) {
        $result[] = $node[''name''];
        array_push($result, ...$walk($node[''children'']));
    }

    return $result;
};

print_r($walk($tree));');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('easy', 'Количество положительных чисел', 'Посчитайте положительные числа в массиве и выведите их количество.', '<?php

$numbers = [-3, 0, 7, 12, -5, 4];', '<?php

$numbers = [-3, 0, 7, 12, -5, 4];
$positiveNumbers = array_filter(
    $numbers,
    fn (int $number): bool => $number > 0,
);

echo count($positiveNumbers);');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('easy', 'Имена с заглавной буквы', 'Преобразуйте каждое имя так, чтобы оно начиналось с заглавной буквы, и выведите массив.', '<?php

$names = [''анна'', ''борис'', ''вера''];', '<?php

$names = [''анна'', ''борис'', ''вера''];
$formattedNames = array_map(
    fn (string $name): string => mb_convert_case($name, MB_CASE_TITLE, ''UTF-8''),
    $names,
);

print_r($formattedNames);');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('easy', 'Удаление повторов', 'Удалите повторяющиеся значения из массива, восстановите последовательные индексы и выведите результат.', '<?php

$colors = [''red'', ''blue'', ''red'', ''green'', ''blue''];', '<?php

$colors = [''red'', ''blue'', ''red'', ''green'', ''blue''];
$uniqueColors = array_values(array_unique($colors));

print_r($uniqueColors);');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('easy', 'Самое длинное слово', 'Найдите самое длинное слово в массиве и выведите его.', '<?php

$words = [''код'', ''функция'', ''массив'', ''переменная''];', '<?php

$words = [''код'', ''функция'', ''массив'', ''переменная''];
$longestWord = '''';

foreach ($words as $word) {
    if (mb_strlen($word) > mb_strlen($longestWord)) {
        $longestWord = $word;
    }
}

echo $longestWord;');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('easy', 'Проверка палиндрома', 'Определите, является ли слово палиндромом, и выведите «да» или «нет».', '<?php

$word = ''топот'';', '<?php

$word = ''топот'';
$characters = preg_split(''//u'', $word, -1, PREG_SPLIT_NO_EMPTY);
$isPalindrome = $characters === array_reverse($characters);

echo $isPalindrome ? ''да'' : ''нет'';');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('easy', 'Среднее арифметическое', 'Вычислите среднее арифметическое чисел массива и выведите результат.', '<?php

$numbers = [6, 8, 10, 12];', '<?php

$numbers = [6, 8, 10, 12];
$average = array_sum($numbers) / count($numbers);

echo $average;');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('easy', 'Фильтр коротких строк', 'Оставьте строки длиной не менее пяти символов и выведите массив с последовательными индексами.', '<?php

$items = [''PHP'', ''Laravel'', ''код'', ''Symfony'', ''тест''];', '<?php

$items = [''PHP'', ''Laravel'', ''код'', ''Symfony'', ''тест''];
$longItems = array_values(array_filter(
    $items,
    fn (string $item): bool => mb_strlen($item) >= 5,
));

print_r($longItems);');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('easy', 'Таблица квадратов', 'Создайте массив квадратов чисел от 1 до 5 и выведите его.', '<?php

$numbers = range(1, 5);', '<?php

$numbers = range(1, 5);
$squares = array_map(
    fn (int $number): int => $number ** 2,
    $numbers,
);

print_r($squares);');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('easy', 'Поиск подстроки', 'Проверьте, встречается ли слово PHP в тексте без учёта регистра, и выведите «найдено» или «не найдено».', '<?php

$text = ''Изучаем php функции'';', '<?php

$text = ''Изучаем php функции'';
$containsPhp = stripos($text, ''PHP'') !== false;

echo $containsPhp ? ''найдено'' : ''не найдено'';');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('easy', 'Объединение имён', 'Объедините имена в строку через запятую и пробел и выведите результат.', '<?php

$names = [''Анна'', ''Борис'', ''Вера''];', '<?php

$names = [''Анна'', ''Борис'', ''Вера''];

echo implode('', '', $names);');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('medium', 'Сумма заказов по клиентам', 'Подсчитайте общую сумму заказов каждого клиента и выведите ассоциативный массив.', '<?php

$orders = [
    [''customer'' => ''Анна'', ''total'' => 1200],
    [''customer'' => ''Борис'', ''total'' => 900],
    [''customer'' => ''Анна'', ''total'' => 450],
];', '<?php

$orders = [
    [''customer'' => ''Анна'', ''total'' => 1200],
    [''customer'' => ''Борис'', ''total'' => 900],
    [''customer'' => ''Анна'', ''total'' => 450],
];
$totals = [];

foreach ($orders as $order) {
    $customer = $order[''customer''];
    $totals[$customer] = ($totals[$customer] ?? 0) + $order[''total''];
}

print_r($totals);');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('medium', 'Сортировка товаров', 'Отсортируйте товары сначала по возрастанию цены, а при равной цене — по названию.', '<?php

$products = [
    [''name'' => ''Клавиатура'', ''price'' => 3000],
    [''name'' => ''Мышь'', ''price'' => 1500],
    [''name'' => ''Коврик'', ''price'' => 1500],
];', '<?php

$products = [
    [''name'' => ''Клавиатура'', ''price'' => 3000],
    [''name'' => ''Мышь'', ''price'' => 1500],
    [''name'' => ''Коврик'', ''price'' => 1500],
];

usort($products, function (array $left, array $right): int {
    return [$left[''price''], $left[''name'']] <=> [$right[''price''], $right[''name'']];
});

print_r($products);');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('medium', 'Индекс пользователей', 'Постройте ассоциативный массив пользователей, где ключом служит id, и выведите его.', '<?php

$users = [
    [''id'' => 10, ''name'' => ''Анна''],
    [''id'' => 25, ''name'' => ''Борис''],
];', '<?php

$users = [
    [''id'' => 10, ''name'' => ''Анна''],
    [''id'' => 25, ''name'' => ''Борис''],
];
$usersById = array_column($users, null, ''id'');

print_r($usersById);');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('medium', 'Пересечение тегов', 'Найдите общие теги двух списков без повторов и выведите массив с последовательными индексами.', '<?php

$firstTags = [''php'', ''backend'', ''api'', ''php''];
$secondTags = [''api'', ''testing'', ''php''];', '<?php

$firstTags = [''php'', ''backend'', ''api'', ''php''];
$secondTags = [''api'', ''testing'', ''php''];
$commonTags = array_values(array_unique(array_intersect($firstTags, $secondTags)));

print_r($commonTags);');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('medium', 'Нормализация телефона', 'Удалите из номера все символы, кроме цифр, и приведите российский номер к формату +7XXXXXXXXXX.', '<?php

$phone = ''8 (999) 123-45-67'';', '<?php

$phone = ''8 (999) 123-45-67'';
$digits = preg_replace(''/\D+/'', '''', $phone);

if (strlen($digits) === 11 && $digits[0] === ''8'') {
    $digits = ''7''.substr($digits, 1);
}

echo ''+''.$digits;');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('medium', 'Разбиение на страницы', 'Разбейте массив записей на страницы по три элемента и выведите вторую страницу.', '<?php

$records = range(1, 10);
$pageSize = 3;
$page = 2;', '<?php

$records = range(1, 10);
$pageSize = 3;
$page = 2;
$offset = ($page - 1) * $pageSize;
$pageRecords = array_slice($records, $offset, $pageSize);

print_r($pageRecords);');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('medium', 'Баланс операций', 'Вычислите итоговый баланс по операциям типов income и expense и выведите его.', '<?php

$operations = [
    [''type'' => ''income'', ''amount'' => 5000],
    [''type'' => ''expense'', ''amount'' => 1200],
    [''type'' => ''expense'', ''amount'' => 800],
];', '<?php

$operations = [
    [''type'' => ''income'', ''amount'' => 5000],
    [''type'' => ''expense'', ''amount'' => 1200],
    [''type'' => ''expense'', ''amount'' => 800],
];
$balance = array_reduce(
    $operations,
    function (int $balance, array $operation): int {
        $sign = $operation[''type''] === ''income'' ? 1 : -1;

        return $balance + $sign * $operation[''amount''];
    },
    0,
);

echo $balance;');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('medium', 'Проверка скобок', 'Проверьте правильность расстановки круглых скобок в строке и выведите «корректно» или «ошибка».', '<?php

$expression = ''(a + b) * (c - (d / 2))'';', '<?php

$expression = ''(a + b) * (c - (d / 2))'';
$balance = 0;
$isValid = true;

foreach (str_split($expression) as $character) {
    if ($character === ''('') {
        $balance++;
    } elseif ($character === '')'') {
        $balance--;
    }

    if ($balance < 0) {
        $isValid = false;
        break;
    }
}

$isValid = $isValid && $balance === 0;
echo $isValid ? ''корректно'' : ''ошибка'';');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('medium', 'Сводка оценок', 'Для каждого студента вычислите среднюю оценку, округлённую до одного знака, и выведите результат.', '<?php

$grades = [
    ''Анна'' => [5, 4, 5],
    ''Борис'' => [3, 4, 4],
];', '<?php

$grades = [
    ''Анна'' => [5, 4, 5],
    ''Борис'' => [3, 4, 4],
];
$averages = [];

foreach ($grades as $student => $studentGrades) {
    $averages[$student] = round(
        array_sum($studentGrades) / count($studentGrades),
        1,
    );
}

print_r($averages);');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('medium', 'Параметры URL', 'Добавьте параметры page и sort к URL, сохранив существующие параметры, и выведите новый URL.', '<?php

$url = ''https://example.com/catalog?category=books'';
$newParameters = [''page'' => 2, ''sort'' => ''price''];', '<?php

$url = ''https://example.com/catalog?category=books'';
$newParameters = [''page'' => 2, ''sort'' => ''price''];
$parts = parse_url($url);
parse_str($parts[''query''] ?? '''', $parameters);
$parameters = array_merge($parameters, $newParameters);
$result = $parts[''scheme''].''://''.$parts[''host''].$parts[''path''];
$result .= ''?''.http_build_query($parameters);

echo $result;');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('hard', 'Группировка анаграмм', 'Сгруппируйте слова-анаграммы и выведите массив групп.', '<?php

$words = [''кот'', ''ток'', ''рост'', ''сорт'', ''кто'', ''сон''];', '<?php

$words = [''кот'', ''ток'', ''рост'', ''сорт'', ''кто'', ''сон''];
$groups = [];

foreach ($words as $word) {
    $characters = preg_split(''//u'', $word, -1, PREG_SPLIT_NO_EMPTY);
    sort($characters);
    $key = implode('''', $characters);
    $groups[$key][] = $word;
}

print_r(array_values($groups));');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('hard', 'Медиана массива', 'Вычислите медиану числового массива для чётного и нечётного количества элементов.', '<?php

$numbers = [12, 3, 5, 7, 4, 19];', '<?php

$numbers = [12, 3, 5, 7, 4, 19];
sort($numbers);
$count = count($numbers);
$middle = intdiv($count, 2);
$median = $count % 2 === 1
    ? $numbers[$middle]
    : ($numbers[$middle - 1] + $numbers[$middle]) / 2;

echo $median;');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('hard', 'Топ популярных товаров', 'Подсчитайте количество продаж каждого товара, отсортируйте по убыванию и выведите три самых популярных.', '<?php

$sales = [''Книга'', ''Чашка'', ''Книга'', ''Ручка'', ''Чашка'', ''Книга'', ''Блокнот''];', '<?php

$sales = [''Книга'', ''Чашка'', ''Книга'', ''Ручка'', ''Чашка'', ''Книга'', ''Блокнот''];
$popularity = array_count_values($sales);
arsort($popularity);
$topProducts = array_slice($popularity, 0, 3, true);

print_r($topProducts);');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('hard', 'Плоский массив категорий', 'Преобразуйте дерево категорий в плоский массив строк с отступами, отражающими глубину.', '<?php

$categories = [
    [
        ''name'' => ''Техника'',
        ''children'' => [
            [''name'' => ''Ноутбуки'', ''children'' => []],
            [''name'' => ''Телефоны'', ''children'' => []],
        ],
    ],
];', '<?php

$categories = [
    [
        ''name'' => ''Техника'',
        ''children'' => [
            [''name'' => ''Ноутбуки'', ''children'' => []],
            [''name'' => ''Телефоны'', ''children'' => []],
        ],
    ],
];
$flatten = function (array $nodes, int $depth = 0) use (&$flatten): array {
    $result = [];

    foreach ($nodes as $node) {
        $result[] = str_repeat(''  '', $depth).$node[''name''];
        array_push($result, ...$flatten($node[''children''], $depth + 1));
    }

    return $result;
};

print_r($flatten($categories));');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('hard', 'Самая длинная последовательность', 'Найдите длину самой длинной последовательности подряд идущих целых чисел в неотсортированном массиве.', '<?php

$numbers = [100, 4, 200, 1, 3, 2, 5];', '<?php

$numbers = [100, 4, 200, 1, 3, 2, 5];
$set = array_fill_keys($numbers, true);
$longest = 0;

foreach ($numbers as $number) {
    if (isset($set[$number - 1])) {
        continue;
    }

    $length = 1;
    while (isset($set[$number + $length])) {
        $length++;
    }

    $longest = max($longest, $length);
}

echo $longest;');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('hard', 'Вложенные параметры', 'Преобразуйте пары с точечной нотацией ключей во вложенный ассоциативный массив.', '<?php

$values = [
    ''user.name'' => ''Анна'',
    ''user.address.city'' => ''Казань'',
    ''settings.theme'' => ''dark'',
];', '<?php

$values = [
    ''user.name'' => ''Анна'',
    ''user.address.city'' => ''Казань'',
    ''settings.theme'' => ''dark'',
];
$result = [];

foreach ($values as $path => $value) {
    $target = &$result;

    foreach (explode(''.'', $path) as $key) {
        $target = &$target[$key];
    }

    $target = $value;
    unset($target);
}

print_r($result);');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('hard', 'Проверка судоку', 'Проверьте, нет ли повторяющихся ненулевых чисел в строках и столбцах поля судоку.', '<?php

$board = [
    [5, 3, 0, 0],
    [0, 0, 4, 2],
    [3, 0, 0, 0],
    [0, 1, 0, 0],
];', '<?php

$board = [
    [5, 3, 0, 0],
    [0, 0, 4, 2],
    [3, 0, 0, 0],
    [0, 1, 0, 0],
];
$isUnique = function (array $values): bool {
    $values = array_filter($values, fn (int $value): bool => $value !== 0);

    return count($values) === count(array_unique($values));
};
$isValid = true;

for ($index = 0; $index < count($board); $index++) {
    $column = array_column($board, $index);

    if (!$isUnique($board[$index]) || !$isUnique($column)) {
        $isValid = false;
        break;
    }
}

echo $isValid ? ''корректно'' : ''ошибка'';');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('hard', 'Разница конфигураций', 'Найдите значения, которые отличаются в двух вложенных конфигурациях, сохранив путь к каждому отличию.', '<?php

$old = [''app'' => [''debug'' => false, ''locale'' => ''ru''], ''cache'' => true];
$new = [''app'' => [''debug'' => true, ''locale'' => ''ru''], ''cache'' => false];', '<?php

$old = [''app'' => [''debug'' => false, ''locale'' => ''ru''], ''cache'' => true];
$new = [''app'' => [''debug'' => true, ''locale'' => ''ru''], ''cache'' => false];
$compare = function (array $left, array $right, string $prefix = '''') use (&$compare): array {
    $differences = [];

    foreach (array_unique(array_merge(array_keys($left), array_keys($right))) as $key) {
        $path = $prefix === '''' ? (string) $key : $prefix.''.''.$key;
        $leftValue = $left[$key] ?? null;
        $rightValue = $right[$key] ?? null;

        if (is_array($leftValue) && is_array($rightValue)) {
            $differences += $compare($leftValue, $rightValue, $path);
        } elseif ($leftValue !== $rightValue) {
            $differences[$path] = [''old'' => $leftValue, ''new'' => $rightValue];
        }
    }

    return $differences;
};

print_r($compare($old, $new));');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('hard', 'Маршрут в графе', 'Найдите кратчайший по числу рёбер маршрут между двумя вершинами невзвешенного графа.', '<?php

$graph = [
    ''A'' => [''B'', ''C''],
    ''B'' => [''D''],
    ''C'' => [''D'', ''E''],
    ''D'' => [''F''],
    ''E'' => [''F''],
    ''F'' => [],
];
$start = ''A'';
$finish = ''F'';', '<?php

$graph = [
    ''A'' => [''B'', ''C''],
    ''B'' => [''D''],
    ''C'' => [''D'', ''E''],
    ''D'' => [''F''],
    ''E'' => [''F''],
    ''F'' => [],
];
$start = ''A'';
$finish = ''F'';
$queue = [[$start]];
$visited = [$start => true];
$route = [];

while ($queue !== []) {
    $path = array_shift($queue);
    $vertex = $path[array_key_last($path)];

    if ($vertex === $finish) {
        $route = $path;
        break;
    }

    foreach ($graph[$vertex] as $neighbor) {
        if (!isset($visited[$neighbor])) {
            $visited[$neighbor] = true;
            $queue[] = [...$path, $neighbor];
        }
    }
}

print_r($route);');

INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code)
VALUES ('hard', 'Окна активности', 'Объедините соседние события пользователя в сессии: новая сессия начинается после перерыва более 30 минут.', '<?php

$events = [
    ''2026-09-09 09:00:00'',
    ''2026-09-09 09:20:00'',
    ''2026-09-09 10:05:00'',
    ''2026-09-09 10:25:00'',
];', '<?php

$events = [
    ''2026-09-09 09:00:00'',
    ''2026-09-09 09:20:00'',
    ''2026-09-09 10:05:00'',
    ''2026-09-09 10:25:00'',
];
$sessions = [];

foreach ($events as $event) {
    $timestamp = strtotime($event);
    $lastIndex = array_key_last($sessions);

    if ($lastIndex === null || $timestamp - $sessions[$lastIndex][''end''] > 1800) {
        $sessions[] = [''start'' => $timestamp, ''end'' => $timestamp];
    } else {
        $sessions[$lastIndex][''end''] = $timestamp;
    }
}

foreach ($sessions as &$session) {
    $session[''start''] = date(''Y-m-d H:i:s'', $session[''start'']);
    $session[''end''] = date(''Y-m-d H:i:s'', $session[''end'']);
}
unset($session);

print_r($sessions);');

