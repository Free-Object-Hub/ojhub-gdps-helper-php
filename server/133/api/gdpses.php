<?php

class GDPS {
	public int $ID;
	public string $title;
	public string $link;
	public string $database;
	public string $img;
	public string $ban;
	public string $description;
	public ?string $short = '';
	public string $text;
	public int $channel;
	public int $author;
	public int $userId;
	public string $username;
	public string $tags;
	public string $os;
	public int $mask;
	public int $checked;
	public int $likes;
	public int $disls;
	public int $hasLgbt;
	public int $commsCount;
	public int $freejoin;
	public int $points;
	public int $status;
	public string $language;
	public int $gdpsId;
	public int $date;
	public int $editCount;
	public int $connectedWiki;
	public int $isBanned;
	public ?int $isLiked = 0;
	public ?string $role = '';

	
	public function GDPSrender($fullText = false, int $userId = 0) {
		$text = $fullText 
			? $this->description 
			: ($this->short ?? mb_substr($this->description, 0, 121));

		$links = str_replace('\"', '"', $this->link);
		if (str_starts_with($links, '{'))
			$links = json_decode($links);

		$liked = 0;
		if ($userId !== 0)
			$liked = content::checkLike($this->ID, $userId, 0);
		if (empty($liked))
			$liked = 0;
		else
			$liked = $liked['type'];
		
		$gdps = [
			'ID' => (int)$this->ID,
			'title' => $this->title,
			'text' => $text,
			'links' => $links,
			'tags' => $this->tags,
			'os' => $this->os,
			'likes' => [
				(int)$this->likes,
				(int)$this->disls,
				(int)$this->commsCount
			],
			'isLiked' => (int)$liked,
			'author' => (int)$this->author,
			'username' => $this->username,
			'img' => $this->img,
			'ban' => $this->ban,
			'freejoin' => (int)$this->freejoin,
			'language' => $this->language,
			'checked' => (int)$this->checked,
			'points' => (int)$this->points,
			'wiki' => (int)$this->connectedWiki
		];
		return $gdps;
	}

	public function GDPSrenderAdmin($fullText = false) {
		$text = $fullText 
			? $this->description 
			: ($this->short ?? mb_substr($this->description, 0, 121));

		return [
			'ID' => $this->ID,
			'title' => $this->title,
			'text' => $text,
			'tags' => $this->tags,
			'os' => $this->os,
			'likes' => $this->likes,
			'author' => $this->author,
			'username' => $this->username,
			'img' => urlencode($this->img),
			'ban' => urlencode($this->ban),
			'freejoin' => $this->freejoin,
			'language' => $this->language,
			'checked' => $this->checked,
			'points' => $this->points,
			'wiki' => $this->connectedWiki,
			'link' => $this->link,
		];
	}

	public function GDPSrenderLT($isText = 'g', $userId = 0) {
		$liked = $this->isLiked;
		if (!$liked)
			$liked = 0;

		$text = $this->short;
		if ($this->short === '') {
			$text = mb_substr($this->description, 0, 121);
		}
		$gdps = [
			'ID' => (int)$this->ID,
			'title' => $this->title,
			'text' => $text,
			'tags' => $this->tags,
			'os' => $this->os,
			'likes' => [
				(int)$this->likes,
				(int)$this->disls,
				(int)$this->commsCount
			],
			'isLiked' => (int)$liked,
			'author' => (int)$this->author,
			'username' => $this->username,
			'img' => $this->img,
			'ban' => $this->ban,
			'channel' => (int)$this->channel,
			'wiki' => (int)$this->connectedWiki
		];
		return $gdps;
	}
	public function GDPSrenderLT2($isText = 'g', $userId = 0) {
		$liked = $this->isLiked;
		if (!$liked)
			$liked = 0;
		$text = json_encode(($this->short ?? mb_substr($this->description, 0, 121)));
		return '"'.$isText.$this->ID.'":{'.
		'"ID":'		 .$this->ID.','.
		'"title":"'	 .$this->title.'",'.
		'"text":'	   .$text.','.
		'"tags":"'	  .str_replace('"', '\\"', $this->tags).'",'.
		'"os":"'		.str_replace('"', '\\"', $this->os).'",'.
		'"likes":['	 .$this->likes.','.$this->disls.','.$this->commsCount.'],'.
		'"isLiked":'	.$liked.','.
		'"author":'	 .$this->author.','.
		'"username":"'  .$this->username.'",'.
		'"img":"'	   .urlencode($this->img).'",'.
		'"ban":"'	   .urlencode($this->ban).'",'.
		'"wiki":'	   .$this->connectedWiki
		.'}';
	}
	
	public function GDPSrenderLegacy() {
		$text = $this->description;
		echo 
		'"g'.$this->ID.'":['.
		$this->ID.',"'.
		$this->title.'",'.
		json_encode(mb_substr($text, 0, 121)).',"'.
		str_replace('"', '\\"', $this->tags).'","'.
		str_replace('"', '\\"', $this->os).'",'.
		$this->likes.','.
		$this->author.',"'.
		$this->username.'","'.
		urlencode($this->img).'",'.
		$this->freejoin.','.
		$this->status.',"'.
		$this->language.'",'.
		$this->points
		.']';
	}
}

class Comments {
	public int $ID;
	public int $gdpsId;
	public string $text;
	public string $title;
	public int $whereIz;
	public int $gChannel;
	public int $uPriority;
	public int $userId;
	public string $username;
	public string $uNickname;
	public string $uUsername;
	public string $gTitle;
	public int $date;
	public int $likes;
	public $isLiked;
	public int $disls;
	public int $checked;
	public int $commsCount;
	public int $channel;
	public string $hasFile;

	public static function fetchById(int $ID): ?Comments {
		global $conn;
		$gdpses = $conn->prepare('SELECT * FROM `comments` WHERE `ID` = ?');
		$gdpses->execute([$ID]);
		$gdps = $gdpses->fetchObject(Comments::class);
		if ($gdps == false) {
			return null;
		}
		return $gdps;
	}

	public static function newsFetchById(int $ID, int $userId = 0): ?Comments {
		global $conn;
		$query = 'SELECT n.*, l.type as isLiked, '.
				 'u.username as uUsername, u.nickname as uNickname, '.
				 'g.title as gTitle, g.channel as gChannel '.
				 'FROM news n '.
				 'LEFT JOIN likes l ON l.whereIz = n.ID AND l.userId = ? AND l.channel = 6 '.
				 'LEFT JOIN users u ON u.userId = n.userId '.
				 'LEFT JOIN gdpses g ON g.ID = n.gdpsId '.
				 'WHERE n.ID = ?';
		$gdpses = $conn->prepare($query);
		$gdpses->execute([$userId, $ID]);
		$gdps = $gdpses->fetchObject(Comments::class);
		if ($gdps == false) {
			return null;
		}
		return $gdps;
	}
	
	public function COMMrender() {
		$liked = $this->isLiked;
		if (!$liked)
			$liked = 0;

		$showNick = $this->uNickname;
		if (!$showNick)
			$showNick = $this->uUsername;
		if (!$showNick)
			$showNick = '???';

		return [
			(int)$this->ID,
			$showNick,
			base64_decode($this->text),
			(int)$this->userId,
			(int)$this->uPriority,
			[
				(int)$this->likes,
				(int)$this->disls,
			],
			(int)$this->date,
			(int)$liked
		];
	}

	public function newsRenderObj() {

		$liked = $this->isLiked;
		if (!$liked)
			$liked = 0;

		$showNick = $this->uNickname;
		if (!$showNick)
			$showNick = '???';

		$gCh = GDPSswitchChannel($this->gChannel);

		$news = [
			'ID' => (int)$this->ID,
			'title' => $this->title,
			'text' => base64_decode(str_replace(PHP_EOL, '<br>', $this->text)),
			'author' => (int)$this->userId,
			'username' => $showNick,
			'gdpsId' => $gCh.$this->gdpsId,
			'gdpsTitle' => ($this->gTitle ?? '???'),
			'date' => (int)$this->date,
			'likes' => [
				(int)$this->likes,
				(int)$this->disls,
				(int)$this->commsCount
			],
			'isLiked' => (int)$this->isLiked,
			'hasFile' => $this->hasFile,
		];
		return $news;
	}

