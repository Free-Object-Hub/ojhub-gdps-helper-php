<?php
require_once __DIR__.'/connection.php';
require_once __DIR__ . '/configs.php';

abstract class Roles {
	const None = 0;
	const Manager = 1;
	const Admin = 2;
	const Owner = 3;

	public static function toString(int $role): string {
		return match ($role) {
			self::None => 'Нет',
			self::Manager => 'Менеджер',
			self::Admin => 'Админ',
			self::Owner => 'Фюрер'
		};
	}
}

define('ROLE_DEFAULT', Roles::None);

class User {
	public readonly int $userId;
	public string $username;
	public string $nickname;
	public int $priority;
	public string $mail;
	public int $activated;
	public string $token;
	public string $password;
	public int $commTime;
	public int $code;
	public string $resume;
	public string $socials;
	public string $staticFp;
	public string $dynamicFp;
	public int $addDate;
	public int $lastJoin;


	public function verifyPassword(string $password) {
		return password_verify($password, $this->password);
	}

	function getNickname() {
		if ($this->nickname == '')
			return $this->username;
		return $this->nickname;
	}

	public function getDevices() {
		global $conn;
		$gdpses = $conn->prepare('SELECT * FROM `devices` WHERE userId = ?');
		$gdpses->execute([$this->userId]);
		return $gdpses->fetchAll(PDO::FETCH_DEFAULT);
	}

	public function checkDevice(string $UAgent, string $country, string $city, string $staticFp) {
		global $conn;
		$gdpses = $conn->prepare("SELECT * FROM `devices` WHERE `userId` = ? AND `userAgent` = ? AND `country` = ? AND `city` = ? AND `staticFp` = ?");

		$gdpses->execute([$this->userId, $UAgent, $country, $city, $staticFp]);
		return $gdpses->fetch();
	}

	public function easyCheckDevice(string $staticFp) {
		global $conn;
		$gdpses = $conn->prepare("SELECT * FROM `devices` WHERE `userId` = ? AND `staticFp` = ?");

		$gdpses->execute([$this->userId, $staticFp]);
		return $gdpses->fetch();
	}

