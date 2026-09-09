<?php

declare(strict_types=1);

namespace App\Service;

use PDO;

/** Хранит практические задачи и варианты решений в SQLite. */
final class TaskStore
{
    private ?PDO $connection = null;

    private const TASKS = [
        [
            'easy',
            'Сумма чётных чисел',
            'Дан массив целых чисел. Выведите сумму только чётных элементов.',
            <<<'PHP'
<?php

$numbers = [3, 8, 12, 5, 7, 4];
PHP,
            <<<'PHP'
<?php

$numbers = [3, 8, 12, 5, 7, 4];
$sum = array_sum(array_filter(
    $numbers,
    fn (int $number): bool => $number % 2 === 0,
));

echo $sum;
PHP,
        ],
        [
            'easy',
            'Разворот слов',
            'Разверните порядок слов в строке и выведите результат.',
            <<<'PHP'
<?php

$text = 'PHP нравится разработчикам';
PHP,
            <<<'PHP'
<?php

$text = 'PHP нравится разработчикам';
$words = explode(' ', $text);

echo implode(' ', array_reverse($words));
PHP,
        ],
        [
            'medium',
            'Группировка пользователей',
            'Сгруппируйте имена пользователей по полю role и выведите результат через print_r.',
            <<<'PHP'
<?php

$users = [
    ['name' => 'Анна', 'role' => 'admin'],
    ['name' => 'Борис', 'role' => 'user'],
    ['name' => 'Вера', 'role' => 'admin'],
];
PHP,
            <<<'PHP'
<?php

$users = [
    ['name' => 'Анна', 'role' => 'admin'],
    ['name' => 'Борис', 'role' => 'user'],
    ['name' => 'Вера', 'role' => 'admin'],
];
$grouped = [];

foreach ($users as $user) {
    $grouped[$user['role']][] = $user['name'];
}

print_r($grouped);
PHP,
        ],
        [
            'medium',
            'Частота значений',
            'Подсчитайте частоту слов без учёта регистра и отсортируйте результат по убыванию.',
            <<<'PHP'
<?php

$words = ['PHP', 'code', 'php', 'Test', 'code', 'php'];
PHP,
            <<<'PHP'
<?php

$words = ['PHP', 'code', 'php', 'Test', 'code', 'php'];
$normalizedWords = array_map('strtolower', $words);
$frequency = array_count_values($normalizedWords);
arsort($frequency);

print_r($frequency);
PHP,
        ],
        [
            'hard',
            'Слияние интервалов',
            'Объедините пересекающиеся интервалы и выведите итоговый массив.',
            <<<'PHP'
<?php

$intervals = [[1, 3], [2, 6], [8, 10], [9, 12], [15, 18]];
PHP,
            <<<'PHP'
<?php

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

print_r($merged);
PHP,
        ],
        [
            'hard',
            'Обход дерева',
            'Получите плоский список названий узлов в порядке обхода дерева в глубину.',
            <<<'PHP'
<?php

$tree = [
    [
        'name' => 'A',
        'children' => [
            ['name' => 'B', 'children' => []],
            ['name' => 'C', 'children' => []],
        ],
    ],
];
PHP,
            <<<'PHP'
<?php

$tree = [
    [
        'name' => 'A',
        'children' => [
            ['name' => 'B', 'children' => []],
            ['name' => 'C', 'children' => []],
        ],
    ],
];
$walk = function (array $nodes) use (&$walk): array {
    $result = [];

    foreach ($nodes as $node) {
        $result[] = $node['name'];
        array_push($result, ...$walk($node['children']));
    }

    return $result;
};

print_r($walk($tree));
PHP,
        ],
        [
            'easy',
            'Количество положительных чисел',
            'Посчитайте положительные числа в массиве и выведите их количество.',
            <<<'PHP'
<?php

$numbers = [-3, 0, 7, 12, -5, 4];
PHP,
            <<<'PHP'
<?php

$numbers = [-3, 0, 7, 12, -5, 4];
$positiveNumbers = array_filter(
    $numbers,
    fn (int $number): bool => $number > 0,
);

echo count($positiveNumbers);
PHP,
        ],
        [
            'easy',
            'Имена с заглавной буквы',
            'Преобразуйте каждое имя так, чтобы оно начиналось с заглавной буквы, и выведите массив.',
            <<<'PHP'
<?php

$names = ['анна', 'борис', 'вера'];
PHP,
            <<<'PHP'
<?php

$names = ['анна', 'борис', 'вера'];
$formattedNames = array_map(
    fn (string $name): string => mb_convert_case($name, MB_CASE_TITLE, 'UTF-8'),
    $names,
);

print_r($formattedNames);
PHP,
        ],
        [
            'easy',
            'Удаление повторов',
            'Удалите повторяющиеся значения из массива, восстановите последовательные индексы и выведите результат.',
            <<<'PHP'
<?php

$colors = ['red', 'blue', 'red', 'green', 'blue'];
PHP,
            <<<'PHP'
<?php

$colors = ['red', 'blue', 'red', 'green', 'blue'];
$uniqueColors = array_values(array_unique($colors));

print_r($uniqueColors);
PHP,
        ],
        [
            'easy',
            'Самое длинное слово',
            'Найдите самое длинное слово в массиве и выведите его.',
            <<<'PHP'
<?php

$words = ['код', 'функция', 'массив', 'переменная'];
PHP,
            <<<'PHP'
<?php

$words = ['код', 'функция', 'массив', 'переменная'];
$longestWord = '';

foreach ($words as $word) {
    if (mb_strlen($word) > mb_strlen($longestWord)) {
        $longestWord = $word;
    }
}

echo $longestWord;
PHP,
        ],
        [
            'easy',
            'Проверка палиндрома',
            'Определите, является ли слово палиндромом, и выведите «да» или «нет».',
            <<<'PHP'
<?php

$word = 'топот';
PHP,
            <<<'PHP'
<?php

$word = 'топот';
$characters = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);
$isPalindrome = $characters === array_reverse($characters);

echo $isPalindrome ? 'да' : 'нет';
PHP,
        ],
        [
            'easy',
            'Среднее арифметическое',
            'Вычислите среднее арифметическое чисел массива и выведите результат.',
            <<<'PHP'
<?php

$numbers = [6, 8, 10, 12];
PHP,
            <<<'PHP'
<?php

$numbers = [6, 8, 10, 12];
$average = array_sum($numbers) / count($numbers);

echo $average;
PHP,
        ],
        [
            'easy',
            'Фильтр коротких строк',
            'Оставьте строки длиной не менее пяти символов и выведите массив с последовательными индексами.',
            <<<'PHP'
<?php

$items = ['PHP', 'Laravel', 'код', 'Symfony', 'тест'];
PHP,
            <<<'PHP'
<?php

$items = ['PHP', 'Laravel', 'код', 'Symfony', 'тест'];
$longItems = array_values(array_filter(
    $items,
    fn (string $item): bool => mb_strlen($item) >= 5,
));

print_r($longItems);
PHP,
        ],
        [
            'easy',
            'Таблица квадратов',
            'Создайте массив квадратов чисел от 1 до 5 и выведите его.',
            <<<'PHP'
<?php

$numbers = range(1, 5);
PHP,
            <<<'PHP'
<?php

$numbers = range(1, 5);
$squares = array_map(
    fn (int $number): int => $number ** 2,
    $numbers,
);

print_r($squares);
PHP,
        ],
        [
            'easy',
            'Поиск подстроки',
            'Проверьте, встречается ли слово PHP в тексте без учёта регистра, и выведите «найдено» или «не найдено».',
            <<<'PHP'
<?php

$text = 'Изучаем php функции';
PHP,
            <<<'PHP'
<?php

$text = 'Изучаем php функции';
$containsPhp = stripos($text, 'PHP') !== false;

echo $containsPhp ? 'найдено' : 'не найдено';
PHP,
        ],
        [
            'easy',
            'Объединение имён',
            'Объедините имена в строку через запятую и пробел и выведите результат.',
            <<<'PHP'
<?php

$names = ['Анна', 'Борис', 'Вера'];
PHP,
            <<<'PHP'
<?php

$names = ['Анна', 'Борис', 'Вера'];

echo implode(', ', $names);
PHP,
        ],
        [
            'medium',
            'Сумма заказов по клиентам',
            'Подсчитайте общую сумму заказов каждого клиента и выведите ассоциативный массив.',
            <<<'PHP'
<?php

$orders = [
    ['customer' => 'Анна', 'total' => 1200],
    ['customer' => 'Борис', 'total' => 900],
    ['customer' => 'Анна', 'total' => 450],
];
PHP,
            <<<'PHP'
<?php

$orders = [
    ['customer' => 'Анна', 'total' => 1200],
    ['customer' => 'Борис', 'total' => 900],
    ['customer' => 'Анна', 'total' => 450],
];
$totals = [];

foreach ($orders as $order) {
    $customer = $order['customer'];
    $totals[$customer] = ($totals[$customer] ?? 0) + $order['total'];
}

print_r($totals);
PHP,
        ],
        [
            'medium',
            'Сортировка товаров',
            'Отсортируйте товары сначала по возрастанию цены, а при равной цене — по названию.',
            <<<'PHP'
<?php

$products = [
    ['name' => 'Клавиатура', 'price' => 3000],
    ['name' => 'Мышь', 'price' => 1500],
    ['name' => 'Коврик', 'price' => 1500],
];
PHP,
            <<<'PHP'
<?php

$products = [
    ['name' => 'Клавиатура', 'price' => 3000],
    ['name' => 'Мышь', 'price' => 1500],
    ['name' => 'Коврик', 'price' => 1500],
];

usort($products, function (array $left, array $right): int {
    return [$left['price'], $left['name']] <=> [$right['price'], $right['name']];
});

print_r($products);
PHP,
        ],
        [
            'medium',
            'Индекс пользователей',
            'Постройте ассоциативный массив пользователей, где ключом служит id, и выведите его.',
            <<<'PHP'
<?php

$users = [
    ['id' => 10, 'name' => 'Анна'],
    ['id' => 25, 'name' => 'Борис'],
];
PHP,
            <<<'PHP'
<?php

$users = [
    ['id' => 10, 'name' => 'Анна'],
    ['id' => 25, 'name' => 'Борис'],
];
$usersById = array_column($users, null, 'id');

print_r($usersById);
PHP,
        ],
        [
            'medium',
            'Пересечение тегов',
            'Найдите общие теги двух списков без повторов и выведите массив с последовательными индексами.',
            <<<'PHP'
<?php

$firstTags = ['php', 'backend', 'api', 'php'];
$secondTags = ['api', 'testing', 'php'];
PHP,
            <<<'PHP'
<?php

$firstTags = ['php', 'backend', 'api', 'php'];
$secondTags = ['api', 'testing', 'php'];
$commonTags = array_values(array_unique(array_intersect($firstTags, $secondTags)));

print_r($commonTags);
PHP,
        ],
        [
            'medium',
            'Нормализация телефона',
            'Удалите из номера все символы, кроме цифр, и приведите российский номер к формату +7XXXXXXXXXX.',
            <<<'PHP'
<?php

$phone = '8 (999) 123-45-67';
PHP,
            <<<'PHP'
<?php

$phone = '8 (999) 123-45-67';
$digits = preg_replace('/\D+/', '', $phone);

if (strlen($digits) === 11 && $digits[0] === '8') {
    $digits = '7'.substr($digits, 1);
}

echo '+'.$digits;
PHP,
        ],
        [
            'medium',
            'Разбиение на страницы',
            'Разбейте массив записей на страницы по три элемента и выведите вторую страницу.',
            <<<'PHP'
<?php

$records = range(1, 10);
$pageSize = 3;
$page = 2;
PHP,
            <<<'PHP'
<?php

$records = range(1, 10);
$pageSize = 3;
$page = 2;
$offset = ($page - 1) * $pageSize;
$pageRecords = array_slice($records, $offset, $pageSize);

print_r($pageRecords);
PHP,
        ],
        [
            'medium',
            'Баланс операций',
            'Вычислите итоговый баланс по операциям типов income и expense и выведите его.',
            <<<'PHP'
<?php

$operations = [
    ['type' => 'income', 'amount' => 5000],
    ['type' => 'expense', 'amount' => 1200],
    ['type' => 'expense', 'amount' => 800],
];
PHP,
            <<<'PHP'
<?php

$operations = [
    ['type' => 'income', 'amount' => 5000],
    ['type' => 'expense', 'amount' => 1200],
    ['type' => 'expense', 'amount' => 800],
];
$balance = array_reduce(
    $operations,
    function (int $balance, array $operation): int {
        $sign = $operation['type'] === 'income' ? 1 : -1;

        return $balance + $sign * $operation['amount'];
    },
    0,
);

echo $balance;
PHP,
        ],
        [
            'medium',
            'Проверка скобок',
            'Проверьте правильность расстановки круглых скобок в строке и выведите «корректно» или «ошибка».',
            <<<'PHP'
<?php

$expression = '(a + b) * (c - (d / 2))';
PHP,
            <<<'PHP'
<?php

$expression = '(a + b) * (c - (d / 2))';
$balance = 0;
$isValid = true;

foreach (str_split($expression) as $character) {
    if ($character === '(') {
        $balance++;
    } elseif ($character === ')') {
        $balance--;
    }

    if ($balance < 0) {
        $isValid = false;
        break;
    }
}

$isValid = $isValid && $balance === 0;
echo $isValid ? 'корректно' : 'ошибка';
PHP,
        ],
        [
            'medium',
            'Сводка оценок',
            'Для каждого студента вычислите среднюю оценку, округлённую до одного знака, и выведите результат.',
            <<<'PHP'
<?php

$grades = [
    'Анна' => [5, 4, 5],
    'Борис' => [3, 4, 4],
];
PHP,
            <<<'PHP'
<?php

$grades = [
    'Анна' => [5, 4, 5],
    'Борис' => [3, 4, 4],
];
$averages = [];

foreach ($grades as $student => $studentGrades) {
    $averages[$student] = round(
        array_sum($studentGrades) / count($studentGrades),
        1,
    );
}

print_r($averages);
PHP,
        ],
        [
            'medium',
            'Параметры URL',
            'Добавьте параметры page и sort к URL, сохранив существующие параметры, и выведите новый URL.',
            <<<'PHP'
<?php

$url = 'https://example.com/catalog?category=books';
$newParameters = ['page' => 2, 'sort' => 'price'];
PHP,
            <<<'PHP'
<?php

$url = 'https://example.com/catalog?category=books';
$newParameters = ['page' => 2, 'sort' => 'price'];
$parts = parse_url($url);
parse_str($parts['query'] ?? '', $parameters);
$parameters = array_merge($parameters, $newParameters);
$result = $parts['scheme'].'://'.$parts['host'].$parts['path'];
$result .= '?'.http_build_query($parameters);

echo $result;
PHP,
        ],
        [
            'hard',
            'Группировка анаграмм',
            'Сгруппируйте слова-анаграммы и выведите массив групп.',
            <<<'PHP'
<?php

$words = ['кот', 'ток', 'рост', 'сорт', 'кто', 'сон'];
PHP,
            <<<'PHP'
<?php

$words = ['кот', 'ток', 'рост', 'сорт', 'кто', 'сон'];
$groups = [];

foreach ($words as $word) {
    $characters = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);
    sort($characters);
    $key = implode('', $characters);
    $groups[$key][] = $word;
}

print_r(array_values($groups));
PHP,
        ],
        [
            'hard',
            'Медиана массива',
            'Вычислите медиану числового массива для чётного и нечётного количества элементов.',
            <<<'PHP'
<?php

$numbers = [12, 3, 5, 7, 4, 19];
PHP,
            <<<'PHP'
<?php

$numbers = [12, 3, 5, 7, 4, 19];
sort($numbers);
$count = count($numbers);
$middle = intdiv($count, 2);
$median = $count % 2 === 1
    ? $numbers[$middle]
    : ($numbers[$middle - 1] + $numbers[$middle]) / 2;

echo $median;
PHP,
        ],
        [
            'hard',
            'Топ популярных товаров',
            'Подсчитайте количество продаж каждого товара, отсортируйте по убыванию и выведите три самых популярных.',
            <<<'PHP'
<?php

$sales = ['Книга', 'Чашка', 'Книга', 'Ручка', 'Чашка', 'Книга', 'Блокнот'];
PHP,
            <<<'PHP'
<?php

$sales = ['Книга', 'Чашка', 'Книга', 'Ручка', 'Чашка', 'Книга', 'Блокнот'];
$popularity = array_count_values($sales);
arsort($popularity);
$topProducts = array_slice($popularity, 0, 3, true);

print_r($topProducts);
PHP,
        ],
        [
            'hard',
            'Плоский массив категорий',
            'Преобразуйте дерево категорий в плоский массив строк с отступами, отражающими глубину.',
            <<<'PHP'
<?php

$categories = [
    [
        'name' => 'Техника',
        'children' => [
            ['name' => 'Ноутбуки', 'children' => []],
            ['name' => 'Телефоны', 'children' => []],
        ],
    ],
];
PHP,
            <<<'PHP'
<?php

$categories = [
    [
        'name' => 'Техника',
        'children' => [
            ['name' => 'Ноутбуки', 'children' => []],
            ['name' => 'Телефоны', 'children' => []],
        ],
    ],
];
$flatten = function (array $nodes, int $depth = 0) use (&$flatten): array {
    $result = [];

    foreach ($nodes as $node) {
        $result[] = str_repeat('  ', $depth).$node['name'];
        array_push($result, ...$flatten($node['children'], $depth + 1));
    }

    return $result;
};

print_r($flatten($categories));
PHP,
        ],
        [
            'hard',
            'Самая длинная последовательность',
            'Найдите длину самой длинной последовательности подряд идущих целых чисел в неотсортированном массиве.',
            <<<'PHP'
<?php

$numbers = [100, 4, 200, 1, 3, 2, 5];
PHP,
            <<<'PHP'
<?php

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

echo $longest;
PHP,
        ],
        [
            'hard',
            'Вложенные параметры',
            'Преобразуйте пары с точечной нотацией ключей во вложенный ассоциативный массив.',
            <<<'PHP'
<?php

$values = [
    'user.name' => 'Анна',
    'user.address.city' => 'Казань',
    'settings.theme' => 'dark',
];
PHP,
            <<<'PHP'
<?php

$values = [
    'user.name' => 'Анна',
    'user.address.city' => 'Казань',
    'settings.theme' => 'dark',
];
$result = [];

foreach ($values as $path => $value) {
    $target = &$result;

    foreach (explode('.', $path) as $key) {
        $target = &$target[$key];
    }

    $target = $value;
    unset($target);
}

print_r($result);
PHP,
        ],
        [
            'hard',
            'Проверка судоку',
            'Проверьте, нет ли повторяющихся ненулевых чисел в строках и столбцах поля судоку.',
            <<<'PHP'
<?php

$board = [
    [5, 3, 0, 0],
    [0, 0, 4, 2],
    [3, 0, 0, 0],
    [0, 1, 0, 0],
];
PHP,
            <<<'PHP'
<?php

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

echo $isValid ? 'корректно' : 'ошибка';
PHP,
        ],
        [
            'hard',
            'Разница конфигураций',
            'Найдите значения, которые отличаются в двух вложенных конфигурациях, сохранив путь к каждому отличию.',
            <<<'PHP'
<?php

$old = ['app' => ['debug' => false, 'locale' => 'ru'], 'cache' => true];
$new = ['app' => ['debug' => true, 'locale' => 'ru'], 'cache' => false];
PHP,
            <<<'PHP'
<?php

$old = ['app' => ['debug' => false, 'locale' => 'ru'], 'cache' => true];
$new = ['app' => ['debug' => true, 'locale' => 'ru'], 'cache' => false];
$compare = function (array $left, array $right, string $prefix = '') use (&$compare): array {
    $differences = [];

    foreach (array_unique(array_merge(array_keys($left), array_keys($right))) as $key) {
        $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
        $leftValue = $left[$key] ?? null;
        $rightValue = $right[$key] ?? null;

        if (is_array($leftValue) && is_array($rightValue)) {
            $differences += $compare($leftValue, $rightValue, $path);
        } elseif ($leftValue !== $rightValue) {
            $differences[$path] = ['old' => $leftValue, 'new' => $rightValue];
        }
    }

    return $differences;
};

print_r($compare($old, $new));
PHP,
        ],
        [
            'hard',
            'Маршрут в графе',
            'Найдите кратчайший по числу рёбер маршрут между двумя вершинами невзвешенного графа.',
            <<<'PHP'
<?php

$graph = [
    'A' => ['B', 'C'],
    'B' => ['D'],
    'C' => ['D', 'E'],
    'D' => ['F'],
    'E' => ['F'],
    'F' => [],
];
$start = 'A';
$finish = 'F';
PHP,
            <<<'PHP'
<?php

$graph = [
    'A' => ['B', 'C'],
    'B' => ['D'],
    'C' => ['D', 'E'],
    'D' => ['F'],
    'E' => ['F'],
    'F' => [],
];
$start = 'A';
$finish = 'F';
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

print_r($route);
PHP,
        ],
        [
            'hard',
            'Окна активности',
            'Объедините соседние события пользователя в сессии: новая сессия начинается после перерыва более 30 минут.',
            <<<'PHP'
<?php

$events = [
    '2026-09-09 09:00:00',
    '2026-09-09 09:20:00',
    '2026-09-09 10:05:00',
    '2026-09-09 10:25:00',
];
PHP,
            <<<'PHP'
<?php

$events = [
    '2026-09-09 09:00:00',
    '2026-09-09 09:20:00',
    '2026-09-09 10:05:00',
    '2026-09-09 10:25:00',
];
$sessions = [];

foreach ($events as $event) {
    $timestamp = strtotime($event);
    $lastIndex = array_key_last($sessions);

    if ($lastIndex === null || $timestamp - $sessions[$lastIndex]['end'] > 1800) {
        $sessions[] = ['start' => $timestamp, 'end' => $timestamp];
    } else {
        $sessions[$lastIndex]['end'] = $timestamp;
    }
}

foreach ($sessions as &$session) {
    $session['start'] = date('Y-m-d H:i:s', $session['start']);
    $session['end'] = date('Y-m-d H:i:s', $session['end']);
}
unset($session);

print_r($sessions);
PHP,
        ],
    ];

    public function __construct(private readonly string $databaseFile) {}

    /** Возвращает случайную задачу без решения. */
    public function random(string $difficulty): ?array
    {
        $statement = $this->connection()->prepare('SELECT id, difficulty, title, description, starter_code FROM tasks WHERE difficulty = :difficulty ORDER BY RANDOM() LIMIT 1');
        $statement->execute(['difficulty' => $difficulty]);
        $task = $statement->fetch();
        return is_array($task) ? $task : null;
    }

    /** Возвращает вариант решения по идентификатору задачи. */
    public function solution(int $id): ?string
    {
        $statement = $this->connection()->prepare('SELECT solution_code FROM tasks WHERE id = :id');
        $statement->execute(['id' => $id]);
        $solution = $statement->fetchColumn();
        return is_string($solution) ? $solution : null;
    }

    private function connection(): PDO
    {
        if ($this->connection) return $this->connection;
        $this->connection = new PDO('sqlite:'.$this->databaseFile, options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $this->connection->exec("CREATE TABLE IF NOT EXISTS tasks (id INTEGER PRIMARY KEY AUTOINCREMENT, difficulty TEXT NOT NULL CHECK (difficulty IN ('easy', 'medium', 'hard')), title TEXT NOT NULL, description TEXT NOT NULL, starter_code TEXT NOT NULL, solution_code TEXT NOT NULL, UNIQUE (difficulty, title))");
        $insert = $this->connection->prepare('INSERT OR IGNORE INTO tasks (difficulty, title, description, starter_code, solution_code) VALUES (?, ?, ?, ?, ?)');
        foreach (self::TASKS as $task) $insert->execute($task);
        return $this->connection;
    }
}
