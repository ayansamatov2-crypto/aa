<?php
// Определяем константы подключения к БД
define('DB_HOST', 'MySQL-8.4');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'invitations');

// Простая авторизация (пароль можно изменить)
define('ADMIN_PASSWORD', 'wedding2026');

session_start();

$is_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$error_message = '';

// Обработка входа
if (isset($_POST['login'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        $is_logged_in = true;
    } else {
        $error_message = 'Неверный пароль';
    }
}

// Обработка выхода
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

$guests = [];
$stats = ['yes' => 0, 'no' => 0, 'total' => 0];

if ($is_logged_in) {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        $conn->set_charset("utf8mb4");
        
        // Получаем всех гостей, отсортированных по дате (новые сверху)
        $result = $conn->query("SELECT * FROM guests ORDER BY submitted_at DESC");
        while ($row = $result->fetch_assoc()) {
            $guests[] = $row;
            if ($row['response'] === 'Да') {
                $stats['yes']++;
            } else {
                $stats['no']++;
            }
            $stats['total']++;
        }
        
        $conn->close();
    } catch (Exception $e) {
        $error_message = 'Ошибка подключения к базе данных: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель | Свадебное приглашение</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            background-color: #faf7f2;
            color: #2c2c2c;
            line-height: 1.6;
            padding: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
        }

        h1 {
            font-family: 'Playfair Display', 'Georgia', serif;
            color: #8b6b5a;
            margin-bottom: 1rem;
            text-align: center;
        }

        h2 {
            font-family: 'Playfair Display', 'Georgia', serif;
            color: #5d3f2e;
            margin: 2rem 0 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e5d6cc;
        }

        /* Статистика */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #f9f5f0;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #8b6b5a;
            font-family: 'Playfair Display', serif;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .stat-card.yes { border-left: 4px solid #4a7c59; }
        .stat-card.no { border-left: 4px solid #a94442; }
        .stat-card.total { border-left: 4px solid #8b6b5a; }

        /* Таблица */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5d6cc;
        }

        th {
            background-color: #f1ebe6;
            color: #5d3f2e;
            font-weight: bold;
        }

        tr:hover {
            background-color: #f9f5f0;
        }

        .response-yes {
            color: #4a7c59;
            font-weight: bold;
        }

        .response-no {
            color: #a94442;
            font-weight: bold;
        }

        /* Форма входа */
        .login-form {
            max-width: 400px;
            margin: 5rem auto;
            background: #f9f5f0;
            padding: 2rem;
            border-radius: 8px;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #5d3f2e;
        }

        input[type="password"] {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #e5d6cc;
            border-radius: 4px;
            font-family: inherit;
            font-size: 1rem;
        }

        button {
            background-color: #8b6b5a;
            color: white;
            border: none;
            padding: 1rem 2rem;
            font-size: 1rem;
            border-radius: 40px;
            cursor: pointer;
            width: 100%;
            font-family: inherit;
            transition: background 0.3s;
        }

        button:hover {
            background-color: #5d3f2e;
        }

        .error {
            background-color: #ffe5e5;
            color: #a94442;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .logout-btn {
            display: inline-block;
            background: #8b6b5a;
            color: white;
            text-decoration: none;
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .logout-btn:hover {
            background: #5d3f2e;
        }

        .empty-message {
            text-align: center;
            padding: 3rem;
            color: #888;
            font-style: italic;
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            body { padding: 1rem; }
            .container { padding: 1rem; }
            .stats-grid { grid-template-columns: 1fr; }
            table { font-size: 0.9rem; }
            th, td { padding: 0.5rem; }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1>Админ-панель</h1>
        <p style="text-align: center; color: #8b6b5a; margin-bottom: 2rem;">
            Свадьба Александра и Валерии — 12.06.2026
        </p>

        <?php if (!$is_logged_in): ?>
            
            <div class="login-form">
                <?php if ($error_message): ?>
                    <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="form-group">
                        <label for="password">Пароль:</label>
                        <input type="password" id="password" name="password" required placeholder="Введите пароль">
                    </div>
                    <button type="submit" name="login">Войти</button>
                </form>
            </div>

        <?php else: ?>
            
            <a href="?logout=1" class="logout-btn">Выйти</a>
            
            <h2>Статистика</h2>
            <div class="stats-grid">
                <div class="stat-card yes">
                    <div class="stat-number"><?php echo $stats['yes']; ?></div>
                    <div class="stat-label">Придут</div>
                </div>
                <div class="stat-card no">
                    <div class="stat-number"><?php echo $stats['no']; ?></div>
                    <div class="stat-label">Не придут</div>
                </div>
                <div class="stat-card total">
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Всего ответов</div>
                </div>
            </div>

            <h2>Список гостей</h2>
            
            <?php if (empty($guests)): ?>
                <div class="empty-message">Пока нет ни одного ответа</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Имя</th>
                            <th>Фамилия</th>
                            <th>Ответ</th>
                            <th>Дата и время</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($guests as $index => $guest): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($guest['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($guest['last_name']); ?></td>
                                <td class="<?php echo $guest['response'] === 'Да' ? 'response-yes' : 'response-no'; ?>">
                                    <?php echo $guest['response']; ?>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($guest['submitted_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</body>
</html>