	public static function getParentChannel(int $channel) {
		switch ($channel) {
			case 0:
			case 1:
				return 'gdpses';
			case 2:
				return 'guides';
			case 3:
				return 'news';
			case 4:
				return 'forumPosts';
			case 5:
				return 'vacans';
		}
	}

	public static function getLikeChannel(int $channel) {
		switch ($channel) {
			case 0:
				return 1;
			case 1:
				return 4;
			case 2:
				return 5;
			case 3:
				return 4;
			case 4:
				return 10;
			case 5:
				return 12;
		}
	}

	public static function getComments(int $type, int $content, int $userId, int $page = 0) {
		switch ($type) {
			case 0:
				$ch = 0; // gdpses
				$pr = self::getLikeChannel($ch);
				break;
			case 1:
				$ch = 0; // gdpses, no more texures
				$pr = self::getLikeChannel($ch);
				break;
			case 2:
				$ch = 2; // guides
				$pr = self::getLikeChannel($ch);
				break;
			case 3:
				$ch = 3; // news
				$pr = self::getLikeChannel($ch);
				break;
			case 4:
				$ch = 4; // forumPosts
				$pr = self::getLikeChannel($ch);
				break;
			case 5:
				$ch = 5; // vacs
				$pr = self::getLikeChannel($ch);
				break;
		}
		$page2 = $page * 10;

		$query = 'SELECT c.*, l.type as isLiked, u.username as uUsername, u.nickname as uNickname, u.priority as uPriority FROM comments c '.
				 'LEFT JOIN likes l ON l.whereIz = c.ID AND l.userId = ? AND l.channel = ? '.
				 'LEFT JOIN users u ON u.userId = c.userId '.
				 'WHERE c.whereIz = ? AND c.channel = ? ORDER BY c.ID DESC LIMIT 11';
		$exec = [$userId, $pr, $content, $ch];
		if ($page != 0) {
			$query .= ' OFFSET '.$page2;
		}

		global $conn;
		$gdpses = $conn->prepare($query);
		$gdpses->execute($exec);
		return $gdpses->fetchAll(PDO::FETCH_CLASS, Comments::class);
	}

	public static function getAllComments(int $type, int $content): array {
		switch ($type) {
			case 0:
				$ch = 0; // gdpses
				break;
			case 1:
				$ch = 1; // texures
				break;
			case 2:
				$ch = 2; // guides
				break;
			case 3:
				$ch = 3; // news
				break;
		}

		global $conn;
		$query = 'SELECT `ID` FROM `comments` WHERE `whereIz` = ? AND `channel` = ? ORDER BY `ID` DESC';

		$gdpses = $conn->prepare($query);
		//$gdpses->bindValue(1, $content, PDO::PARAM_INT);
		//$gdpses->bindValue(2, $ch, PDO::PARAM_INT);
		$gdpses->execute([$content, $ch]);
		return $gdpses->fetchAll(PDO::FETCH_CLASS, Comments::class);
	}

	public static function getRealAllComments(int $type): array {
		switch ($type) {
			case 0:
				$ch = 0; // gdpses
				break;
			case 1:
				$ch = 1; // texures
				break;
			case 2:
				$ch = 2; // guides
				break;
			case 3:
				$ch = 3; // news
				break;
			case 4:
				$ch = 4; // forums
				break;
		}

		global $conn;
		$query = 'SELECT `ID` FROM `comments` WHERE `channel` = ? ORDER BY `ID` DESC';

		$gdpses = $conn->prepare($query);
		//$gdpses->bindValue(1, $ch, PDO::PARAM_INT);
		$gdpses->execute([$ch]);
		return $gdpses->fetchAll(PDO::FETCH_CLASS, Comments::class);
	}

	public static function fetchAllNews(): array {
		global $conn;
		$query = 'SELECT * FROM `news` ORDER BY `ID` DESC';
		$gdpses = $conn->prepare($query);
		$gdpses->execute();
		return $gdpses->fetchAll(PDO::FETCH_CLASS, Comments::class);
	}

