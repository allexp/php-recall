<?php

declare(strict_types=1);

namespace App\Service;

use PDO;

/** Хранит практические задачи и варианты решений в SQLite. */
final class TaskStore
{
    private ?PDO $connection = null;

    private const TASKS = [
        ['easy', 'Сумма чётных чисел', 'Дан массив целых чисел. Выведите сумму только чётных элементов.', "<?php\n\n\$numbers = [3, 8, 12, 5, 7, 4];", "<?php\n\n\$numbers = [3, 8, 12, 5, 7, 4];\n\$sum = array_sum(array_filter(\$numbers, fn (int \$number): bool => \$number % 2 === 0));\necho \$sum;"],
        ['easy', 'Разворот слов', 'Разверните порядок слов в строке и выведите результат.', "<?php\n\n\$text = 'PHP нравится разработчикам';", "<?php\n\n\$text = 'PHP нравится разработчикам';\necho implode(' ', array_reverse(explode(' ', \$text)));"],
        ['medium', 'Группировка пользователей', 'Сгруппируйте имена пользователей по полю role и выведите результат через print_r.', "<?php\n\n\$users = [['name' => 'Анна', 'role' => 'admin'], ['name' => 'Борис', 'role' => 'user'], ['name' => 'Вера', 'role' => 'admin']];", "<?php\n\n\$users = [['name' => 'Анна', 'role' => 'admin'], ['name' => 'Борис', 'role' => 'user'], ['name' => 'Вера', 'role' => 'admin']];\n\$grouped = [];\nforeach (\$users as \$user) { \$grouped[\$user['role']][] = \$user['name']; }\nprint_r(\$grouped);"],
        ['medium', 'Частота значений', 'Подсчитайте частоту слов без учёта регистра и отсортируйте результат по убыванию.', "<?php\n\n\$words = ['PHP', 'code', 'php', 'Test', 'code', 'php'];", "<?php\n\n\$words = ['PHP', 'code', 'php', 'Test', 'code', 'php'];\n\$frequency = array_count_values(array_map('strtolower', \$words));\narsort(\$frequency);\nprint_r(\$frequency);"],
        ['hard', 'Слияние интервалов', 'Объедините пересекающиеся интервалы и выведите итоговый массив.', "<?php\n\n\$intervals = [[1, 3], [2, 6], [8, 10], [9, 12], [15, 18]];", "<?php\n\n\$intervals = [[1, 3], [2, 6], [8, 10], [9, 12], [15, 18]];\nusort(\$intervals, fn (array \$a, array \$b): int => \$a[0] <=> \$b[0]);\n\$merged = [];\nforeach (\$intervals as \$interval) {\n    \$last = count(\$merged) - 1;\n    if (\$last < 0 || \$merged[\$last][1] < \$interval[0]) { \$merged[] = \$interval; continue; }\n    \$merged[\$last][1] = max(\$merged[\$last][1], \$interval[1]);\n}\nprint_r(\$merged);"],
        ['hard', 'Обход дерева', 'Получите плоский список названий узлов в порядке обхода дерева в глубину.', "<?php\n\n\$tree = [['name' => 'A', 'children' => [['name' => 'B', 'children' => []], ['name' => 'C', 'children' => []]]]];", "<?php\n\n\$tree = [['name' => 'A', 'children' => [['name' => 'B', 'children' => []], ['name' => 'C', 'children' => []]]]];\n\$walk = function (array \$nodes) use (&\$walk): array {\n    \$result = [];\n    foreach (\$nodes as \$node) { \$result[] = \$node['name']; array_push(\$result, ...\$walk(\$node['children'])); }\n    return \$result;\n};\nprint_r(\$walk(\$tree));"],
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
