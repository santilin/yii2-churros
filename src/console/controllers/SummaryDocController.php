<?php
namespace santilin\churros\console\controllers;

use Yii;
use yii\helpers\Console;
use yii\console\Controller;

/**
 * Churros code documentator for @doc annotations
 *
 * @author Santilín <santi@noviolento.es>
 * @since 1.0
 */
class SummaryDocController extends Controller
{
    const VERSION = '0.1';
    private $verbose = true;

	private $suiteParams = [
		'intranext' => [
			'doc_pattern' => '\\* @doc',
			'find_patterns' => ["*.php", "*.css", "TODO", "*.json", "*.js" ],
			'find_exclude' => [ 'vendor', 'runtime', 'web/assets' ],
			'dest_path' => '/home/santilin/devel/yii2base/apps/intranext/docs/dev',
		],
	];

    public function actionBuild(string $suite)
    {
        $this->stdout("Generating documentation for $suite\n", Console::FG_GREEN, Console::BOLD);

        $source_dir = Yii::getAlias('@app');
        $code_comments = $this->getCodeComments($source_dir, $this->suiteParams[$suite]);

        if ($this->verbose) {
            $this->stdout("Found " . count($code_comments) . " @doc comments\n");
        }

        $this->generateSingleHtmlFile($code_comments, $suite);
    }
	private function getCodeComments(string $source_path, array $suite_params): array
	{
		$find_patterns = $suite_params['find_patterns'];
		$excluded_patterns = $suite_params['find_exclude'] ?? [];
		$doc_pattern = $suite_params['doc_pattern'];

		// Build find command with excludes
		$command_parts = ["cd $source_path;", "find ."];

		// Add file patterns
		if (count($find_patterns) > 1) {
			$pattern_parts = [];
			foreach ($find_patterns as $pattern) {
				$pattern_parts[] = "-name \"$pattern\"";
			}
			$command_parts[] = "\\( " . implode(' -o ', $pattern_parts) . " \\)";
		} else {
			$command_parts[] = "-name \"{$find_patterns[0]}\"";
		}

		$command_parts[] = "-type f";

		// Add exclude patterns
		foreach ($excluded_patterns as $exclude) {
			$command_parts[] = "-not -path \"*/$exclude/*\"";
		}

		// Add grep command
		$command_parts[] = "-exec grep -l \"$doc_pattern\" {} +";

		$command = implode(' ', $command_parts);

		if ($this->verbose) {
			$this->stdout("Command: $command\n");
		}

		exec($command, $files, $result);
		$comments = [];
        foreach ($files as $file) {
            $full_path = realpath($source_path . '/' . $file);
            $file_comments = $this->parseFileForDocComments($full_path);
            if (!empty($file_comments)) {
                $comments[] = $file_comments;
            }
        }
		return $comments;
	}

    private function parseFileForDocComments(string $filename): array
    {
        $content = file_get_contents($filename);
        $doc_comments = [];

        // Find all docblock comments with their line numbers
        $line_number = 1;
        $lines = explode("\n", $content);
        $in_doc_comment = false;
        $current_comment = '';
        $comment_start_line = 0;

        foreach ($lines as $line) {
            // Check if we're starting a docblock
            if (!$in_doc_comment && preg_match('/^\s*\/\*\*/', $line)) {
                $in_doc_comment = true;
                $current_comment = $line . "\n";
                $comment_start_line = $line_number;
            }
            // Check if we're inside a docblock
            elseif ($in_doc_comment) {
                $current_comment .= $line . "\n";

                // Check if we're ending the docblock
                if (strpos($line, '*/') !== false) {
                    $in_doc_comment = false;

                    // Check if this docblock contains @doc
                    if (strpos($current_comment, '@doc') !== false) {
                        $parsed_comments = $this->parseDocComment($current_comment, $filename, $comment_start_line);
                        $doc_comments = array_merge($doc_comments, $parsed_comments);
                    }

                    $current_comment = '';
                }
            }

            $line_number++;
        }

        return $doc_comments;
    }

    private function parseDocComment(string $comment, string $filename, int $start_line): array
    {
        // Remove the opening /** and closing */
        $comment = preg_replace('/^\/\*\*|\*\/$/', '', $comment);

        // Split into lines and clean them
        $lines = explode("\n", $comment);
        $clean_lines = [];

        foreach ($lines as $line) {
            // Remove leading * and whitespace
            $line = preg_replace('/^\s*\*\s?/', '', $line);
            $clean_lines[] = trim($line);
        }

        $doc_blocks = [];
        $current_block = null;

        foreach ($clean_lines as $line_number => $line) {
            // Check if this line starts a new @doc block
            if (strpos($line, '@doc') === 0) {
                // If we were building a previous block, save it
                if ($current_block !== null && !empty($current_block['summary']) && !empty($current_block['body'])) {
                    $doc_blocks[] = $current_block;
                }

                // Start new block
                $current_block = [
                    'summary' => '',
                    'body' => trim(substr($line, 4)), // Remove '@doc'
                    'file' => $filename,
                    'line' => $start_line + $line_number + 1 // +1 because we removed opening /**
                ];

                // Look backwards for the previous non-empty line as summary
                for ($i = $line_number - 1; $i >= 0; $i--) {
                    if (!empty($clean_lines[$i])) {
                        $current_block['summary'] = $clean_lines[$i];
                        break;
                    }
                }
            }
            // If we're inside a @doc block and line is not empty
            elseif ($current_block !== null && !empty($line) && $line !== '*') {
                // Stop if we hit an empty line after already having body content
                if (strpos($line, '@') === 0) {
                    // This is another tag, save current block and stop
                    if (!empty($current_block['body'])) {
                        $doc_blocks[] = $current_block;
                    }
                    $current_block = null;
                } else {
                    $current_block['body'] .= "\n" . $line;
                }
            }
            // If we hit an empty line and we have a current block with body, save it
            elseif ($current_block !== null && empty($line) && !empty($current_block['body'])) {
                $doc_blocks[] = $current_block;
                $current_block = null;
            }
        }

        // Don't forget the last block
        if ($current_block !== null && !empty($current_block['summary']) && !empty($current_block['body'])) {
            $doc_blocks[] = $current_block;
        }

        return $doc_blocks;
    }

