<?php
// analysis.php - Анализатор PHP кода для поиска эндпоинтов

header('Content-Type: application/json; charset=utf-8');

class PHPAnalyzer {
	private $noEndpointsFolders = ['api'];
	private $blackFiles = ['adminAction.php'];
	private $postBlacklist = ['token'];
	
	private $pointName = [];
	private $pointData = [];
	private $endpoints = [];
	private $includeFiles = [];
	
	private $fileCount = 0;
	private $endpointCount = 0;
	private $blacklistedCount = 0;
	private $includedCount = 0;
	
	public function run() {
		$this->scanDirectory('.');
		$this->analyzeFiles();
		$this->renderOutput();
	}
	
	/**
	 * Сканирование всех PHP файлов в директории и поддиректориях
	 */
	private function scanDirectory($dir) {
		$files = scandir($dir);
		
		foreach ($files as $file) {
			if ($file == '.' || $file == '..') continue;
			
			$path = $dir . '/' . $file;
			
			if (is_dir($path)) {
				$this->scanDirectory($path);
			} elseif (pathinfo($path, PATHINFO_EXTENSION) == 'php') {
				$this->fileCount++;
				
				// Проверка на черный список файлов
				if (in_array($file, $this->blackFiles)) {
					$this->blacklistedCount++;
					continue;
				}
				
				$this->pointName[] = $path;
			}
		}
	}
	
	/**
	 * Анализ найденных файлов
	 */
	private function analyzeFiles() {
		foreach ($this->pointName as $index => $filePath) {
			$content = file_get_contents($filePath);
			
			// Проверка, является ли файл инклудом
			if ($this->isIncludeFile($content, $filePath)) {
				$this->includedCount++;
				$this->includeFiles[] = $filePath;
				unset($this->pointName[$index]);
				continue;
			}
			
			// Проверка, является ли файл эндпоинтом
			if (!$this->isEndpoint($content, $filePath)) {
				unset($this->pointName[$index]);
				continue;
			}
			
			// Анализ свойств $_GET и $_POST
			$analysis = $this->analyzeProperties($content);
			
			// Пропускаем файлы из папок без эндпоинтов
			if ($this->shouldSkipEndpoint($filePath)) {
				continue;
			}
			
			$this->endpointCount++;
			
			// Извлечение описания из комментариев
			$description = $this->extractComments($content);
			
			$this->pointData[$filePath] = [
				'methods' => $analysis['methods'],
				'get_params' => $analysis['get_params'],
				'post_params' => $analysis['post_params'],
				'description' => $description['description'] ?? '',
				'return_value' => $description['return_value'] ?? ''
			];
		}
		
		// Обновляем массив pointName после удалений
		$this->pointName = array_values($this->pointName);
	}
	
