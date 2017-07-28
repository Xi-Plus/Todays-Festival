<?php
require(__DIR__.'/config/config.php');
if (!in_array(PHP_SAPI, $C["allowsapi"])) {
	exit("No permission");
}

$testmode = isset($argv[1]);
$month = date("n");
$date = date("j");
if (isset($argv[2]) && is_numeric($argv[1]) && is_numeric($argv[2])) {
	$month = $argv[1];
	$date = $argv[2];
}
echo "fetch ".$month."/".$date."\n";

$message = "";

$html = file_get_contents("https://zh.wikipedia.org/zh-tw/".$month."月".$date."日?action=render");
$html = str_replace("&#160;", " ", $html);
$html = html_entity_decode($html);
$hash = md5(uniqid(rand(), true));
$html = str_replace("<h2>", $hash, $html);
$html = strip_tags($html);
$sections = explode($hash, $html);
foreach ($sections as $section) {
	$lines = explode("\n", $section);
	echo $lines[0]."\n";
	foreach ($C['SectionTitle'] as $title) {
		if (strpos($lines[0], $title) !== false) {
			unset($lines[0]);
			foreach ($lines as $line) {
				$line = trim($line);
				if ($line !== "" && $line !== $title) {
					if (strpos($line, "維基共享資源中相關的多媒體資源：") === 0) {
						continue;
					}
					$line = preg_replace("/\[\d+\]/", "", $line);
					$line = str_replace("[來源請求]", "", $line);
					$line = trim($line);
					$message .= "\n".$line;
				}
			}
			break;
		}
	}
}
echo "\n";

if ($message === "") {
	$message = $month."月".$date."日目前找不到任何節日、風俗習慣\n\n".
		"立即上維基百科添加： https://zh.wikipedia.org/zh-tw/".$month."月".$date."日";
} else {
	$message = $month."月".$date."日的節假日、習俗、紀念日有\n".
		$message."\n\n".
		"來源：中文維基百科 (CC-BY-SA-3.0) https://zh.wikipedia.org/zh-tw/".$month."月".$date."日";
}
echo "message:\n".$message."\n";

if ($testmode) {
	exit("test mode on\n");
}

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
