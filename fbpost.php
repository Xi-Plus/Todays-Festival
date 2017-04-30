<?php
require(__DIR__.'/config/config.php');
if (!in_array(PHP_SAPI, $C["allowsapi"])) {
	exit("No permission");
}

$month = date("n");
$date = date("j");

$message = "";

$html = file_get_contents("https://zh.wikipedia.org/zh-tw/".$month."月".$date."日?action=render");
$html = html_entity_decode($html);
$hash = md5(uniqid(rand(), true));
$html = str_replace("<h2>", $hash, $html);
$html = strip_tags($html);
$sections = explode($hash, $html);
foreach ($sections as $section) {
	foreach ($C['SectionTitle'] as $title) {
		if (strpos($section, $title) === 0) {
			$lines = explode("\n", $section);
			foreach ($lines as $line) {
				$line = trim($line);
				if ($line !== "" && $line !== $title) {
					if (strpos($line, "維基共享資源中相關的多媒體資源：") === 0) {
						continue;
					}
					$line = preg_replace("/\[\d+\]/", "", $line);
					$line = str_replace("[來源請求]", "", $line);
					$message .= "\n".$line;
				}
			}
		}
	}
}
if ($message === "") {
	$message = $month."月".$date."日目前找不到任何節日、風俗習慣\n\n".
		"立即上維基百科添加： https://zh.wikipedia.org/zh-tw/".$month."月".$date."日";
} else {
	$message = $month."月".$date."日的節日、風俗習慣有\n".
		$message."\n\n".
		"來源： https://zh.wikipedia.org/zh-tw/".$month."月".$date."日";
}
echo $message."\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/v2.8/me/feed");
curl_setopt($ch, CURLOPT_POST, true);
$post = array(
	"message" => $message,
	"access_token" => $C['FBpagetoken']
);
curl_setopt($ch,CURLOPT_POSTFIELDS, http_build_query($post));
curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
$res = curl_exec($ch);
curl_close($ch);

$res = json_decode($res, true);
if (isset($res["error"])) {
	echo json_encode($res)."\n";
} else {
	echo "Success\n";
}
