<?php
// 1. .envファイルからAPIキーを読み込む簡易関数
function getEnvValue($key, $default = null) {
    if (!file_exists('.env')) return $default;
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        if (trim($name) === $key) return trim($value);
    }
    return $default;
}

$apiKey = getEnvValue('OPENAI_API_KEY');
$result = "";

// 2. フォーム送信時の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $userInput = $_POST['message'];
    
    $url = "https://api.openai.com/v1/chat/completions";
    
    // APIへのリクエストデータ
    $data = [
        "model" => "gpt-5-mini", // 指定のモデル名
        "messages" => [
            [
                "role" => "system",
                "content" => "与えられたテキストから最も重要な単語を1つだけ選び、以下の形式のプレーンテキストで返してください。\n原語: [単語]\n英訳: [English Translation]"
            ],
            [
                "role" => "user",
                "content" => $userInput
            ]
        ],
        "temperature" => 0.3
    ];

    // cURLによるリクエスト送信
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $apiKey"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $jsonResponse = json_decode($response, true);
        $result = $jsonResponse['choices'][0]['message']['content'];
    } else {
        $result = "Error: APIリクエストに失敗しました。コード: " . $httpCode . "\n" . $response;
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Chatbot - Word Extractor</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 2em auto; line-height: 1.6; }
        textarea { width: 100%; height: 100px; margin-bottom: 10px; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; white-space: pre-wrap; }
        .result-box { margin-top: 20px; border-left: 5px solid #333; padding-left: 15px; }
    </style>
</head>
<body>
    <h2>重要語抽出チャットボット</h2>
    <form method="post">
        <textarea name="message" placeholder="文章を入力してください..." required></textarea><br>
        <button type="submit">抽出する</button>
    </form>

    <?php if ($result): ?>
        <div class="result-box">
            <h3>結果:</h3>
            <pre><?php echo htmlspecialchars($result); ?></pre>
        </div>
    <?php endif; ?>
</body>
</html>