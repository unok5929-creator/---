<?php
session_start();

// 管理者のみアクセス可（ログイン認証がある場合）


// DB接続
$dsn = 'データベース名';
$user = 'ユーザー名';
$password = 'パスワード';
$pdo = new PDO($dsn, $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING
]);

// ユーザーIDから名前取得用
$usernames = [];
$stmt = $pdo->query("SELECT id, username FROM users");
while ($row = $stmt->fetch()) {
    $usernames[$row['id']] = $row['username'];
}

// 全シフト取得（ソート付き）
$stmt = $pdo->query("SELECT * FROM shifts ORDER BY date ASC, start_time ASC");
$shifts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>シフト希望一覧（管理者）</title>
  <style>
    table {
      border-collapse: collapse;
      width: 90%;
      margin: 20px auto;
    }
    th, td {
      border: 1px solid #999;
      padding: 8px;
      text-align: center;
    }
    th {
      background-color: #f0f0f0;
    }
    h2 {
      text-align: center;
    }
  </style>
</head>
<body>
  <h2>スタッフ出勤希望一覧</h2>

  <table>
    <tr>
      <th>名前</th>
      <th>日付</th>
      <th>開始時間</th>
      <th>終了時間</th>
    </tr>
    <?php foreach ($shifts as $s): ?>
      <tr>
        <td><?= htmlspecialchars($usernames[$s['user_id']] ?? '不明') ?></td>
        <td><?= htmlspecialchars($s['date']) ?></td>
        <td><?= htmlspecialchars($s['start_time']) ?></td>
        <td><?= htmlspecialchars($s['end_time']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</body>
</html>