	private function generateSingleHtmlFile(array $comments, string $suite)
	{
		$ncomments = count($comments);
		$html = <<<HTML
<!DOCTYPE html>
<html lang='es'>
<head>
	<meta charset='UTF-8'>
	<meta name='viewport' content='width=device-width, initial-scale=1.0'>
	<title>Documentación - $suite</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			margin: 20px;
			max-width: 1200px;
			margin-left: auto;
			margin-right: auto;
		}
		h1 {
			color: #333;
			border-bottom: 2px solid #333;
			padding-bottom: 10px;
		}
		.stats {
			background: #f8f9fa;
			padding: 10px;
			border-radius: 5px;
			margin-bottom: 20px;
		}
		details {
			margin: 15px 0;
			border: 1px solid #ddd;
			border-radius: 5px;
			background: white;
			box-shadow: 0 1px 3px rgba(0,0,0,0.1);
		}
		summary {
			padding: 15px;
			cursor: pointer;
			background: #f8f9fa;
			font-weight: bold;
			font-size: 1.1em;
			color: #333;
			border-left: 4px solid #007acc;
		}
		summary:hover {
			background: #e9ecef;
		}
		.doc-body {
			padding: 15px;
			background: white;
			border-top: 1px solid #ddd;
			line-height: 1.6;
		}
		.file-info {
			font-size: 0.9em;
			color: #666;
			margin-top: 15px;
			padding: 8px;
			background: #f8f9fa;
			border-radius: 3px;
			border-left: 3px solid #6c757d;
		}
		.doc-body pre {
			background: #f8f9fa;
			padding: 10px;
			border-radius: 3px;
			overflow-x: auto;
			border-left: 3px solid #007acc;
		}
		.doc-body code {
			background: #f8f9fa;
			padding: 2px 4px;
			border-radius: 3px;
			font-family: 'Courier New', monospace;
		}
	</style>
</head>
<body>
	<h1>Documentación - $suite</h1>

	<div class='stats'>
		<strong>Total de documentación encontrada:</strong>$ncomments elementos<br>
	</div>
HTML;

		// Group comments by file for better organization
		$grouped_comments = [];
		foreach ($comments as $comment) {
			$grouped_comments[$comment['file']][] = $comment;
		}

		// Sort files alphabetically
		ksort($grouped_comments);

		foreach ($grouped_comments as $filename => $file_comments) {
			$short_filename = basename($filename);
			$html .= "
		<details>
			<summary>📁 $short_filename</summary>
			<div class='doc-body'>";

			foreach ($file_comments as $index => $comment) {
				$summary = htmlspecialchars($comment['summary']);
				$body = $this->formatDocBody($comment['body']);
				$line_number = $comment['line'];

				$html .= "
				<details style='margin: 10px 0; margin-left: 10px;'>
					<summary>$summary</summary>
					<div class='doc-body'>
						$body
						<div class='file-info'>
							📍 <strong>Ubicación:</strong> $filename (Línea: $line_number)
						</div>
					</div>
				</details>";

				// Add separator between items (except last one)
				if ($index < count($file_comments) - 1) {
					$html .= "<hr style='margin: 15px 0; border: none; border-top: 1px dashed #ddd;'>";
				}
			}

			$html .= "
			</div>
		</details>";
		}

		$html .= "
	</body>
	</html>";

		$output_file = Yii::getAlias('@app/runtime/doc_output.html');

		if (file_put_contents($output_file, $html)) {
			$this->stdout("✅ Documentación generada en: $output_file\n", Console::FG_GREEN);
			$this->stdout("📊 Total de elementos documentados: " . count($comments) . "\n", Console::FG_BLUE);
			$this->stdout("📁 Archivos procesados: " . count($grouped_comments) . "\n", Console::FG_BLUE);
		} else {
        $this->stderr("❌ Error al generar la documentación en: $output_file\n", Console::FG_RED);
    }

    if ($this->verbose) {
        $this->stdout("\nVista previa del contenido:\n");
        $this->stdout("============================\n");
        foreach ($comments as $comment) {
            $this->stdout("📝 " . $comment['summary'] . "\n", Console::FG_CYAN);
            $this->stdout("   📍 " . $comment['file'] . ":" . $comment['line'] . "\n", Console::FG_YELLOW);
            $this->stdout("   " . substr($comment['body'], 0, 100) . "...\n\n");
        }
    }
}

/**
 * Format the documentation body with basic markup
 */
private function formatDocBody(string $body): string
{
    // Convert line breaks
    $body = nl2br(htmlspecialchars(trim($body)));

    // Format code blocks (between backticks)
    $body = preg_replace_callback('/`([^`]+)`/', function($matches) {
        return '<code>' . htmlspecialchars($matches[1]) . '</code>';
    }, $body);

    // Format multi-line code blocks (between triple backticks)
    $body = preg_replace_callback('/```([^`]+)```/s', function($matches) {
        return '<pre>' . htmlspecialchars(trim($matches[1])) . '</pre>';
    }, $body);

    return $body;
}

}