	/**
	 * Проверка, является ли файл инклудом
	 */
	private function isIncludeFile($content, $filePath) {
		// Поиск паттернов включения файлов
		$patterns = [
			'/include\s*[\'"\(]|[^a-z]include\s*\(/i',
			'/require\s*[\'"\(]|[^a-z]require\s*\(/i',
			'/include_once|require_once/'
		];
		
		foreach ($patterns as $pattern) {
			if (preg_match($pattern, $content)) {
				// Дополнительная проверка: если в файле есть прямое обращение к $_GET/$_POST,
				// возможно это всё же эндпоинт
				if (preg_match('/\$_GET\[|\$_POST\[/', $content)) {
					return false;
				}
				return true;
			}
		}
		
		// Проверка по имени файла
		$filename = basename($filePath);
		if (preg_match('/^(inc|include|functions|class|helper)\.|\.(inc|class)\./i', $filename)) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Проверка, является ли файл эндпоинтом
	 */
	private function isEndpoint($content, $filePath) {
		// Проверяем наличие работы с HTTP данными
		$endpointPatterns = [
			'/\$_GET\[/',
			'/\$_POST\[/',
			'/\$_REQUEST\[/',
			'/echo\s+.*html|json|xml/i',
			'/header\s*\(/i',
			'/Content-Type:/i',
			'/application\/json|application\/xml|text\/html/i'
		];
		
		foreach ($endpointPatterns as $pattern) {
			if (preg_match($pattern, $content)) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Проверка, нужно ли пропустить эндпоинт
	 */
	private function shouldSkipEndpoint($filePath) {
		foreach ($this->noEndpointsFolders as $folder) {
			if (strpos($filePath, '/' . $folder . '/') !== false) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Анализ свойств $_GET и $_POST
	 */
	private function analyzeProperties($content) {
		$result = [
			'methods' => [],
			'get_params' => [],
			'post_params' => []
		];
		
		// Определение методов
		if (preg_match('/\$_GET\[/', $content)) {
			$result['methods'][] = 'GET';
		}
		if (preg_match('/\$_POST\[/', $content)) {
			$result['methods'][] = 'POST';
		}
		
		if (empty($result['methods'])) {
			$result['methods'][] = 'UNKNOWN';
		}
		
		// Поиск всех $_GET параметров
		preg_match_all('/\$_GET\s*\[\s*[\'"]([^\'"]+)[\'"]\s*\]/', $content, $getMatches);
		if (!empty($getMatches[1])) {
			$result['get_params'] = array_unique($getMatches[1]);
		}
		
		// Поиск всех $_POST параметров (исключая черный список)
		preg_match_all('/\$_POST\s*\[\s*[\'"]([^\'"]+)[\'"]\s*\]/', $content, $postMatches);
		if (!empty($postMatches[1])) {
			$filtered = array_diff(array_unique($postMatches[1]), $this->postBlacklist);
			$result['post_params'] = array_values($filtered);
		}
		
		return $result;
	}
	
	/**
	 * Извлечение описания из комментариев
	 */
	private function extractComments($content) {
		$result = [
			'description' => '',
			'return_value' => ''
		];
		
		// Многострочные комментарии
		preg_match_all('/\/\*\*?(.*?)\*\//s', $content, $multilineMatches);
		
		foreach ($multilineMatches[1] as $comment) {
			$lines = explode("\n", $comment);
			foreach ($lines as $line) {
				$line = trim($line, " \t\n\r\0\x0B*");
				
				// Описание после @
				if (preg_match('/@\s*(.+)/', $line, $descMatch)) {
					$result['description'] .= $descMatch[1] . ' ';
				}
				
				// Возвращаемое значение после #
				if (preg_match('/#\s*(.+)/', $line, $returnMatch)) {
					$result['return_value'] .= $returnMatch[1] . ' ';
				}
			}
		}
		
		// Однострочные комментарии
		preg_match_all('/\/\/\s*[#@]\s*(.+)/', $content, $singlelineMatches);
		foreach ($singlelineMatches[1] as $comment) {
			if (strpos($comment, '@') === 0) {
				$result['description'] .= substr($comment, 1) . ' ';
			} elseif (strpos($comment, '#') === 0) {
				$result['return_value'] .= substr($comment, 1) . ' ';
			}
		}
		
		$result['description'] = trim($result['description']);
		$result['return_value'] = trim($result['return_value']);
		
		return $result;
	}
	
	/**
	 * Группировка эндпоинтов по папкам
	 */
	private function groupByFolders() {
		$grouped = [];
		
		foreach ($this->pointData as $filePath => $data) {
			$dir = dirname($filePath);
			$filename = basename($filePath);
			
			if (!isset($grouped[$dir])) {
				$grouped[$dir] = [];
			}
			
			$grouped[$dir][$filename] = [
				'file' => $filename,
				'path' => $filePath,
				'methods' => $data['methods'],
				'get_params' => $data['get_params'],
				'post_params' => $data['post_params'],
				'description' => $data['description'],
				'return_value' => $data['return_value']
			];
			
			// Сортировка по имени файла внутри папки
			ksort($grouped[$dir]);
		}
		
		// Сортировка папок по алфавиту
		ksort($grouped);
		
		return $grouped;
	}
	
	/**
	 * Вывод результатов
	 */
	private function renderOutput() {
		$groupedEndpoints = $this->groupByFolders();
		
		$output = [
			'statistics' => [
				'total_files_scanned' => $this->fileCount,
				'endpoints_found' => $this->endpointCount,
				'included_files' => $this->includedCount,
				'blacklisted_files' => $this->blacklistedCount
			],
			'endpoints' => $groupedEndpoints,
			'included_files' => $this->includeFiles,
			'configuration' => [
				'no_endpoints_folders' => $this->noEndpointsFolders,
				'blacklisted_files' => $this->blackFiles,
				'post_blacklist' => $this->postBlacklist
			]
		];
		
		echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	}
}

// Запуск анализатора
$analyzer = new PHPAnalyzer();
$analyzer->run();