	public function addDevice(string $UAgent, string $IP, string $country, string $city, string $platform, string $browser, string $staticFp, string $dynamicFp) {
		global $conn;
		$gdpses = $conn->prepare("INSERT INTO `devices` (`userId`, `userAgent`, `ip`, `country`, `city`, `platform`, `browser`, `staticFp`, `dynamicFp`)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
		$gdpses->execute([$this->userId, $UAgent, $IP, $country, $city, $platform, $browser, $staticFp, $dynamicFp]);
	}

	public function removeDevice(string $UAgent, string $IP, string $country, string $city) {
		global $conn;
		$gdpses = $conn->prepare("DELETE FROM `devices` WHERE userId = ? AND userAgent = ? AND ip = ? AND country = ? AND city = ?");
		$gdpses->execute([$this->userId, $UAgent, $IP, $country, $city]);
	}

	public function removeDeviceById(int $ID) {
		global $conn;
		$gdpses = $conn->prepare("DELETE FROM `devices` WHERE userId = ? AND ID = ?");
		$gdpses->execute([$this->userId, $ID]);
		return $gdpses->rowCount() > 0;
	}
	
	public static function checkActivate($userId): int {
		global $conn;
		$gdpses = $conn->prepare('SELECT * FROM `users` WHERE userId = ? AND activated = 1');
		$gdpses->execute([$userId]);
		if ($gdpses->fetch() != 0) return 1;
		else return 0;
	}
	
	public function verifyAcc(int|string $code) {
		global $conn;
		$query = $conn->prepare('UPDATE `users` SET `activated` = 1, `code` = 0 WHERE `userId` = ? AND `code` = ?');
		$query->execute([$this->userId, $code]);
		return $query->rowCount() > 0;
	}

	public static function dropPasswordPre(string $token, int $date, string $email, string $username) {
		global $conn;
		global $dropPass1;
		global $dropPass2;
		global $dropPass3;
		global $dropPass4;

		$verifyLink = "https://objecthub.xyz/refresh.php?auth=$token";

		$conn->prepare('INSERT INTO droppass (token,date,email,username) VALUES (?,?,?,?)')
			->execute([hash('sha256', $token), $date, $email, $username]);
		mail($email, $dropPass1, $dropPass2.$username.$dropPass3.$verifyLink.$dropPass4);
	}

	public static function getDropPassToken(string $token) {
		global $conn;
		$time = (int)($_SERVER['REQUEST_TIME'] ?? 0);
		$time = $time - 86400;
		$email = $conn->prepare('SELECT * FROM droppass WHERE token = ? AND isUsed = 0 AND date > ?');
		$email->execute([hash('sha256', $token), $time]);
		if ($email->rowCount() > 0)
			return $email->fetch(PDO::FETCH_ASSOC);
		else 
			return false;
	}

	public function refreshPassword(string $password, string $tokenPass) {
		global $conn;
		$conn->prepare('UPDATE droppass SET isUsed = 1 WHERE token = ? AND isUsed = 0')
			->execute([hash('sha256', $tokenPass)]);
		$conn->prepare('UPDATE users SET password = ? WHERE userId = ?')
			->execute([password_hash($password, PASSWORD_DEFAULT), $this->userId]);
		
	}
	
	public static function hasUsed(string $email, string $username) {
		global $conn;
		$user = $conn->prepare('SELECT * FROM `users` WHERE `mail` = ? OR `username` = ? LIMIT 1');
		$user->execute([$email, $username ]);
		return $user->fetchColumn();
	}
	
	public static function Webhook(string $webhookUrl, string $msg) {
		$message = 'BUILD_'.HELPER_VER.' '.$msg;
		$data = array('content' => $message);
		$jsonData = json_encode($data);
		$ch = curl_init($webhookUrl);

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

		curl_exec($ch);
		curl_close($ch);
	}

	public static function TGwebhook(int|string $channelId, string $msg) {
		global $TGbotToken;
		$message = $msg."\n\nBUILD".HELPER_VER;
		$data = [
			'chat_id' => $channelId,
			'text' => $message,
			'parse_mode' => 'Markdown'
		];
		$ch = curl_init("https://api.telegram.org/$TGbotToken/sendMessage");

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

		$resp = curl_exec($ch);
		curl_close($ch);

		return $resp;
	}

	public static function TGwebhookLog(string $msg) {
		$message = $msg."\n\nBUILD".HELPER_VER;
		$resp1 = false;

		$data = [
			'chat_id' => TG_NEWS_RESENDER,
			'text' => $message
		];
		$ch = curl_init("https://api.telegram.org/" . TG_BOT_TOKEN . "/sendMessage");

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

		$resp2 = curl_exec($ch);
		curl_close($ch);

		return [$resp1, $resp2];
	}
}

class Users {
	static function fetchAll(): array {
		global $conn;
		$req = $conn->prepare('SELECT * FROM `users`');
		$req->execute();
		return $req->fetchAll(PDO::FETCH_CLASS, User::class);
	}

	static function fetchById(int $userId) {
		global $conn;
		$req = $conn->prepare('SELECT * FROM `users` WHERE `userId` = ?');
		$req->execute([$userId]);
		return $req->fetchObject(User::class);
	}
	
	static function fetchByUsername(string $username) {
		global $conn;
		$req = $conn->prepare('SELECT * FROM `users` WHERE `username` = ?');
		$req->execute([$username]);
		return $req->fetchObject(User::class);
	}
	
	static function fetchByEmail(string $email) {
		global $conn;
		$req = $conn->prepare('SELECT * FROM `users` WHERE `mail` = ?');
		$req->execute([$email]);
		return $req->fetchObject(User::class);
	}
	
