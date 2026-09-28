<?php

function likeTypeSearch(int $type) {
		switch ($type) {
			case 0:
				return 0;
			case 1:
				return 0;
			case 2:
				return 0;
			case -1:
				return 8;
			case -2:
				return 0;
			case -5:
				return 11;
		}
}

function mergeAndSortTags(array $tags, array $oss): array {
	$result = array_map('intval', array_merge($tags, $oss));
	sort($result);
	return $result;
}

function createBitmask(array $tagIds): int {
	$mask = 0;
	foreach ($tagIds as $tagId) {
		$mask |= (1 << ((int)$tagId - 1));
	}
	return $mask;
}

function NewGdpsFinder(int $userId, int $method, int $type, int $page, array $tags = [], array $oss = [], string $name = '', int $lgbtBan = 0) {
	$prep = '';
	$exec = [];
	$likeFull = '';
	$likeJoin = '';
	if ($userId != 0) {
		$likeJoin = ', l.type as isLiked';
		$likeFull = ' LEFT JOIN likes l ON g.ID = l.whereIz AND l.userId = ? AND l.channel = '.likeTypeSearch($type);
		array_push($exec, $userId);
	}

	$defGdpsData = 'ID,g.title,g.channel,g.description,g.short,g.tags,g.os,g.mask,g.likes,g.disls,g.commsCount,g.author,g.username,g.img,g.ban,g.connectedWiki';
	switch ($type) {
		case -5:
			if ($userId != 0) {
				$likeJoin .= ', a.ID as applied';
				$likeFull .= ' LEFT JOIN vacsApplies a ON g.ID = a.vacId AND a.userId = ?';
				$exec[] = $userId;
			}
			$prep = "SELECT g.*, p.channel as gChannel, p.title as gTitle$likeJoin FROM vacans g$likeFull LEFT JOIN gdpses p ON g.gdpsId = p.ID WHERE g.checked = 1";
			break;
		case -2:
			$prep = "SELECT g.$defGdpsData$likeJoin FROM gdpses g$likeFull WHERE g.checked = 1";
			break;
		case -1:
			$prep = "SELECT g.*$likeJoin FROM wikis g$likeFull WHERE g.checked = 1";
			break;
		case 0:
		case 1:
		case 2:
			$prep = "SELECT g.$defGdpsData$likeJoin FROM gdpses g$likeFull WHERE g.checked = 1 AND g.channel = ?";
			array_push($exec, $type);
			break;
	}
	if ($lgbtBan !== 0)
		$prep .= " AND g.hasLgbt = 0";

	if ($name !== '') {
		$prep .= " AND LOWER(g.title) LIKE LOWER(?)";
		array_push($exec, "%$name%");
	}

	$mask = mergeAndSortTags($tags, $oss);
	
	$bitmask = array_reduce($mask, function($mask, $tagId) {
		return $mask | (1 << ($tagId - 1));
	}, 0);
	
	if ($bitmask !== 0) {
		$prep .= " AND (g.mask & ?) = ?";
		array_push($exec, $bitmask, $bitmask);
	}

	switch ($method) {
		case 0: $prep .= ' ORDER BY g.ID DESC';					 break;
		case 1: $prep .= ' ORDER BY g.likes DESC';				  break;
		case 2: $prep .= ' ORDER BY g.disls';					   break;
		case 3: $prep .= ' ORDER BY g.points DESC';				 break;
		case 4: $prep .= ' AND g.MC = 1 ORDER BY g.points DESC';	break;
		case 5: $prep .= ' AND g.CC = 1 ORDER BY g.points DESC';	break;
	}

	$prep .= " LIMIT 9";
	if ($page != 0) {
		$page = $page * 8;
		$prep .= " OFFSET $page";
	}

	global $conn;
	$gdpsesPre = $conn->prepare($prep);
	$gdpsesPre->execute($exec);

	if ($type == -5)
		return $gdpsesPre->fetchAll(PDO::FETCH_CLASS, Vacans::class);
	if ($type == -1)
		return $gdpsesPre->fetchAll(PDO::FETCH_CLASS, Guides::class);
	else
		return $gdpsesPre->fetchAll(PDO::FETCH_CLASS, GDPS::class);
}

function masterConnect(int $return, int $wikiId, int $gdpsId) {
	global $conn;
	$stmt = $conn->prepare("UPDATE `gdpses` SET `connectedWiki` = ? WHERE `gdpses`.`ID` = ?");
	$stmt->execute([$wikiId, $gdpsId]);
	$stmt = $conn->prepare("UPDATE `wikis` SET `connectedGdps` = ? WHERE `wikis`.`ID` = ?");
	$stmt->execute([$gdpsId, $wikiId]);

	if ($return == 0)
		return $gdpsId;
	else
		return $wikiId;
}

function dropConnectWiki(int $wikiId) {
	global $conn;
	$stmt = $conn->prepare("SELECT `connectedGdps` FROM `wikis` WHERE `ID` = ?");
	$stmt->execute([$wikiId]);
	$gdpsId = $stmt->fetch()[0];
	$stmt = $conn->prepare("UPDATE `gdpses` SET `connectedWiki` = 0 WHERE `gdpses`.`ID` = ?");
	$stmt->execute([$gdpsId]);
	$stmt = $conn->prepare("UPDATE `wikis` SET `connectedGdps` = 0 WHERE `wikis`.`ID` = ?");
	$stmt->execute([$wikiId]);
}

function dropConnectGdps(int $gdpsId) {
	global $conn;
	$stmt = $conn->prepare("SELECT `connectedWiki` FROM `gdpses` WHERE `ID` = ?");
	$stmt->execute([$gdpsId]);
	$wikiId = $stmt->fetch()[0];
	$stmt = $conn->prepare("UPDATE `gdpses` SET `connectedWiki` = 0 WHERE `gdpses`.`ID` = ?");
	$stmt->execute([$gdpsId]);
	$stmt = $conn->prepare("UPDATE `wikis` SET `connectedGdps` = 0 WHERE `wikis`.`ID` = ?");
	$stmt->execute([$wikiId]);
}