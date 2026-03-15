<?php
// Определяем константы подключения к БД
define('DB_HOST', 'MySQL-8.4');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'invitations');

// Включаем вывод ошибок для отладки (на время разработки)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$thank_you_message = "";
$message_type = ""; // 'success' или 'error'

try {
    // Создаем соединение
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    $conn->set_charset("utf8mb4");
    
    if (isset($_POST['rsvp_submit'])) {
        if (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['response'])) {
            $thank_you_message = "Пожалуйста, заполните все поля.";
            $message_type = "error";
        } else {
            $first_name = $conn->real_escape_string(trim($_POST['first_name']));
            $last_name = $conn->real_escape_string(trim($_POST['last_name']));
            
            // Получаем значение response из формы (приходит 'yes' или 'no')
            $response_raw = $_POST['response'];
            
            // Преобразуем английские значения в русские для базы данных
            if ($response_raw === 'yes') {
                $response = 'Да';
            } elseif ($response_raw === 'no') {
                $response = 'Нет';
            } else {
                $thank_you_message = "Некорректное значение ответа.";
                $message_type = "error";
                $response = null;
            }
            
            // Если все хорошо, сохраняем в базу (или обновляем существующую запись)
            if (isset($response)) {
                // Используем INSERT ... ON DUPLICATE KEY UPDATE для возможности изменения ответа
                $sql = "INSERT INTO guests (first_name, last_name, response, submitted_at) VALUES (?, ?, ?, NOW()) 
                        ON DUPLICATE KEY UPDATE response = VALUES(response), submitted_at = VALUES(submitted_at)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $first_name, $last_name, $response);
                
                if ($stmt->execute()) {
                    if ($stmt->affected_rows == 1) {
                        $thank_you_message = "Спасибо за ответ! Мы обязательно учтем его.";
                    } else {
                        $thank_you_message = "Ваш ответ обновлен! Спасибо, что сообщили.";
                    }
                    $message_type = "success";
                } else {
                    $thank_you_message = "Ошибка при сохранении.";
                    $message_type = "error";
                }
                $stmt->close();
            }
        }
    }
    
    $conn->close();
    
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $thank_you_message = "Извините, произошла техническая ошибка. Пожалуйста, попробуйте позже.";
    $message_type = "error";
    // Для отладки можно раскомментировать:
    // $thank_you_message = "Ошибка: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Александр и Валерия | Свадебное приглашение</title>
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
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            background-color: #ffffff;
            box-shadow: 0 0 30px rgba(0,0,0,0.05);
        }

        /* Типографика */
        h1 {
            font-size: 3.5rem;
            font-weight: normal;
            letter-spacing: 2px;
            margin-bottom: 0.5rem;
            text-align: center;
            font-family: 'Playfair Display', 'Georgia', serif;
        }

        h2 {
            font-size: 2rem;
            font-weight: normal;
            margin: 2rem 0 1rem;
            text-align: center;
            position: relative;
            font-family: 'Playfair Display', 'Georgia', serif;
        }

        h2:after {
            content: "";
            display: block;
            width: 60px;
            height: 1px;
            background: #d4b7a1;
            margin: 1rem auto 0;
        }

        h3 {
            font-size: 1.3rem;
            font-weight: normal;
            margin: 1.5rem 0 0.5rem;
            color: #8b6b5a;
        }

        .subtitle {
            text-align: center;
            font-size: 1.1rem;
            color: #8b6b5a;
            margin-bottom: 2rem;
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        .date-large {
            text-align: center;
            font-size: 2.5rem;
            margin: 1.5rem 0;
            color: #8b6b5a;
            font-family: 'Playfair Display', serif;
        }

        /* Секции */
        .section {
            margin: 3rem 0;
            padding: 1rem 0;
            border-bottom: 1px dashed #e5d6cc;
        }

        .section:last-child {
            border-bottom: none;
        }

        /* Сетка для расписания */
        .schedule-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin: 2rem 0;
        }

        .schedule-item {
            background: #f9f5f0;
            padding: 1.5rem;
            border-radius: 4px;
        }

        .schedule-item h3 {
            margin-top: 0;
            color: #5d3f2e;
        }

        .schedule-item p {
            margin-bottom: 0.3rem;
        }

        .time {
            font-size: 1.3rem;
            font-weight: bold;
            color: #8b6b5a;
            margin: 0.5rem 0;
        }

        /* Дресс-код */
        .dress-code {
            background: #f1ebe6;
            padding: 2rem;
            border-radius: 8px;
            text-align: center;
        }

        .color-palette {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .color-dot {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .color-dot.beige { background: #eedcc9; }
        .color-dot.green { background: #a8b5a1; }
        .color-dot.brown { background: #8b6b5a; }
        .color-dot.dusty { background: #c4a69c; }

        /* Детали */
        .details-list {
            list-style: none;
        }

        .details-list li {
            margin-bottom: 1rem;
            padding-left: 1.5rem;
            position: relative;
        }

        .details-list li:before {
            content: "•";
            color: #d4b7a1;
            font-size: 1.5rem;
            position: absolute;
            left: 0;
            top: -5px;
        }

        /* Форма */
        .rsvp-form {
            background: #f9f5f0;
            padding: 2rem;
            border-radius: 8px;
            max-width: 500px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #5d3f2e;
        }

        input[type="text"] {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #e5d6cc;
            border-radius: 4px;
            font-family: inherit;
            font-size: 1rem;
        }

        .radio-group {
            display: flex;
            gap: 2rem;
            margin: 1rem 0;
        }

        .radio-group label {
            display: inline;
            margin-left: 0.3rem;
        }

        button {
            background-color: #8b6b5a;
            color: white;
            border: none;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            border-radius: 40px;
            cursor: pointer;
            width: 100%;
            font-family: inherit;
            transition: background 0.3s;
        }

        button:hover {
            background-color: #5d3f2e;
        }

        .message {
            text-align: center;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }

        .message.success {
            background-color: #e1f0e1;
            color: #2c5e2c;
        }

        .message.error {
            background-color: #ffe5e5;
            color: #a94442;
        }

        /* Таймер */
        .timer-section {
            text-align: center;
            background: #f1ebe6;
            padding: 2rem;
            border-radius: 8px;
        }

        .timer-display {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .timer-item {
            text-align: center;
        }

        .timer-number {
            font-size: 2.5rem;
            font-family: 'Playfair Display', serif;
            color: #8b6b5a;
            font-weight: bold;
            min-width: 60px;
            display: inline-block;
        }

        .timer-label {
            font-size: 0.9rem;
            color: #8b6b5a;
            text-transform: lowercase;
        }

        /* Адаптивность */
        @media (max-width: 600px) {
            .container { padding: 1rem; }
            h1 { font-size: 2.5rem; }
            .schedule-grid { grid-template-columns: 1fr; }
            .radio-group { flex-direction: column; gap: 0.5rem; }
            .form-row { grid-template-columns: 1fr; }
            .timer-number { font-size: 2rem; }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1>АЛЕКСАНДР<br>И ВАЛЕРИЯ</h1>
        <div class="subtitle">ПРИГЛАШЕНИЕ НА СВАДЬБУ</div>
        <div class="date-large">12.06.2026</div>

        <div class="section" style="text-align: center;">
            <h2>Дорогие гости!</h2>
            <p style="font-size: 1.2rem; max-width: 600px; margin: 0 auto;">
                Один день в этом году будет для нас особенным,<br>
                и мы хотим разделить его с вами.
            </p>
        </div>

        <div class="section">
            <h2>Ждём вас:</h2>
            <div class="schedule-grid">
                <div class="schedule-item">
                    <h3>Торжественная регистрация</h3>
                    <p>Дворец Бракосочетания 2</p>
                    <p style="color: #6b4f3c;">Английская набережная, 28</p>
                    <div class="time">17:00</div>
                </div>
                <div class="schedule-item">
                    <h3>Праздничный банкет</h3>
                    <p>Ресторан "Летний дворец"</p>
                    <p style="color: #6b4f3c;">Санкт-Петербургское шоссе, 130 к7</p>
                    <div class="time">18:00</div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Дресс-код</h2>
            <div class="dress-code">
                <p>Пожалуйста, приходите в красивой одежде, которая соответствует торжественному настроению.</p>
                <p>Мы будем рады, если вы поддержите цветовую гамму нашей свадьбы в своих нарядах:</p>
                
                <div class="color-palette">
                    <div class="color-dot beige" title="Бежевый"></div>
                    <div class="color-dot green" title="Зелёный"></div>
                    <div class="color-dot brown" title="Коричневый"></div>
                    <div class="color-dot dusty" title="Пыльная роза"></div>
                </div>
                
                <p><small>Соблюдение дресс-кода желательно, но не обязательно.</small></p>
            </div>
        </div>

        <div class="section">
            <h2>Детали</h2>
            <ul class="details-list">
                <li><strong>Без детей:</strong> Подарите себе вечер свободы от родительских забот! Ждем вас без маленьких спутников.</li>
                <li><strong>Цветы или вино?</strong> Цветы завянут через несколько дней, а хорошим вином мы насладимся на годовщину. Будем рады бутылочке вашего любимого напитка вместо букета!</li>
                <li><strong>Без "Горько!":</strong> От всего сердца просим вас воздержаться от криков "Горько!" и сохранить атмосферу уютного семейного праздника.</li>
            </ul>
        </div>

        <div class="section">
            <h2>Анкета</h2>
            <p style="text-align: center; margin-bottom: 1.5rem;">Пожалуйста, подтвердите ваше присутствие до 01 июня 2026</p>
            
            <div class="rsvp-form">
                <?php if (!empty($thank_you_message)): ?>
                    <div class="message <?php echo $message_type; ?>">
                        <?php echo $thank_you_message; ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">Имя:</label>
                            <input type="text" id="first_name" name="first_name" required placeholder="Например, Анна">
                        </div>
                        <div class="form-group">
                            <label for="last_name">Фамилия:</label>
                            <input type="text" id="last_name" name="last_name" required placeholder="Например, Иванова">
                        </div>
                    </div>

                    <div class="form-group">
                        <p><strong>Вы сможете прийти?</strong></p>
                        <div class="radio-group">
                            <div>
                                <input type="radio" id="yes" name="response" value="yes" required>
                                <label for="yes">Да, с радостью!</label>
                            </div>
                            <div>
                                <input type="radio" id="no" name="response" value="no">
                                <label for="no">Нет, не получится</label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="rsvp_submit">Отправить ответ</button>
                </form>
                <p style="text-align: center; margin-top: 1rem; font-size: 0.9rem; color: #8b6b5a;">
                    *Если вы передумали, просто заполните форму ещё раз — ваш ответ будет обновлен.
                </p>
            </div>
        </div>

        <div class="section">
            <div class="timer-section">
                <p style="font-size: 1.2rem;">До окончания приёма подтверждений осталось</p>
                <div class="timer-display" id="countdown">
                    <div class="timer-item">
                        <div class="timer-number" id="days">0</div>
                        <div class="timer-label">дней</div>
                    </div>
                    <div class="timer-item">
                        <div class="timer-number" id="hours">0</div>
                        <div class="timer-label">часов</div>
                    </div>
                    <div class="timer-item">
                        <div class="timer-number" id="minutes">0</div>
                        <div class="timer-label">минут</div>
                    </div>
                    <div class="timer-item">
                        <div class="timer-number" id="seconds">0</div>
                        <div class="timer-label">секунд</div>
                    </div>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 3rem; color: #b8a99a; font-size: 0.9rem;">
            С любовью, Александр и Валерия
        </div>
    </div>

    <script>
        // Таймер до 1 июня 2026 года (дедлайн подтверждения)
        function updateCountdown() {
            const deadline = new Date('2026-06-01T23:59:59').getTime();
            const now = new Date().getTime();
            const distance = deadline - now;

            if (distance < 0) {
                document.getElementById('countdown').innerHTML = '<p style="font-size: 1.5rem; color: #8b6b5a;">Приём подтверждений завершён</p>';
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            document.getElementById('days').textContent = days;
            document.getElementById('hours').textContent = hours;
            document.getElementById('minutes').textContent = minutes;
            document.getElementById('seconds').textContent = seconds;
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);
    </script>
</body>
</html>