	static function fetchByToken(string $token) {
		global $conn;
		$req = $conn->prepare('SELECT * FROM `users` WHERE `token` = ?');
		$req->execute([$token]);
		return $req->fetchObject(User::class);
	}

	static function fetchByTokenAndDevice(string $token, string $staticFp = '') {
		global $conn;
		$sql = 'SELECT * FROM `users` WHERE `token` = ?';
		$exec = [$token];
		if ($staticFp) {
			$sql = 'SELECT u.* FROM users u INNER JOIN devices d on d.userId = u.userId WHERE u.token = ? AND d.staticFp = ?';
			array_push($exec, $staticFp);
		}
		$req = $conn->prepare($sql);
		$req->execute($exec);
		return $req->fetchObject(User::class);
	}

	static function loginUser(string $token, string $userAgent = '', string $IP = '') {
		$user = self::fetchByToken($token);
		global $conn;
		$device = $conn->prepare('SELECT ID FROM `devices` WHERE `userId` = ? AND `userAgent` = ?');
		$device->execute([$user->userId, $userAgent]);
		if ($device->fetchColumn() == true)
			return $user;
		else 
			return self::fetchById(0);
	}

	public static function logJoin(int $gdpsId, int $userId, string $findKey) {
		global $conn;
		$conn->prepare('INSERT INTO `joinlog`(`gdpsId`, `userId`, `joinData`, `joinDate`) VALUES (?, ?, ?, ?)')
			->execute([$gdpsId, $userId, $findKey, time()]);
	}

	static function setRoleToUser(int $userId, int $role): bool {
		global $conn;
		return $conn
			->prepare('UPDATE users SET priority = ? WHERE userId = ?')
			->execute([$role, $userId]);
	}

	static function setNickname(int $userId, string $name): bool {
		global $conn;
		$conn
			->prepare('UPDATE gdpses SET username = ? WHERE author = ?')
			->execute([$name, $userId]);
		$conn
			->prepare('UPDATE forumPosts SET username = ? WHERE userId = ?')
			->execute([$name, $userId]);
		return $conn
			->prepare('UPDATE users SET nickname = ? WHERE userId = ?')
			->execute([$name, $userId]);
	}

	static function setResume(int $userId, string $resume): bool {
		global $conn;
		return $conn
			->prepare('UPDATE users SET resume = ? WHERE userId = ?')
			->execute([$resume, $userId]);
	}

	static function setSocials(int $userId, string $socials): bool {
		global $conn;
		return $conn
			->prepare('UPDATE users SET socials = ? WHERE userId = ?')
			->execute([$socials, $userId]);
	}
	
	static function randomString($length) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[random_int(0, $charactersLength - 1)];
		}
		return $randomString;
	}

	static function refreshPassword(string $password, string $username, int $userId) {
		$token = hash('sha256', Users::randomString(16).$username);
		global $conn;
		if (!$conn
			->prepare('UPDATE users SET code = 0, activated = 1, password = ?, token = ? WHERE userId = ?')
			->execute([
				password_hash($password, PASSWORD_DEFAULT),
				$token,
				$userId
			]))
			return null;
		return $token;
	}

	static function newUserToken(string $username, string $password, string $email, int $activated, string $token, $priority = ROLE_DEFAULT): ?string {
		global $conn;
		if (!$conn
			->prepare('INSERT INTO users (username, password, mail, code, token, priority) VALUES (?, ?, ?, ?, ?, ?)')
			->execute([
				$username,
				password_hash($password, PASSWORD_DEFAULT),
				$email,
				$activated,
				$token,
				$priority
			]))
			return null;
		return $token;
	}

	public static function sendMail(string $user, string $email, int $activated) {
		global $verifyAcc1;
		global $verifyAcc2;
		global $verifyAcc3;
		global $verifyAcc4;
		mail($email, $verifyAcc1, $verifyAcc2.$user.$verifyAcc3.$activated.$verifyAcc4);
	}
}