<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$dsn = 'データベース名';
$user = 'ユーザー名';
$password = 'パスワード';
$pdo = new PDO($dsn, $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING
]);

// 出勤希望テーブル（初回のみ実行）
$sql = "
CREATE TABLE IF NOT EXISTS shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$pdo->exec($sql);

// 出勤可能時間の保存処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['shift_submit'])) {
    $date = $_POST['date'] ?? '';
    $start = $_POST['start_time'] ?? '';
    $end = $_POST['end_time'] ?? '';
    $userId = $_SESSION["user_id"];

    if ($date && $start && $end) {
        $stmt = $pdo->prepare("REPLACE INTO shifts (user_id, date, start_time, end_time) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $date, $start, $end]);
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>出勤可能時間 登録カレンダー</title>
  <style>
    .calendar {
      border-collapse: collapse;
      margin-bottom: 20px;
    }
    .calendar th, .calendar td {
      border: 1px solid #999;
      width: 120px;
      height: 120px;
      text-align: center;
      vertical-align: top;
      padding: 5px;
      font-size: 12px;
    }
    .calendar td.weekend {
      background-color: #f0f8ff;
    }
    .date-header {
      font-weight: bold;
      margin-bottom: 5px;
    }
    .multi-calendar .month-block {
      display: inline-block;
      vertical-align: top;
      margin: 10px;
    }
    input[type="time"] {
      width: 90%;
      margin: 2px 0;
    }
    button {
      font-size: 11px;
      padding: 2px 5px;
    }
  </style>
</head>
<body>
  <h2><?= htmlspecialchars($_SESSION["username"]) ?>さんの出勤希望</h2>

  <form method="post">
    <label>開始年:
      <input type="number" name="start_year" min="1900" max="2100"
        value="<?= htmlspecialchars($_POST['start_year'] ?? date('Y')) ?>">
    </label>
    <label>開始月:
      <select name="start_month">
        <?php foreach (range(1, 12) as $m): ?>
          <option value="<?= $m ?>"
            <?= ($m === (int)($_POST['start_month'] ?? date('n'))) ? 'selected' : '' ?>>
            <?= date('F', mktime(0,0,0,$m,1)) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>表示月数:
      <input type="number" name="num_months" min="1" max="12"
        value="<?= htmlspecialchars($_POST['num_months'] ?? '1') ?>">
    </label>
    <button type="submit">表示</button>
  </form>

<?php
// 値取得
$startYear  = isset($_POST['start_year'])  ? (int)$_POST['start_year']  : (int)date('Y');
$startMonth = isset($_POST['start_month']) ? (int)$_POST['start_month'] : (int)date('n');
$numMonths  = isset($_POST['num_months'])  ? max(1, min(12, (int)$_POST['num_months'])) : 1;
$userId = $_SESSION["user_id"];

function get_shift_for_date($pdo, $userId, $date) {
    $stmt = $pdo->prepare("SELECT start_time, end_time FROM shifts WHERE user_id = ? AND date = ?");
    $stmt->execute([$userId, $date]);
    return $stmt->fetch();
}

function draw_calendar($month, $year, $pdo, $userId) {
    $firstDay = date('N', strtotime("$year-$month-01")); // 1 = Monday
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

    echo "<table class='calendar'><tr>";
    foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d) echo "<th>$d</th>";
    echo "</tr><tr>";

    for ($i = 1; $i < $firstDay; $i++) echo "<td></td>";

    for ($day = 1; $day <= $daysInMonth; $day++) {
        $date = sprintf("%04d-%02d-%02d", $year, $month, $day);
        $shift = get_shift_for_date($pdo, $userId, $date);
        $wd = ($firstDay + $day - 2) % 7;
        $isWeekend = ($wd === 5 || $wd === 6);
        $class = $isWeekend ? 'weekend' : '';

        echo "<td class='$class'>";
        echo "<div class='date-header'>$day</div>";

        if ($shift) {
            echo "<div style='color:green;'>{$shift['start_time']}～{$shift['end_time']}</div>";
        }

        echo "<form method='post' style='font-size:10px;'>";
        echo "<input type='hidden' name='date' value='$date'>";
        echo "開始:<br><input type='time' name='start_time' value='".($shift['start_time'] ?? '')."' required><br>";
        echo "終了:<br><input type='time' name='end_time' value='".($shift['end_time'] ?? '')."' required><br>";
        echo "<button type='submit' name='shift_submit'>保存</button>";
        echo "</form>";

        echo "</td>";

        if ((($firstDay + $day - 1) % 7) === 0 && $day !== $daysInMonth) {
            echo "</tr><tr>";
        }
    }

    $endDay = ($firstDay + $daysInMonth - 1) % 7;
    if ($endDay !== 0) {
        for ($i = $endDay + 1; $i <= 7; $i++) echo "<td></td>";
    }

    echo "</tr></table>";
}

function draw_multi_calendar($startMonth, $startYear, $numMonths, $pdo, $userId) {
    echo "<div class='multi-calendar'>";
    for ($i = 0; $i < $numMonths; $i++) {
        $m = $startMonth + $i;
        $y = $startYear;
        if ($m > 12) { $m -= 12; $y++; }
        echo "<div class='month-block'>";
        echo "<h3>{$y}年{$m}月</h3>";
        draw_calendar($m, $y, $pdo, $userId);
        echo "</div>";
    }
    echo "</div>";
}

// カレンダー表示
draw_multi_calendar($startMonth, $startYear, $numMonths, $pdo, $userId);
?>

</body>
</html>