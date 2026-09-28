<?php

$userWord = $_POST['word'] ?? '';
$words = [
	'АБЕР','АВЗИ','БЗТЗ','БУКА','ЕБАР','ИНМТ','НСОШ','ЭЗМГ','ОЛЕГ','ОШКФ','ОШФБ','ПИПП','СЗНП','СОКА','ЕШКА',
	'АРТИВ','САЛКИ','ГОЙДА','ФРИДЖ','КЛЕВИ','ОШХАБ','КОСМО','МЕРФИ','НОВЛО','КНАЙФ','ПСЕВЯ','ПШИКА',
	'ИКОТИК','ФЕДКАР','САЙДАМ','ПИКЧЕР','ХЕЛОИЗ','ШПИНАТ','НЕСКЬЮ','ДЕЛЬТА','СТЕРКА',
];

function getDailyIndex(array $words, int $day = 0): int {
	$hash1 = crc32(strval($day));
	$hash2 = md5(strval($day), true);
	$hash3 = sha1(strval($day), true);
	
	$combined = $hash1 ^ unpack('L', $hash2)[1] ^ unpack('L', $hash3)[1];
	
	return abs($combined) % count($words);
}
$day = time()/43200;
$secretWord = mb_strtolower($words[getDailyIndex($words, $day)]);

if ($userWord == '') {
	echo mb_strlen($secretWord);
	exit();
}

$userLetters = mb_str_split($userWord);
$secretLetters = mb_str_split($secretWord);

$result = array_fill(0, count($secretLetters), 0);

for ($i = 0; $i < count($secretLetters); $i++) {
	if ($userLetters[$i] === $secretLetters[$i]) {
		$result[$i] = 2;
		$secretLetters[$i] = null;
		$userLetters[$i] = null;
	}
}

for ($i = 0; $i < count($userLetters); $i++) {
	if ($userLetters[$i] !== null) {
		$index = array_search($userLetters[$i], $secretLetters, true);
		if ($index !== false) {
			$result[$i] = 1;
			$secretLetters[$index] = null;
		}
	}
}

echo implode('', $result);
