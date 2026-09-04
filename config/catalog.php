<?php

declare(strict_types=1);

/**
 * Категории тренажёра и разрешённые имена функций.
 *
 * @return array<string, array{title: string, functions: list<string>}>
 */
return [
    'arrays' => [
        'title' => 'Массивы',
        'functions' => ['array_change_key_case', 'array_chunk', 'array_column', 'array_combine', 'array_count_values', 'array_diff', 'array_filter', 'array_flip', 'array_intersect', 'array_key_exists', 'array_keys', 'array_map', 'array_merge', 'array_multisort', 'array_pop', 'array_push', 'array_rand', 'array_reduce', 'array_replace', 'array_reverse', 'array_search', 'array_shift', 'array_slice', 'array_splice', 'array_sum', 'array_unique', 'array_unshift', 'array_values', 'array_walk', 'count', 'in_array', 'range', 'sort', 'usort'],
    ],
    'strings' => [
        'title' => 'Строки',
        'functions' => ['addslashes', 'chunk_split', 'explode', 'htmlspecialchars', 'implode', 'lcfirst', 'levenshtein', 'ltrim', 'md5', 'nl2br', 'number_format', 'parse_str', 'rtrim', 'sprintf', 'str_contains', 'str_ends_with', 'str_pad', 'str_repeat', 'str_replace', 'str_split', 'str_starts_with', 'strcasecmp', 'strchr', 'strcmp', 'strip_tags', 'stripos', 'stripslashes', 'strlen', 'strpos', 'strrev', 'strtolower', 'strtoupper', 'substr', 'substr_count', 'trim', 'ucfirst', 'ucwords', 'wordwrap'],
    ],
    'filesystem' => [
        'title' => 'Файловая система',
        'functions' => ['basename', 'copy', 'dirname', 'disk_free_space', 'file', 'file_exists', 'file_get_contents', 'file_put_contents', 'filesize', 'fopen', 'fread', 'fwrite', 'glob', 'is_dir', 'is_file', 'mkdir', 'pathinfo', 'rename', 'rmdir', 'scandir', 'tempnam', 'touch', 'unlink'],
    ],
    'datetime' => [
        'title' => 'Дата и время',
        'functions' => ['checkdate', 'date', 'date_create', 'date_diff', 'date_format', 'date_modify', 'getdate', 'gettimeofday', 'gmdate', 'idate', 'localtime', 'microtime', 'mktime', 'strtotime', 'time'],
    ],
    'json' => [
        'title' => 'JSON',
        'functions' => ['json_decode', 'json_encode', 'json_last_error', 'json_last_error_msg'],
    ],
    'math' => [
        'title' => 'Математика',
        'functions' => ['abs', 'base_convert', 'ceil', 'cos', 'deg2rad', 'floor', 'fmod', 'hexdec', 'is_finite', 'is_nan', 'max', 'min', 'mt_rand', 'pi', 'pow', 'rad2deg', 'round', 'sin', 'sqrt'],
    ],
    'variables' => [
        'title' => 'Переменные и типы',
        'functions' => ['boolval', 'empty', 'floatval', 'get_debug_type', 'gettype', 'intval', 'is_array', 'is_bool', 'is_callable', 'is_float', 'is_int', 'is_null', 'is_numeric', 'is_object', 'is_resource', 'is_scalar', 'is_string', 'isset', 'print_r', 'serialize', 'strval', 'unserialize', 'var_dump'],
    ],
    'regex' => [
        'title' => 'Регулярные выражения',
        'functions' => ['preg_filter', 'preg_grep', 'preg_last_error', 'preg_match', 'preg_match_all', 'preg_quote', 'preg_replace', 'preg_replace_callback', 'preg_split'],
    ],
    'url' => [
        'title' => 'URL и HTTP',
        'functions' => ['base64_decode', 'base64_encode', 'get_headers', 'header', 'http_build_query', 'parse_url', 'rawurldecode', 'rawurlencode', 'setcookie', 'urldecode', 'urlencode'],
    ],
    'functions' => [
        'title' => 'Функции',
        'functions' => ['call_user_func', 'call_user_func_array', 'function_exists', 'get_defined_functions', 'register_shutdown_function'],
    ],
];
