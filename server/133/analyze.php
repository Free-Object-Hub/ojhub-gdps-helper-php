<?php
header('Content-Type: application/json');

class PHPAnalyzer {
	private $noEndpointsFolders = ['api'];
	private $blackFiles = ['adminAction.php'];
	private $postBlacklist = ['token'];
	private $pointName = [];
	private $pointData = [];
	private $endpoints = [];
	private $includeFiles = [];
	private $scannedFiles = 0;
	private $blacklistedFiles = 0;
	private $ignoredEndpoints = 0;

	public function analyze() {
		$this->scanDirectory('.');
		
		$result = [
			'stats' => [
				'total_files_scanned' => $this->scannedFiles,
				'blacklisted_files' => $this->blacklistedFiles,
				'endpoints_found' => count($this->endpoints),
				'ignored_endpoints' => $this->ignoredEndpoints,
				'include_files_found' => count($this->includeFiles)
			],
			'endpoints' => $this->getSortedEndpoints()
		];
		
		if (!empty($this->includeFiles)) {
			$result['include_files'] = $this->includeFiles;
		}
		
		echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	}

	private function scanDirectory($dir) {
		$files = scandir($dir);
		
		foreach ($files as $file) {
			if ($file == '.' || $file == '..') continue;
			
			$path = $dir . '/' . $file;
			
			if (is_dir($path)) {
				$this->scanDirectory($path);
				continue;
			}
			
			if (pathinfo($path, PATHINFO_EXTENSION) !== 'php') {
				continue;
			}
			
			$this->scannedFiles++;
			
			if (in_array($file, $this->blackFiles)) {
				$this->blacklistedFiles++;
				continue;
			}
			
			$this->analyzeFile($path);
		}
	}

	private function analyzeFile($filePath) {
		$content = file_get_contents($filePath);
		if ($content === false) return;
		
		$relativePath = ltrim($filePath, './');
		
		$isInclude = $this->checkIfInclude($content, $relativePath);
		$isEndpoint = !$isInclude && $this->checkIfEndpoint($content, $relativePath);
		
		if ($isInclude) {
			$this->includeFiles[] = $relativePath;
			return;
		}
		
		if (!$isEndpoint) return;
		
		if ($this->shouldIgnoreEndpoint($relativePath)) {
			$this->ignoredEndpoints++;
			return;
		}
		
		$methods = $this->extractHttpMethods($content);
		$getParams = $this->extractSuperGlobalParams($content, '$_GET');
		$postParams = $this->extractSuperGlobalParams($content, '$_POST');
		
		$postParams = array_diff($postParams, $this->postBlacklist);
		
		$this->endpoints[$relativePath] = [
			'file' => $relativePath,
			'methods' => $methods,
			'get_params' => array_values($getParams),
			'post_params' => array_values($postParams),
			'has_get' => !empty($getParams),
			'has_post' => !empty($postParams)
		];
	}

	private function checkIfInclude($content, $filePath) {
		$includePatterns = [
			'/include\s*[\'"\(]' . preg_quote(basename($filePath), '/') . '[\'"\)]/i',
			'/include_once\s*[\'"\(]' . preg_quote(basename($filePath), '/') . '[\'"\)]/i',
			'/require\s*[\'"\(]' . preg_quote(basename($filePath), '/') . '[\'"\)]/i',
			'/require_once\s*[\'"\(]' . preg_quote(basename($filePath), '/') . '[\'"\)]/i'
		];
		
		foreach ($includePatterns as $pattern) {
			if (preg_match($pattern, $content)) {
				return true;
			}
		}
		
		$parentDir = dirname($filePath);
		$includeInParentPatterns = [
			'/include\s*[\'"\(].*' . preg_quote($filePath, '/') . '[\'"\)]/i',
			'/include\s*[\'"\(].*' . preg_quote($parentDir . '/' . basename($filePath), '/') . '[\'"\)]/i'
		];
		
		foreach ($includeInParentPatterns as $pattern) {
			if (preg_match($pattern, $content)) {
				return true;
			}
		}
		
		return false;
	}

	private function checkIfEndpoint($content, $filePath) {
		$endpointPatterns = [
			'/\$_GET/i',
			'/\$_POST/i',
			'/Route::/i',
			'/@route/i',
			'/->(get|post|put|delete|patch|any)\(/i',
			'/form.*action=.*php/i',
			'/header\(.*Location:/i',
			'/http_response_code\(/i'
		];
		
		foreach ($endpointPatterns as $pattern) {
			if (preg_match($pattern, $content)) {
				return true;
			}
		}
		
		return false;
	}

	private function shouldIgnoreEndpoint($filePath) {
		foreach ($this->noEndpointsFolders as $folder) {
			if (strpos($filePath, $folder) !== false) {
				return true;
			}
		}
		return false;
	}

	private function extractHttpMethods($content) {
		$methods = [];
		
		if (preg_match('/\$_GET/i', $content)) {
			$methods[] = 'GET';
		}
		
		if (preg_match('/\$_POST/i', $content)) {
			$methods[] = 'POST';
		}
		
		$methodPatterns = [
			'/->get\(/' => 'GET',
			'/->post\(/' => 'POST',
			'/->put\(/' => 'PUT',
			'/->delete\(/' => 'DELETE',
			'/->patch\(/' => 'PATCH',
			'/->any\(/' => 'ANY'
		];
		
		foreach ($methodPatterns as $pattern => $method) {
			if (preg_match($pattern, $content)) {
				$methods[] = $method;
			}
		}
		
		return array_unique($methods);
	}

	private function extractSuperGlobalParams($content, $global) {
		$params = [];
		
		$pattern = '/' . preg_quote($global, '/') . '\[[\'"]([^\'"]+)[\'"]\]/';
		preg_match_all($pattern, $content, $matches);
		
		if (!empty($matches[1])) {
			$params = array_unique($matches[1]);
		}
		
		$pattern2 = '/' . preg_quote($global, '/') . '\[([^\]]+)\]/';
		preg_match_all($pattern2, $content, $matches2);
		
		foreach ($matches2[1] as $match) {
			$match = trim($match, '"\'\t\n\r\0\x0B');
			if (!empty($match) && !is_numeric($match)) {
				$params[] = $match;
			}
		}
		
		return array_unique($params);
	}

	private function getSortedEndpoints() {
		$grouped = [];
		
		foreach ($this->endpoints as $endpoint) {
			$dir = dirname($endpoint['file']);
			$grouped[$dir][] = $endpoint;
		}
		
		ksort($grouped);
		
		foreach ($grouped as &$endpoints) {
			usort($endpoints, function($a, $b) {
				return strcmp($a['file'], $b['file']);
			});
		}
		
		return $grouped;
	}
}

$analyzer = new PHPAnalyzer();
$analyzer->analyze();