	public static function addComment(int $userId, int $ID, string $text, int $date = 0, int $channel = 0) {
		if ($channel == 1)
			$channel = 0;
		global $conn;

		$ct = $date + 600;
		$uder = $conn->prepare('UPDATE `users` SET `commTime` = ? WHERE `userId` = ?');
		$uder->execute([$ct, $userId]);

		$where = self::getParentChannel($channel);
		$commCount = $conn->prepare("UPDATE $where SET `commsCount` = `commsCount` + 1 WHERE ID = ?");
		$commCount->execute([$ID]);

		$insert = $conn->prepare('INSERT INTO `comments` (`userId`, `whereIz`, `text`, `date`, `channel`)
		VALUES (?,?,?,?,?)');
		return $insert->execute([$userId, $ID, $text, $date, $channel]);
	}

	public static function modifyComment(int $userId, int $commId, string $text, int $date) {
		global $conn;
		$ct = $date + 600;
		$uder = $conn->prepare('UPDATE `users` SET `commTime` = ? WHERE `userId` = ?');
		$uder->execute([$ct, $userId]);
		$insert = $conn->prepare('UPDATE `comments` SET `text` = ? WHERE `ID` = ?');
		return $insert->execute([$text, $commId]);
	}

	public static function NEWSpost(int $userId, int $ID, string $text, int $date = 0, string $title = '', int $checked = 0, string $hasFile = '') {
		global $conn;
		$insert = $conn->prepare('INSERT INTO `news` (`userId`, `gdpsId`, `date`, `title`, `text`, `checked`, `hasFile`)
		VALUES (?,?,?,?,?,?,?)');
		$new = $insert->execute([$userId, $ID, $date, $title, $text, $checked, $hasFile]);

		return $conn->lastInsertId();
	}

	public static function NEWSpostModify(int $ID, string $text, string $title = '') {
		global $conn;
		$insert = $conn->prepare('UPDATE `news` SET `text` = ?, `title` = ? WHERE `ID` = ?');
		return $insert->execute([$text, $title, $ID]);
	}

	public static function deleteComm(int $ID, int $channel, int $likeChannel) {
		global $conn;

		$parentID = $conn->prepare('SELECT whereIz FROM comments WHERE ID = ?');
		$parentID->execute([$ID]);
		$parentID = $parentID->fetch()[0];

		$where = self::getParentChannel($channel);
		$commCount = $conn->prepare("UPDATE $where SET `commsCount` = `commsCount` - 1 WHERE ID = ?");
		$commCount->execute([$parentID]);

		$delete = $conn->prepare('DELETE FROM `comments` WHERE `ID` = ? AND `channel` = ?');
		$delete->execute([$ID, $channel]);

		$unlike = $conn->prepare('DELETE FROM `likes` WHERE `whereIz` = ? AND `channel` = ?');
		return $unlike->execute([$ID, $likeChannel]);
	}

	public static function deleteNews(int $ID) {
		global $conn;
		$delete = $conn->prepare('DELETE FROM `news` WHERE `ID` = ?');
		$delete->execute([$ID]);

		$unlike = $conn->prepare('DELETE FROM `likes` WHERE `whereIz` = ? AND `channel` = 6');
		return $unlike->execute([$ID]);
	}
}

class content {
	public static function fetchAll(int $type): array {
		switch ($type) {
			case 0:
				$where = 'gdpses`';
				break;
			case 1:
				$where = 'texures`';
				break;
			case 2:
				$where = 'news`';
				break;
			case 3:
				$where = 'comments` WHERE `channel` = 0';//gdpses
				break;
			case 4:
				$where = 'comments` WHERE `channel` = 1';//texures
				break;
			case 5:
				$where = 'comments` WHERE `channel` = 3';//news
				break;
			case 6:
				$where = 'comments` WHERE `channel` = 2';//guides
				break;
			case 7:
				$where = 'comments`';
				break;
			case 8:
				$where = 'joinlog`';
				break;
			case 9:
				$where = 'soowners`';
				break;
		}
		global $conn;
		$gdpses = $conn->prepare('SELECT * FROM `'.$where.' ORDER BY `ID` DESC');
		$gdpses->execute();
		return $gdpses->fetchAll(PDO::FETCH_OBJ);
	}

	public static function fetchAlls(int $type): array {
		switch ($type) {
			case -2:
				$where = 'guides`';
				break;
			case -1:
				$where = 'wikis`';
				break;
			case 0:
				$where = 'gdpses` WHERE `channel` = 0';
				break;
			case 1:
				$where = 'gdpses` WHERE `channel` = 1';
				break;
			case 2:
				$where = 'gdpses` WHERE `channel` = 2';
				break;
		}
		global $conn;
		$gdpses = $conn->prepare('SELECT * FROM `'.$where.' ORDER BY `ID` DESC');
		$gdpses->execute();
		return $gdpses->fetchAll(PDO::FETCH_CLASS, GDPS::class);
	}

	public static function fetchAlls2(int $type): array {
		switch ($type) {
			case -2:
				$where = 'guides`';
				break;
			case -1:
				$where = 'wikis`';
				break;
			case 0:
				$where = 'gdpses` WHERE `channel` = 0';
				break;
			case 1:
				$where = 'gdpses` WHERE `channel` = 1';
				break;
			case 2:
				$where = 'gdpses` WHERE `channel` = 2';
				break;
		}
		global $conn;
		$gdpses = $conn->prepare('SELECT * FROM `'.$where.' ORDER BY `ID` DESC');
		$gdpses->execute();
		return $gdpses->fetchAll(PDO::FETCH_CLASS, GDPS::class);
	}

	public static function fetchNews(int $ID, int $userId, int $page = 0): array {
		global $conn;
		$query = 'SELECT n.*, l.type as isLiked, '.
				 'u.username as uUsername, u.nickname as uNickname, '.
				 'g.title as gTitle, g.channel as gChannel '.
				 'FROM news n '.
				 'LEFT JOIN likes l ON l.whereIz = n.ID AND l.userId = ? AND l.channel = 6 '.
				 'LEFT JOIN users u ON u.userId = n.userId '.
				 'LEFT JOIN gdpses g ON g.ID = n.gdpsId '.
				 'WHERE n.gdpsId = ? ORDER BY n.ID DESC LIMIT 11';
		$exec = [$userId, $ID];
		if ($page !== 0) {
			$offset = (int)($page * 10);
			$query .= ' OFFSET '.$offset;
		}

		$gdpses = $conn->prepare($query);
		$gdpses->execute($exec);
		return $gdpses->fetchAll(PDO::FETCH_CLASS, Comments::class);
	}

	public static function fetchAllNews(int $userId, int $page = 0): array {
		$page2 = $page * 10;
		global $conn;
		//$query = 'SELECT * FROM `news` WHERE `checked` = 1 ORDER BY `ID` DESC LIMIT 11'; // старый код
		$query = 'SELECT n.*, l.type as isLiked, '.
				 'u.username as uUsername, u.nickname as uNickname, '.
				 'g.title as gTitle, g.channel as gChannel '.
				 'FROM news n '.
				 'LEFT JOIN likes l ON l.whereIz = n.ID AND l.userId = ? AND l.channel = 6 '.
				 'LEFT JOIN users u ON u.userId = n.userId '.
				 'LEFT JOIN gdpses g ON g.ID = n.gdpsId '.
				 'WHERE n.checked = 1 ORDER BY `ID` DESC LIMIT 11';
		if ($page2 !== 0)
			$query .= ' OFFSET '.$page2;

		$gdpses = $conn->prepare($query);
		$gdpses->execute([$userId]);
		return $gdpses->fetchAll(PDO::FETCH_CLASS, Comments::class);
	}

	public static function fetchById(int $ID, int $type) {
		switch ($type) {
			case -3:
				$where = 'news';
				break;
			case 0:
			case 1:
			case 2:
				$where = 'gdpses';
				break;
		}
		global $conn;
		$gdpses = $conn->prepare('SELECT * FROM `'.$where.'` WHERE `ID` = ?');
		$gdpses->execute([$ID]);
		switch ($type) {
			case -3:
				$gdps = $gdpses->fetchObject(Comments::class);
				break;
			case 0:
			case 1:
			case 2:
				$gdps = $gdpses->fetchObject(GDPS::class);
				break;
		}
		if ($gdps == false) {
			return null;
		}
		return $gdps;
	}

	public static function fetchAllVerified(int $type): array {
		switch ($type) {
			case 0:
				$where = 'gdpses';
				break;
			case 1:
				$where = 'texures';
				break;
		}
		global $conn;
		$gdpses = $conn->prepare('SELECT * FROM `'.$where.'` WHERE `checked` = 1 ORDER BY `ID` DESC');
		$gdpses->execute();
		return $gdpses->fetchAll(PDO::FETCH_OBJ);
	}//ОТКАЗ

	public static function fetchAllWorking(): array {
		global $conn;
		$gdpses = $conn->prepare('SELECT * FROM `gdpses` WHERE `checked` = 1 AND `sstatus` = 1 ORDER BY `ID` DESC');
		$gdpses->execute();
		return $gdpses->fetchAll(PDO::FETCH_OBJ);
	}//ОТКАЗ

	public static function fetchOwned(int $Id, int $type): array {
		$ch = '';
		switch ($type) {
			case -1:
				$where = 'wikisoowners';
				$auth = 'wikiId';
				$ch = '';
				break;
			case 0:
				$where = 'soowners';
				$auth = 'gdpsId';
				$ch = 'AND `channel` = 0';
				break;
			case 1:
				$where = 'soowners';
				$auth = 'gdpsId';
				$ch = 'AND `channel` = 1';
				break;
			case 2:
				$where = 'soowners';
				$auth = 'gdpsId';
				$ch = 'AND `channel` = 2';
				break;
			case 4:
				$where = 'joinlog';
				$auth = 'gdpsId';
				break;
		}
		global $conn;
		$gdpses = $conn->prepare('SELECT * FROM `'.$where.'` WHERE `'.$auth.'` = ? '.$ch.' ORDER BY `ID` DESC');
		$gdpses->execute([$Id]);
		return $gdpses->fetchAll(PDO::FETCH_OBJ);
	}

	public static function checkItem(int $userId, int $ID, int $type) {
		switch ($type) {
			case -1: $where = 'wikis';  $user = 'userId';	 break;
			case 0: $where = 'gdpses';  $user = 'author';	 break;
			case 1: $where = 'gdpses';  $user = 'author';	 break;
			case 2: $where = 'gdpses';  $user = 'author';	 break;
		}
		global $conn;
		
		$a = $conn->prepare('SELECT * FROM `'.$where.'` WHERE `ID` = ? AND `'.$user.'` = ?');
		$a->execute([$ID, $userId]);
		if ($a->fetchColumn() == true)
			return 2;

		if ($type !== -1) { // раньше шёл запрос к базе данных сразу на soowners, теперь же проверяет вики это или нет
			$b = $conn->prepare('SELECT * FROM `soowners` WHERE `gdpsId` = ? AND `userId` = ?');
			$b->execute([$ID, $userId]);
			if ($b->fetchColumn() == true)
				return 1;
		} else {
			$b = $conn->prepare('SELECT * FROM `wikisoowners` WHERE `wikiId` = ? AND `userId` = ?');
			$b->execute([$ID, $userId]);
			if ($b->fetchColumn() == true)
				return 1;
		}
		
		return 0;
	}
	
	public static function getValue(string $val, string $where, int $ID) {
		global $conn;
		$gdpses = $conn->prepare('SELECT `'.$val.'` FROM `'.$where.'` WHERE `ID` = ?');
		$gdpses->execute([$ID]);
		return $gdpses->fetch()[0];
	}

	public static function changeValue(string $val, string $where, int $type, int $ID) {
		if ($type == true) {
			$m = '+';
		} else {
			$m = '-';
		}
		global $conn;
		$gdpses = $conn->prepare(
		'UPDATE `'.$where.'` SET `'.$val.'` = `'.$val.'` '.$m.' 1 WHERE `ID` = ?');
		$gdpses->execute([$ID]);
	}

	public static function setPoint(int $gdpsId, int $date, int $currentDate): int {
		global $conn;
		$prepoint = $conn->prepare('SELECT * FROM `gdpses` WHERE `ID` = :gdpsId');
		$prepoint->execute([':gdpsId'=>$gdpsId]);
		$gdps = $prepoint->fetchObject();
		if ($gdps->checked != 1)
			return false;
		if (($gdps->points - $currentDate) > 0)
			return $gdps->points;
		$conn->prepare('UPDATE `gdpses` SET `points` = :date WHERE `gdpses`.`ID` = :gdpsId')
			 ->execute([':date'=>$date, ':gdpsId'=>$gdpsId]);
		return $date;
	}

	public static function getMyContent(int $userId, int $type = 0) {
		global $conn;

		$a = $conn->prepare('SELECT * FROM `gdpses` WHERE `author` = ? AND `channel` = ?');
		$a->execute([$userId, $type]);
		$b = $conn->prepare('SELECT * FROM `soowners` WHERE `userId` = ?');
		$b->execute([$userId]);
		return [$a->fetchAll(PDO::FETCH_OBJ), $b->fetchAll(PDO::FETCH_OBJ)];
	}

	public static function getAllMyContent(int $userId) {
		global $conn;
		$stmt = $conn->prepare('SELECT g.*, "owner" as role FROM `gdpses` g WHERE g.author = ?'.
			'UNION SELECT g.*, "coowner" as role FROM `gdpses` g INNER JOIN `soowners` s ON g.id = s.gdpsId WHERE s.userId = ?');
		$stmt->execute([$userId, $userId]);
		$allGdps = $stmt->fetchAll(PDO::FETCH_CLASS, GDPS::class);

		$ownedGdps = array_filter($allGdps, function($gdps) {
			return $gdps->role === 'owner';
		});
		$sharedGdps = array_filter($allGdps, function($gdps) {
			return $gdps->role === 'coowner';
		});

		foreach ($ownedGdps as $gdps)
			unset($gdps->role);
		foreach ($sharedGdps as $gdps)
			unset($gdps->role);

		return [array_values($ownedGdps), array_values($sharedGdps)];
	}

	public static function getAllMyContent2(int $userId) {
		global $conn;

		$a = $conn->prepare('SELECT * FROM `gdpses` WHERE `author` = ?');
		$a->execute([$userId]);
		$b = $conn->prepare('SELECT * FROM `soowners` WHERE `userId` = ?');
		$b->execute([$userId]);
		return [$a->fetchAll(PDO::FETCH_CLASS, GDPS::class), $b->fetchAll(PDO::FETCH_OBJ)];
	}

	public static function checkOwner(int $userId, int $ID, int $type) {
		switch ($type) {
			case 0:
				$where = 'gdpses`';
				break;
			case 1:
				$where = 'texures`';
				break;
		}
		global $conn;
		$z = $conn->prepare('SELECT * FROM `soowners` WHERE `whereIz` = ? AND `userId` = ? AND `channel` = ? LIMIT 1');
		$z->execute([$ID, $userId, $type]);
		return $z->fetchColumn();
	}

	public static function addOwner(int $gdpsId, int $userId, int $type) {
		switch ($type) {
			case -1:
				$id = 'wikiId';
				$where = 'wikisoowners';
				$channel = '';
				$what = '';
				break;
			case 0:
				$id = 'gdpsId';
				$where = 'soowners';
				$channel = ', `channel`';
				$what = ',0';
				break;
			case 1:
				$id = 'gdpsId';
				$where = 'soowners';
				$channel = ', `channel`';
				$what = ',1';
				break;
			case 2:
				$id = 'gdpsId';
				$where = 'soowners';
				$channel = ', `channel`';
				$what = ',2';
				break;
		}
		global $conn;
		$gdpses = $conn->prepare('INSERT INTO `'.$where.'` (`'.$id.'`, `userId`'.$channel.') VALUES (?,?'.$what.')');
		$gdpses->execute([$gdpsId, $userId]);
	}

	public static function deleteOwner(int $gdpsId, int $userId, int $type) {
		global $conn;
		$table = 'soowners';
		$useChannel = ' AND `channel` = ?';
		$id = 'gdpsId';
		$exec = [$gdpsId, $userId, $type];
		if ($type == 2) {
			$table = 'wikisoowners';
			$useChannel = '';
			$id = 'wikiId';
			$exec = [$gdpsId, $userId];
		}
		$gdpses = $conn->prepare("DELETE FROM `$table` WHERE `$id` = ? AND `userId` = ?$useChannel");
		$gdpses->execute($exec);
	}

	public static function newItem(array $data) {
		global $conn;

		$stmt = $conn->prepare('INSERT INTO `gdpses` (`channel`, `title`, `link`, `img`, `ban`, `description`, `short`, `tags`, `os`, `mask`, `author`, `username`, `language`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
		//$stmt->execute([$data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], $data[7], $data[8], $data[9], $data[10], $data[11]]);
		$stmt->execute($data);
		return $conn->lastInsertId();
	}

	public static function editGdpsPictures(int $id, string $img, string $ban) {
		global $conn;

		return $conn
			->prepare('UPDATE `gdpses` SET `img` = ?, `ban` = ? WHERE `gdpses`.`ID` = ?')
			->execute([$img, $ban, $id]);
	}

	public static function editItem(array $data): bool {
		global $conn;
		return $conn
			->prepare('UPDATE `gdpses` SET `title` = ?, `link` = ?, `img` = ?, `ban` = ?, `description` = ?, `short` = ?, `tags` = ?, `os` = ?, `mask` = ?, `language` = ?, `checked` = 0, `editCount` = `editCount` + 1 WHERE `gdpses`.`ID` = ?')
			->execute([$data[1], $data[2], $data[3], $data[4], $data[5], $data[6], $data[7], $data[8], $data[9], $data[10], $data[11]]);
	}

	public static function liketype(int $type): array {
		switch ($type) {
			case 0:
				return ['gdpses',0,0];////
			case 1:
				return ['texures',0,2];////
			case 2:
				return ['news',0,6];////
			case 3:
				return ['comments',0,1];//gdpses////
			case 4:
				return ['comments',1,4];//texures////
			case 5:
				return ['comments',3,4];//news////
			case 6:
				return ['comments',2,5];//guidescomm////
			case 7:
				return ['guides',0,7];////
			case 8:
				return ['wikis',0,8];////
			case 9:
				return ['forumPosts',0,9];////
			case 10:
				return ['comments',4,10];//forumPostComm////
			case 11:
				return ['vacans',0,11];
			case 12:
				return ['comments',5,12];//vacanscomm
			default:
				return
				[
					'table',
					-1, // commentsChannel
					-1  // likeChannel
				];
			}
	}

	public static function likeSet(int $ID, int $type, int $userId) {
		$where = self::liketype($type);
		$c = '';
		
		if ($where[0] == 'comments') {
			$c = ' AND `channel` = ' . $where[1];
		}
		
		global $conn;
		
		$like = $conn->prepare('UPDATE `' . $where[0] . '` SET `likes` = `likes` + 1 WHERE `ID` = ?' . $c);
		$like->execute([$ID]);
		
		$conn->prepare('INSERT INTO `likes` (`whereIz`, `userId`, `type`, `channel`) VALUES (?, ?, ?, ?)')
			 ->execute([$ID, $userId, -1, $where[2]]);
	}

	public static function dislSet(int $ID, int $type, int $userId) {		
		$where = self::liketype($type);
		$c = '';
		
		if ($where[0] == 'comments') {
			$c = ' AND `channel` = ' . $where[1];
		}
		
		global $conn;
		
		$like = $conn->prepare('UPDATE `' . $where[0] . '` SET `disls` = `disls` - 1 WHERE `ID` = ?' . $c);
		$like->execute([$ID]);
		
		$conn->prepare('INSERT INTO `likes` (`whereIz`, `userId`, `type`, `channel`) VALUES (?, ?, ?, ?)')
			 ->execute([$ID, $userId, 1, $where[2]]);
	}

	public static function removeLike(array $data, int $ID, int $type) {		
		$where = self::liketype($type);
		$c = '';
		
		if ($where[0] == 'comments') {
			$c = ' AND `channel` = ' . $where[1];
		}

		$likeAction = $data['type'];

		if ($likeAction == -1) {
			$q = 'UPDATE `' . $where[0] . '` SET `likes` = `likes` ' . $likeAction . ' WHERE `ID` = ?' . $c;
		} else {
			$likeAction = '+1';
			$q = 'UPDATE `' . $where[0] . '` SET `disls` = `disls` ' . $likeAction . ' WHERE `ID` = ?' . $c;
		}
		
		global $conn;
		
		$del = $conn->prepare('DELETE FROM `likes` WHERE ID = ?');
		$del->execute([$data['ID']]);
		if ($del->rowCount() != 0) {
			$like = $conn->prepare($q);
			$like->execute([$ID]);
		}
	}

	public static function checkLike(int $ID, int $userId, int $channel = 0) {
		global $conn;
		$z = $conn->prepare('SELECT ID,type FROM `likes` WHERE `whereIz` = ? AND `userId` = ? AND `channel` = ? LIMIT 1');
		$z->execute([$ID, $userId, $channel]);
		$resp = $z->fetch();
		if (empty($resp)) 
			return false;
		else 
			return $resp;
	}

	public static function refreshGdps(int $gdpsId, string $link) {
		global $conn;
		$conn
			->prepare('UPDATE `gdpses` SET `link` = ? WHERE `gdpses`.`ID` = ?')
			->execute([$link, $gdpsId]);
		$newLink = $conn
			->prepare('SELECT `link` FROM `gdpses` WHERE `gdpses`.`ID` = ?');
		$newLink->execute([$gdpsId]);
		return $newLink->fetch()[0];
	}
}

class Vacans {
	public int $ID;
	public string $title;
	public string $tags;
	public int $mask;
	public string $text;
	public ?string $short = '';
	public int $vacId;
	public int $gdpsId;
	public int $userId;
	public int $checked;
	public int $hasLgbt;
	public int $date;
	public int $status;
	public int $likes;
	public int $disls;
	public int $commsCount;
	
	public ?string $gTitle = null;
	public ?int $gChannel = null;
	public ?int $applied = null;
	public ?int $isLiked = 0;

	public static function fetchAll() {
		global $conn;
		$vacans = $conn->prepare('SELECT * FROM vacans ORDER BY `ID` DESC');
		$vacans->execute();
		$resp = $vacans->fetchAll(PDO::FETCH_CLASS, Vacans::class);
		if ($resp == false)
			return [];
		return $resp;
	}

	public static function fetchAllVacans(int $page = 0) {
		global $conn;
		$query = 'SELECT * FROM vacans WHERE `checked` = 1 LIMIT 9';
		if ($page !== 0) {
			$page = $page * 8;
			$query .= ' OFFSET '.$page;
		}
		$vacans = $conn->prepare($query);
		$vacans->execute();
		$resp = $vacans->fetchAll(PDO::FETCH_CLASS, Vacans::class);
		if ($resp == false)
			return [];
		return $resp;
	}

	public static function fetchVacanById(int $vacId, int $userId = 0) {
		global $conn;
		if ($userId == 0) {
			$vacans = $conn->prepare('SELECT v.*, p.channel as gChannel, p.title as gTitle FROM vacans v LEFT JOIN gdpses p ON v.gdpsId = p.ID WHERE v.ID = ?');
			$vacans->execute([$vacId]);
		} else {
			$vacans = $conn->prepare('SELECT v.*, l.type as isLiked, a.ID as applied, p.channel as gChannel, p.title as gTitle FROM vacans v LEFT JOIN likes l ON v.ID = l.whereIz AND l.userId = ? AND l.channel = 11 LEFT JOIN gdpses p ON v.gdpsId = p.ID LEFT JOIN vacsApplies a ON v.ID = a.vacId AND a.userId = ? WHERE v.ID = ?');
			$vacans->execute([$userId, $userId, $vacId]);
		}
		$vac = $vacans->fetchObject(Vacans::class);
		if ($vac == false) {
			return null;
		}
		return $vac;
	}

	public static function fetchAplById(int $aplId) {
		global $conn;
		$vacans = $conn->prepare('SELECT * FROM vacsApplies WHERE ID = ?');
		$vacans->execute([$aplId]);
		$vac = $vacans->fetchObject(Vacans::class);
		if ($vac == false) {
			return null;
		}
		return $vac;
	}

	public static function fetchVacansByGdps(int $gdpsId, int $userId, bool|int $isAdmin = false, int $page = 0) {
		global $conn;
		$applied = '';
		$applied2 = '';
		$exec = [];
		if ($userId != 0) {
			$applied = ', a.ID as applied';
			$applied2 = ' LEFT JOIN vacsApplies a ON v.ID = a.vacId AND a.userId = ?';
			$exec[] = $userId;
		}
		$exec[] = $gdpsId;
		$query = 'SELECT v.*'.$applied.', p.channel as gChannel, p.title as gTitle FROM vacans v'.$applied2.' LEFT JOIN gdpses p ON v.gdpsId = p.ID WHERE';
		if ($isAdmin == false)
			$query .= ' v.checked = 1 AND';
		$query .= ' v.gdpsId = ? LIMIT 9';
		if ($page !== 0) {
			$page = $page * 8;
			$query .= ' OFFSET '.$page;
		}
		$vacans = $conn->prepare($query);
		$vacans->execute($exec);
		$resp = $vacans->fetchAll(PDO::FETCH_CLASS, Vacans::class);
		if ($resp == false)
			return [];
		return $resp;
	}

	public static function fetchApplies(int $vacId, int $page = 0) {
		global $conn;
		$query = 'SELECT * FROM vacsApplies WHERE `vacId` = ? LIMIT 21';
		if ($page !== 0) {
			$page = $page * 20;
			$query .= ' OFFSET '.$page;
		}
		$vacans = $conn->prepare($query);
		$vacans->execute([$vacId]);
		$resp = $vacans->fetchAll(PDO::FETCH_CLASS, Vacans::class);
		if ($resp == false)
			return [];
		return $resp;
	}

	public static function addVac(array $data) {
		global $conn;
		$stmt = $conn->prepare('INSERT INTO `vacans` (`title`, `text`, `short`, `tags`, `mask`, `gdpsId`, `checked`, `hasLgbt`, `date`) VALUES (?,?,?,?,?,?,?,?,?)');
		$stmt->execute($data);
		return $conn->lastInsertId();
	}

	public static function editVac(array $data) {
		global $conn;
		return $conn->prepare('UPDATE `vacans` SET `title` = ?, `text` = ?, `short` = ?, `tags` = ?, `mask` = ?, `gdpsId` = ?, `checked` = ?, `hasLgbt` = ? WHERE `ID` = ?')
					->execute($data);
	}

	public static function removeVac(int $vacId) {
		global $conn;
		$conn->prepare('DELETE FROM `vacsApplies` WHERE `vacId` = ?')
			 ->execute([$vacId]);
		return $conn->prepare('DELETE FROM `vacans` WHERE `ID` = ?')
					->execute([$vacId]);
	}

	public static function checkApply(int $vacId, int $userId) {
		global $conn;
		$z = $conn->prepare('SELECT ID FROM `vacsApplies` WHERE `vacId` = ? AND `userId` = ? LIMIT 1');
		$z->execute([$vacId, $userId]);
		$resp = $z->fetch();
		if (empty($resp)) 
			return false;
		else 
			return $resp;
	}

	public static function applyVac(array $data) {
		global $conn;
		$stmt = $conn->prepare('INSERT INTO `vacsApplies` (`vacId`, `userId`, `date`) VALUES (?,?,?)');
		$stmt->execute($data);
		return $conn->lastInsertId();
	}

	public static function removeApl(int $aplId) {
		global $conn;
		return $conn->prepare('DELETE FROM `vacsApplies` WHERE `ID` = ?')
					->execute([$aplId]);
	}

	public static function removeAplsByUser(int $userId, int $aplId) {
		global $conn;
		return $conn->prepare('DELETE FROM `vacsApplies` WHERE `userId` = ? AND ID = ?')
					->execute([$userId, $aplId]);
	}

	public function renderApply() {
		$user = Users::fetchById($this->userId);

		$apl = [
			'ID' => (int)$this->ID,
			'vacId' => (int)$this->vacId,
			'userId' => (int)$this->userId,
			'username' => $user->username,
			'resume' => $user->resume,
			'date' => (int)$this->date,
			'status' => (int)$this->status,
		];
		return $apl;
	}

	public function renderVacan($userId = 0) {
		$applied = 0;
		if ($userId != 0 && $this->applied)
			$applied = $this->applied;
		$liked = $this->isLiked;
		if (!$liked)
			$liked = 0;

		$vac = [
			'ID' => (int)$this->ID,
			'title' => $this->title,
			'tags' => $this->tags,
			'text' => $this->text,
			'short' => $this->short,
			'date' => (int)$this->date,
			'isApplied' => $applied,
			'likes' => [
				(int)$this->likes,
				(int)$this->disls,
				(int)$this->commsCount
			],
			'isLiked' => (int)$liked,
			'gId' => (int)$this->gdpsId,
			'gChannel' => (int)$this->gChannel,
			'gTitle' => $this->gTitle
		];
		return $vac;
	}

	public function renderVacanMini(int $userId = 0) {
		$applied = 0;
		if ($userId != 0 && $this->applied)
			$applied = $this->applied;
		$liked = $this->isLiked;
		if (!$liked)
			$liked = 0;

		$text = $this->short;
		if ($text === '')
			$text = mb_substr($this->text, 0, 121);

		$vac = [
			'ID' => (int)$this->ID,
			'title' => $this->title,
			'tags' => $this->tags,
			'text' => $text,
			'date' => (int)$this->date,
			'isApplied' => $applied,
			'likes' => [
				(int)$this->likes,
				(int)$this->disls,
				(int)$this->commsCount
			],
			'isLiked' => (int)$liked,
			'gId' => (int)$this->gdpsId,
			'gChannel' => (int)$this->gChannel,
			'gTitle' => $this->gTitle
		];
		return $vac;
	}
}

class Guides {
	public int $ID;
	public int $wikiId;
	public int $userId;
	public ?string $wikiTag = null;
	public int $wikiChannel;
	public string $title;
	public string $aftertext;
	public string $guidetext;
	public string $text;
	public string $language;
	public string $templates;
	public int $checked;
	public int $date;
	public int $likes;
	public int $disls;
	public int $hasLgbt;
	public ?int $commsCount = null;
	public string $img;
	public int $connectedGdps;
	public int $permLevel;
	public int $forumId;
	public int $mainWiki;
	public string $files;
	public int $filesSize;
	public string $colors;
	public ?int $isLiked = 0;
	public ?string $role = '';
	
	public function ReturnFilesJson() {
		if ($this->files == '')
		return '';
		$json = json_decode($this->files);
		foreach ($json as $title=>$j) {
			$localUser = Users::fetchById($j[1]);
			$json->$title[2] = $localUser->getNickname();
		}
		$json = json_encode($json);
		return $json;
	}

	public static function upgradeFilesJson(string $json, $size) {
		$jsonR = [
			'fileSize' => (int)$size,
			'files' => json_decode($json)
		];
		return $jsonR;
	}
	
	public function ReturnFullFilesJson() {
		if ($this->files == '')
			return '';
		$json = json_decode($this->files);
		foreach ($json as $title=>$j) {
			$localUser = Users::fetchById($j[1]);
			$json->$title[2] = $localUser->getNickname();
		}
		$jsonR = [
			'fileSize' => (int)$this->filesSize,
			'files' => $json
		];
		return $jsonR;
	}

	public function ModifyJsonFiles($decodedJson, int $size) {
		$json = json_encode($decodedJson);

		global $conn;
		$gdpses = $conn->prepare('UPDATE `wikis` SET files = ?, filesSize = ? WHERE `ID` = ?');
		$gdpses->execute([$json, $size, $this->ID]);
		return $this->ID;
	}

	public static function fetchNew(int $page = 0) {
		$page2 = $page * 4;
		global $conn;
		$query = 'SELECT * FROM `guides` WHERE `checked` = 1 ORDER BY `ID` DESC LIMIT 5';
		if ($page2 !== 0)
			$query .= ' OFFSET '.$page2;

		$guides = $conn->prepare($query);
		$guides->execute();
		$resp = $guides->fetchAll(PDO::FETCH_CLASS, Guides::class);
		if ($resp == false)
			return [];
		return $resp;
	}

	public static function fetchWikis(int $page = 0) {
		$page2 = $page * 8;
		global $conn;
		$query = 'SELECT * FROM `wikis` WHERE `checked` = 1 ORDER BY `ID` DESC LIMIT 9';
		if ($page2 !== 0) {
			$query .= ' OFFSET '.$page2;
		}
		$guides = $conn->prepare($query);
		$guides->execute();
		$resp = $guides->fetchAll(PDO::FETCH_CLASS, Guides::class);
		if ($resp == false)
			return [];
		return $resp;
	}

	public static function fetchAllWikis() {
		global $conn;
		$query = 'SELECT * FROM `wikis` ORDER BY `ID` DESC';
		$guides = $conn->prepare($query);
		$guides->execute();
		$resp = $guides->fetchAll(PDO::FETCH_CLASS, Guides::class);
		if ($resp == false)
			return [];
		return $resp;
	}

	public static function fetchAllGuides(int $wikiId) {
		global $conn;
		$query = 'SELECT * FROM `guides` WHERE `wikiChannel` = ? ORDER BY `ID` DESC';
		$guides = $conn->prepare($query);
		$guides->execute([$wikiId]);
		$resp = $guides->fetchAll(PDO::FETCH_CLASS, Guides::class);
		if ($resp == false)
			return [];
		return $resp;
	}

	public static function realFetchAllGuides() {
		global $conn;
		$query = 'SELECT * FROM `guides` ORDER BY `ID` DESC';
		$guides = $conn->prepare($query);
		$guides->execute();
		$resp = $guides->fetchAll(PDO::FETCH_CLASS, Guides::class);
		if ($resp == false)
			return [];
		return $resp;
	}

	public static function fetchWiki(int $wiki, int $page = 0) {
		$page2 = $page * 4;
		global $conn;
		$query = 'SELECT * FROM `guides` WHERE `checked` = 1 AND `wikiChannel` = ? ORDER BY `ID` DESC LIMIT 5';

		if ($page2 !== 0) {
			$query .= ' OFFSET '.$page2;
		}

		$guides = $conn->prepare($query);
		$guides->execute([$wiki]);
		$resp = $guides->fetchAll(PDO::FETCH_CLASS, Guides::class);
		if ($resp == false)
			return [];
		return $resp;
	}

	public static function fetchWikiAdmin(int $wiki, int $page = 0) {
		$page2 = $page * 10;
		global $conn;
		$query = 'SELECT * FROM `guides` WHERE `wikiChannel` = ? ORDER BY `ID` DESC LIMIT 11';

		if ($page2 !== 0) {
			$query .= ' OFFSET '.$page2;
		}

		$guides = $conn->prepare($query);
		$guides->execute([$wiki]);
		$resp = $guides->fetchAll(PDO::FETCH_CLASS, Guides::class);
		if ($resp == false)
			return [];
		return $resp;
	}

	public static function getMyContent(int $userId) {
		global $conn;
		$stmt = $conn->prepare('SELECT g.*, "owner" as role FROM `wikis` g WHERE g.userId = ?'.
			'UNION SELECT g.*, "coowner" as role FROM `wikis` g INNER JOIN `wikisoowners` s ON g.id = s.wikiId WHERE s.userId = ?');
		$stmt->execute([$userId, $userId]);
		$allGdps = $stmt->fetchAll(PDO::FETCH_CLASS, Guides::class);

		$ownedGdps = array_filter($allGdps, function($gdps) {
			return $gdps->role === 'owner';
		});
		$sharedGdps = array_filter($allGdps, function($gdps) {
			return $gdps->role === 'coowner';
		});

		foreach ($ownedGdps as $gdps)
			unset($gdps->role);
		foreach ($sharedGdps as $gdps)
			unset($gdps->role);

		return [array_values($ownedGdps), array_values($sharedGdps)];
	}

	public static function getMyContent2(int $userId) {
		global $conn;
		$a = $conn->prepare('SELECT * FROM `wikis` WHERE `userId` = ?');
		$a->execute([$userId]);
		$b = $conn->prepare('SELECT * FROM `wikisoowners` WHERE `userId` = ?');
		$b->execute([$userId]);
		return [$a->fetchAll(PDO::FETCH_CLASS, Guides::class), $b->fetchAll(PDO::FETCH_CLASS, Guides::class)];
	}

	public static function fetchByIdOrTag($IDorTag, int $wiki) {
		if ((int)$IDorTag !== 0)
			return self::fetchById($IDorTag);
		else
			return self::fetchByTag($IDorTag, $wiki);
	}

	public static function fetchById(int $ID) {
		global $conn;
		$gdpses = $conn->prepare('SELECT * FROM `guides` WHERE `ID` = ?');
		$gdpses->execute([$ID]);
		$gdps = $gdpses->fetchObject(Guides::class);
		if ($gdps == false) {
			return null;
		}
		return $gdps;
	}

	public static function fetchByTag(string $tag, int $wiki) {
		global $conn;
		$gdpses = $conn->prepare('SELECT * FROM `guides` WHERE `wikiTag` = ? AND `wikiChannel` = ?');
		$gdpses->execute([$tag, $wiki]);
		$gdps = $gdpses->fetchObject(Guides::class);
		if ($gdps == false) {
			return null;
		}
		return $gdps;
	}

	public static function setWikiTag(int $ID, int $wiki, string $tag) {
		if (self::fetchByTag($tag, $wiki) !== null)
			return '-3';
		global $conn;
		$gdpses = $conn->prepare('UPDATE `guides` SET wikiTag = ? WHERE `ID` = ?');
		$gdpses->execute([$tag, $ID]);

	}

	public static function uploadGuide(int $userId, string $title, string $aftertext, string $guidetext, string $language, string $img, string $templates, int $date, int $wikiId) {
		global $conn;
		$gdpses = $conn->prepare('INSERT INTO `guides` (`userId`, `title`, `aftertext`, `guidetext`, `language`, `date`, `img`, `templates`, `wikiChannel`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
		$gdpses->execute([$userId, $title, $aftertext, $guidetext, $language, $date, $img, $templates, $wikiId]);
		return $conn->lastInsertId();
	}

	public static function editGuide(int $userId, string $title, string $aftertext, string $guidetext, string $language, string $img, string $templates, int $guidID) {
		global $conn;
		$gdpses = $conn->prepare('UPDATE `guides` SET `title` = ?, `aftertext` = ?, `guidetext` = ?, `language` = ?, `img` = ?, `templates` = ? WHERE `ID` = ?');
		$gdpses->execute([$title, $aftertext, $guidetext, $language, $img, $templates, $guidID]);
		return $guidID;
	}

	public static function removeGuide(int $id) {
		global $conn;
		$pre = $conn->prepare("DELETE FROM `guides` WHERE `ID` = ?");
		return $pre->execute([$id]);
	}

	public static function fetchWikiById(int $ID) {
		global $conn;
		$gdpses = $conn->prepare('SELECT * FROM `wikis` WHERE `ID` = ?');
		$gdpses->execute([$ID]);
		$gdps = $gdpses->fetchObject(Guides::class);
		if ($gdps == false) {
			return null;
		}
		return $gdps;
	}

	public static function fetchMyWikis(int $userId) {
		global $conn;
		$gdpses = $conn->prepare('SELECT * FROM `wikis` WHERE `userId` = ?');
		$gdpses->execute([$userId]);
		$gdps = $gdpses->fetchAll(PDO::FETCH_CLASS, Guides::class);
		if ($gdps == false) {
			return null;
		}
		return $gdps;
	}

	public static function setMainWiki(int $wikiId, int $guideId) {
		global $conn;
		$gdpses = $conn->prepare('UPDATE `wikis` SET mainWiki = ? WHERE `ID` = ?');
		$gdpses->execute([$guideId, $wikiId]);
		return $guideId;
	}

	public static function setColor(int $wikiId, string $color) {
		global $conn;
		$gdpses = $conn->prepare('UPDATE `wikis` SET colors = ? WHERE `ID` = ?');
		$gdpses->execute([$color, $wikiId]);
		return true;
	}

	function getMainPage() {
		if ($this->mainWiki === 0)
			return 0;
		$guide = self::fetchById($this->mainWiki);
		if ($guide->checked === 1)
			return $this->mainWiki;
		return 0;
	}

	public static function uploadWiki(int $userId, string $title, string $text, string $language, string $img, int $date) {
		global $conn;
		$gdpses = $conn->prepare('INSERT INTO `wikis` (`userId`, `title`, `text`, `language`, `date`, `img`) VALUES (?, ?, ?, ?, ?, ?)');
		$gdpses->execute([$userId, $title, $text, $language, $date, $img]);
		return $conn->lastInsertId();
	}

	public static function editWiki(int $userId, string $title, string $text, string $language, string $img, int $wikiID) {
		global $conn;
		$gdpses = $conn->prepare('UPDATE `wikis` SET `checked` = 0, `title` = ?, `text` = ?, `language` = ?, `img` = ? WHERE `ID` = ?');
		$gdpses->execute([$title, $text, $language, $img, $wikiID]);
		return $conn->lastInsertId();
	}

	public static function checkPerms(int $userId, int $wikiId) {
		global $conn;
		
		$a = $conn->prepare('SELECT * FROM `wikis` WHERE `ID` = ? AND `userId` = ?');
		$a->execute([$wikiId, $userId]);
		if ($a->fetchColumn() == true)
			return 2;
		
		$b = $conn->prepare('SELECT * FROM `wikisoowners` WHERE `wikiId` = ? AND `userId` = ?');
		$b->execute([$wikiId, $userId]);
		if ($b->fetchColumn() == true)
			return 1;
		
		return 0;
	}

	public function renderGuide() {
		$guidetext = str_replace(
			 ["\r","\n","\t"],
			['\r','\n','\t'],
			$this->guidetext);
		$guidetext = json_decode($guidetext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$wiki = self::fetchWikiById($this->wikiChannel);
		$json = [
			'guideinfo' => [
				$this->ID,
				$this->title,
				$this->aftertext,
				$this->wikiTag,
				$wiki->colors
			],
			'guidedata' => $guidetext
		];
		return $json;
	}

	public function renderGuideMini(int $userId = 0) {
		$liked = 0;
		if ($userId !== 0)
			$liked = content::checkLike($this->ID, $userId, 7);
		if (empty($liked))
			$liked = 0;
		else
			$liked = $liked['type'];

		$json = [
			(int)$this->ID,
			$this->title,
			$this->language,
			(int)$this->date,
			[
				(int)$this->likes,
				(int)$this->disls,
				(int)$this->commsCount
			],
			$this->img,
			$this->wikiTag,
			$liked,
		];
		return $json;
	}

	public function renderGuideAdmin() {
		$title = $this->title;
		$json = "\"g$this->ID\":[\"$this->ID\",\"$title\",\"$this->language\",\"$this->date\",$this->likes,\"$this->img\",$this->checked,\"$this->wikiTag\"]";
		return $json;
	}

	public function renderGuideLT() {
		$guidetext = str_replace(
			 ["\r","\n","\t"],
			['\r','\n','\t'],
			$this->guidetext);
		$guidetext = json_decode($guidetext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$json = [
			'guideinfo' => [
				$this->ID,
				$this->title,
				str_replace("\n", '\\n', $this->aftertext),
				$this->language,
				$this->img,
				$this->wikiTag
			],
			'guidedata' => $guidetext
		];
		return $json;
	}

	public function renderWiki($userId = 0) {
		$liked = $this->isLiked;
		if (!$liked)
			$liked = 0;

		$wiki = [
			'ID' => (int)$this->ID,
			'title' => $this->title,
			'text' => $this->text,
			'ban' => $this->img,
			'language' => $this->language,
			'date' => (int)$this->date,
			'likes' => [
				(int)$this->likes,
				(int)$this->disls
			],
			'isLiked' => (int)$liked,
			'forumId' => (int)$this->forumId,
			'mainWiki' => $this->getMainPage()
		];

		return $wiki;
	}

	public function renderWikiAdmin() {
		return [
			'ID' => $this->ID,
			'title' => $this->title,
			'text' => $this->text,
			'ban' => $this->img,
			'language' => $this->language,
			'date' => $this->date,
			'likes' => $this->likes,
			'forumId' => $this->forumId,
			'mainWiki' => $this->mainWiki,
			'checked' => $this->checked,
		];
	}

	public function renderWikiLegacy() {
		$text = json_encode($this->text) ?? '""';
		$json = "[$this->ID,\"$this->title\",$text,\"$this->img\",\"$this->language\",$this->date,$this->likes,$this->forumId]";
		return $json;
	}

	public function renderWikiLT() {
		$text = json_encode($this->text) ?? '""';
		$json = "\"w$this->ID\":[$this->ID,\"$this->title\",$text,\"$this->img\",\"$this->language\",$this->date,[$this->likes,$this->disls],$this->userId,$this->connectedGdps,$this->forumId,$this->mainWiki]";
		return $json;
	}

	public function renderWikiLT2() {
		$wiki = [
			'ID' => (int)$this->ID,
			'title' => $this->title,
			'text' => $this->text,
			'ban' => $this->img,
			'language' => $this->language,
			'date' => (int)$this->date,
			'likes' => [
				(int)$this->likes,
				(int)$this->disls
			],
			'userId' => (int)$this->userId,
			'connGdps' => (int)$this->connectedGdps,
			'forumId' => (int)$this->forumId,
			'mainWiki' => $this->mainWiki,
			'color' => $this->colors,
		];
		return $wiki;
	}
}

class Forums {
	public int $ID;
	public int $wikiId;
	public int $forumId;
	public string $username;
	public int $userId;
	public string $title;
	public string $text;
	public int $date;
	public int $likes;
	public int $disls;
	public int $commsCount;
	public int $checked;

	public static function fetchById(int $ID) {
		global $conn;
		$gdpses = $conn->prepare('SELECT * FROM `forums` WHERE `ID` = ?');
		$gdpses->execute([$ID]);
		$gdps = $gdpses->fetchObject(Forums::class);
		if ($gdps == false) {
			return null;
		}
		return $gdps;
	}

	public static function fetchPostById(int $ID) {
		global $conn;
		$gdpses = $conn->prepare('SELECT * FROM `forumPosts` WHERE `ID` = ?');
		$gdpses->execute([$ID]);
		$gdps = $gdpses->fetchObject(Forums::class);
		if ($gdps == false) {
			return null;
		}
		return $gdps;
	}

	public static function getAllPosts() {
		global $conn;
		$db = $conn->prepare('SELECT * FROM `forumPosts` ORDER BY `ID` DESC');
		$db->execute();
		$posts = $db->fetchAll(PDO::FETCH_CLASS, Forums::class);

		return $posts;
	}

	public static function getPosts(int $forumId) {
		global $conn;
		$db = $conn->prepare('SELECT * FROM `forumPosts` WHERE `forumId` = ? ORDER BY `ID` DESC');
		$db->execute([$forumId]);
		$posts = $db->fetchAll(PDO::FETCH_CLASS, Forums::class);

		return $posts;
	}

	public static function createForum(int $wikiId, int $date) {
		global $conn;
		$forum = $conn->prepare('INSERT INTO `forums` (`wikiId`, `date`) VALUES (?, ?)');
		$forum->execute([$wikiId, $date]);
		$forumId = $conn->lastInsertId();
		
		$wiki = $conn->prepare('UPDATE `wikis` SET `forumId` = ? WHERE `ID` = ?');
		$wiki->execute([$forumId, $wikiId]);

		return $forumId;
	}

	public static function uploadPost(int $forumId, string $username, int $userId, string $title, string $text, int $date) {
		global $conn;
		$forum = $conn->prepare('INSERT INTO `forumPosts` (`forumId`, `username`, `userId`, `title`, `text`, `date`) VALUES (?, ?, ?, ?, ?, ?)');
		$forum->execute([$forumId, $username, $userId, $title, $text, $date]);
		return $conn->lastInsertId();
	}

	public function renderPost($userId = 0) {
		$liked = 0;
		if ($userId !== 0)
			$liked = content::checkLike($this->ID, $userId, 9);
		if (empty($liked))
			$liked = 0;
		else
			$liked = $liked['type'];

		$text = mb_substr(base64_decode($this->text), 0, 121);
		$json = [
			(int)$this->ID,
			(int)$this->forumId,
			$this->username,
			(int)$this->userId,
			$this->title,
			$text,
			(int)$this->date,
			[
				(int)$this->likes,
				(int)$this->disls,
				(int)$this->commsCount
			],
			$liked
		];
		return $json;
	}
}

class WikiTemplates {
	public int $ID;
	public int $wikiId;
	public string $name;
	public string $args;
	public string $method;
	public string $content;

	//public function __construct(int $wikiId = 0, string $name = '', string $args = '', string $method = '', string $content = '') {
	//	$this->ID = 0;
	//	$this->wikiId = $wikiId;
	//	$this->name = $name;
	//	$this->args = $args;
	//	$this->method = $method;
	//	$this->content = $content;
	//}

	public static function getOneTemplateByWiki(int $wikiId, string $template) {
		global $conn;
		$db = $conn->prepare('SELECT * FROM `wikiTemplates` WHERE `wikiId` = ? AND name = ?');
		$db->execute([$wikiId, $template]);
		$posts = $db->fetchObject(WikiTemplates::class);

		return $posts;
	}

	public static function getTemplatesByWiki(int $wikiId, array $templates) {
		global $conn;
		$query = 'SELECT * FROM `wikiTemplates` WHERE `wikiId` = ? AND name IN (';
		$exec = [$wikiId];
		$query2 = '';
		foreach ($templates as $t) {
			$query2 .= ',?';
			array_push($exec, $t);
		}
		$query = $query . ltrim($query2, ',') . ')';
		$db = $conn->prepare($query);
		$db->execute($exec);
		$temps = $db->fetchAll(PDO::FETCH_CLASS, WikiTemplates::class);

		return $temps;
	}

	public static function getAllTemplatesByWiki(int $wikiId, int $page = 0) {
		global $conn;
		$query = 'SELECT * FROM `wikiTemplates` WHERE `wikiId` = ? ORDER BY `ID` DESC LIMIT 11';
		$exec = [$wikiId];
		if ($page !== 0) {
			$page2 = $page * 10;
			$query .= ' OFFSET '.$page2;
		}
		$db = $conn->prepare($query);
		$db->execute($exec);
		$temps = $db->fetchAll(PDO::FETCH_CLASS, WikiTemplates::class);

		return $temps;
	}

	public static function saveTemplate(int $wikiId, string $name, string $args, string $method, string $content) {
		global $conn;
		$db2 = $conn->prepare('SELECT * FROM `wikiTemplates` WHERE `wikiId` = ? AND `name` = ?');
		$db2->execute([$wikiId, $name]);
		$db2->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, 'WikiTemplates');
		$row = $db2->fetch();
		$ID = $row ? $row->ID : false;

		$query = 'UPDATE `wikiTemplates` SET `args` = ?, `method` = ?, `content` = ? WHERE `wikiId` = ? AND `name` = ?';
		if (!$ID)
			$query = 'INSERT INTO `wikiTemplates` (`args`, `method`, `content`, `wikiId`, `name`) VALUES (?,?,?,?,?)';
		$exec = [$args, $method, $content, $wikiId, $name];
		$db = $conn->prepare($query);
		$db->execute($exec);

		$db2 = $conn->prepare('SELECT * FROM `wikiTemplates` WHERE `wikiId` = ? AND `name` = ?');
		$db2->execute([$wikiId, $name]);
		$db2->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, 'WikiTemplates');
		$row = $db2->fetch();
		return $row;
	}

	public static function deleteTemplate(int $wikiId, string $template) {
		global $conn;
		$db = $conn->prepare('DELETE FROM `wikiTemplates` WHERE `wikiId` = ? AND name = ?');
		return $db->execute([$wikiId, $template]);
	}

	public function renderTemplate() {
		return [
			json_decode($this->args) ?? '',
			str_replace('\n', "\n", $this->content),
			$this->method
		];
	}
}