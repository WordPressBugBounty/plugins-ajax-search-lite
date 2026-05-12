<?php

namespace WPDRMS\Utils;

use Exception;
use InvalidArgumentException;

/**
 * File manager/wrapper
 */
class FileManager {
	private static ?self $instance = null;

	/**
	 * @var string[]
	 */
	private array $allowed_directories = array();

	public function setAllowedDirectories( array $directories ): void {
		$this->allowed_directories = $directories;
	}

	public function initialized( bool $init = false, string $check_method = '' ): bool {
		global $wp_filesystem;
		if ( $init && empty($wp_filesystem) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
		}
		if ( function_exists('WP_Filesystem') && WP_Filesystem() === true && is_object($wp_filesystem) ) {
			if ( $check_method !== '' ) {
				return method_exists($wp_filesystem, $check_method);
			}
			return true;
		}
		// Did not init
		return false;
	}

	public function addDirectorySeparator( string $path ): string {
		return rtrim($path, '/\\') . DIRECTORY_SEPARATOR;
	}

	/**
	 * @param callable-string $func
	 * @return mixed
	 */
	public function wrapper( string $func ) {
		global $wp_filesystem;
		$args = func_get_args();
		array_shift($args);
		return $this->initialized(false, $func) ?
			call_user_func_array(array( $wp_filesystem, $func ), $args) : // @phpstan-ignore-line
			call_user_func_array($func, $args);
	}

	/**
	 * @param string $file
	 * @return false|int
	 */
	public function mtime( string $file ) {
		global $wp_filesystem;
		// Did it fail?
		if ( $this->initialized(false, 'mtime') ) {
			return $wp_filesystem->mtime($file);
		}
		return filemtime( $file );
	}

	public function isFile( string $path ): bool {
		return $this->wrapper('is_file', $path);
	}

	public function isDir( string $path ): bool {
		return $this->wrapper('is_dir', $path);
	}

	/**
	 * @param string $filename
	 * @return false|string
	 */
	public function read( string $filename ) {
		global $wp_filesystem;
		// Replace double
		$filename = str_replace(array( '\\\\', '//' ), array( '\\', '/' ), $filename);

		if ( !file_exists($filename) ) {
			return '';
		}

		if ( $this->initialized(false, 'get_contents') ) {
			// All went well, return
			return $wp_filesystem->get_contents( $filename );
		}

		return @file_get_contents($filename); //@phpcs:ignore
	}

	public function write( string $filename, string $contents ): bool {
		global $wp_filesystem;
		// Replace double
		$filename = str_replace(array( '\\\\', '//' ), array( '\\', '/' ), $filename);

		if ( $this->inAllowedDirectory($filename) ) {
			// Make sure that the directory exists
			$this->createRequiredDirectories();

			// Did it fail?
			if ( !$this->initialized(false, 'put_contents') ) {
				/* any problems and we exit */
				return !( @file_put_contents($filename, $contents) === false ); //@phpcs:ignore
			}

			// It worked, use it!
			if ( defined('FS_CHMOD_FILE') ) {
				if ( !$wp_filesystem->put_contents($filename, $contents, FS_CHMOD_FILE) ) {
					return !( @file_put_contents($filename, $contents) === false ); //@phpcs:ignore
				}
			} elseif ( !$wp_filesystem->put_contents($filename, $contents) ) {
				return !( @file_put_contents($filename, $contents) === false ); //@phpcs:ignore
			}
		}

		return true;
	}

	/**
	 * @param string $filename
	 * @return bool
	 */
	public function delFile( string $filename ): bool {
		global $wp_filesystem;
		if ( $this->inAllowedDirectory($filename) ) {
			// Did it fail?
			if ( !$this->initialized(false, 'delete') ) {
				/* any problems and we exit */
				return @unlink($filename); //@phpcs:ignore
			}
			return $wp_filesystem->delete($filename);
		} else {
			return false;
		}
	}

	/**
	 * Delete files in allowed directory according to a pattern
	 *
	 * @param string $target_dir
	 * @param string $glob_pattern
	 * @return int files and directories deleted
	 */
	public function deleteFilesByPattern( string $target_dir, string $glob_pattern = '*.*' ): int {
		if ( !$this->inAllowedDirectory($target_dir) || !$this->pathSafetyCheckBeforeDelete($target_dir) ) {
			return 0;
		}

		$count = 0;
		$files = @glob($target_dir . $glob_pattern, GLOB_MARK); // @phpcs:ignore
		// Glob can return FALSE on error
		if ( is_array($files) ) {
			foreach ( $files as $file ) {
				$this->delFile($file);
				++$count;
			}
		}
		return $count;
	}

	public function rmdir( string $dir, bool $recursive = false, bool $force = false ): bool {
		global $wp_filesystem;
		if ( $this->inAllowedDirectory($dir) ) {
			if ( $force ) {
				$this->recursiveRmdir($dir);
				return true;
			}

			// Did it fail?
			if ( !$this->initialized(false, 'rmdir') ) {
				// $recursive is not supported in the default php rmdir function
				return @rmdir($dir); // @phpcs:ignore
			}

			$wp_filesystem->rmdir($dir, $recursive);
		} else {
			return false;
		}

		return false;
	}

	/**
	 * @param string $path
	 * @return void
	 * @throws InvalidArgumentException
	 */
	public function recursiveRmdir( string $path ): void {
		if ( !is_dir($path) ) {
			return;
		}
		if ( !str_ends_with($path, '/') ) {
			$path .= '/';
		}
		$files = glob($path . '*', GLOB_MARK);
		if ( $files === false ) {
			return;
		}
		foreach ( $files as $file ) {
			if ( is_dir($file) ) {
				$this->recursiveRmdir($file);
			} else {
				unlink($file); // @phpcs:ignore
			}
		}
		rmdir($path); // @phpcs:ignore
	}

	/**
	 * Lists directories, excluding WP system, plugin and theme directories
	 *
	 * @param string $path
	 * @param int    $limit
	 * @return string[]
	 * @throws Exception
	 */
	public function safeListDirectories( string $path, int $limit = 5000 ): array {
		$unsafe_directories = apply_filters(
			'wpdrms/utils/filemanager/unsafe_directories',
			array(
				'node_modules',
				'vendor',
				'cache',
				'.git',
				'_phpstorm',
				'themes',
				'wp-includes',
				'wp-admin',
				'plugins',
				'mu-plugins',
				'et-cache',
				'wpml',
				'wflogs',
				'w3tc-config',
				'config',
				'elementor',
				'wpallimport',
				'buddypress',
				'asp_upload',
				'AAPL',
				'sucuri',
			)
		);
		$directories        = $this->listDirectoriesRecursively( $path, $unsafe_directories );

		sort($directories);

		return array_slice(
			array_values(
				array_map(
					function ( $directory ) {
						return str_replace(ABSPATH, '', rtrim($directory, DIRECTORY_SEPARATOR));
					},
					$directories
				)
			),
			0,
			$limit
		);
	}

	public function listFiles( string $path, string $file_arg = '*.*' ): array {
		return glob($path . $file_arg, GLOB_MARK);
	}


	/**
	 * A very fast recursive directory listing method
	 *
	 * Much faster then using DirectoryIterators
	 *
	 * @param string   $path
	 * @param string[] $exclude_dirs
	 * @return string[]
	 */
	public function listDirectoriesRecursively( string $path = '', $exclude_dirs = array() ): array {
		$directories = array();
		$paths       = glob($path . '*', GLOB_MARK | GLOB_ONLYDIR | GLOB_NOSORT);
		foreach ( $paths as $path ) {
			// Skip excluded directories
			$dir_name = basename(rtrim($path, DIRECTORY_SEPARATOR));
			if ( in_array($dir_name, $exclude_dirs, true) ) {
				continue;
			}
			$directories[] = $path;
			$directories   = array_merge($directories, $this->listDirectoriesRecursively($path, $exclude_dirs));
		}
		return $directories;
	}

	/**
	 * @return bool
	 */
	public function createRequiredDirectories(): bool {
		foreach ( $this->allowed_directories as $directory ) {
			if ( !is_dir($directory) ) {
				if ( !wp_mkdir_p($directory) ) {
					@mkdir($directory, 0755, true); // @phpcs:ignore
				}
			}
		}

		return true;
	}

	/**
	 * Creates directory if it does not exist within the allowed safe directories
	 *
	 * @param string $path
	 * @return bool
	 */
	public function safeCreateDirectory( string $path ): bool {

		if ( !$this->inAllowedDirectory($path) ) {
			return false;
		}

		if ( !is_dir($path) ) {
			if ( !wp_mkdir_p($path) ) {
				@mkdir($path, 0755, true); // @phpcs:ignore
			}
			if ( !is_dir($path) ) {
				return false;
			}
		} else {
			return true;
		}

		return false;
	}

	/**
	 * @return void
	 */
	public function removeRequiredDirectories(): void {
		foreach ( $this->allowed_directories as $directory ) {
			if ( $this->pathSafetyCheckBeforeDelete($directory) && $this->isDir($directory) ) {
				$this->rmdir( $directory  );
				if ( $this->isDir( $directory ) ) { // @phpstan-ignore-line
					$this->rmdir( $directory, true);
					if ( $this->isDir( $directory ) ) { // @phpstan-ignore-line
						// Last attempt, with force
						$this->rmdir( $directory, true, true);
					}
				}
			}
		}
	}

	private function pathSafetyCheckBeforeDelete(string $path ): bool {
		if ( defined('WPD_UNIT_TEST') ) {
			return true;
		}
		if ( !function_exists('get_home_path') ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
		}
		if (
			$path !== '' &&
			$path !== '/' &&
			$path !== './' &&
			str_replace('/', '', get_home_path()) !== str_replace('/', '', $path) &&
			strpos($path, 'wp-content') > 5 &&
			strpos($path, 'plugins') === false &&
			strpos($path, 'wp-includes') === false &&
			strpos($path, 'wp-admin') === false &&
			is_dir( $path )
		) {
			return true;
		}

		return false;
	}

	/**
	 * @param string $path
	 * @return bool
	 */
	private function inAllowedDirectory( string $path ): bool {
		if ( defined('WPD_UNIT_TEST') ) {
			return true;
		}
		foreach ( $this->allowed_directories as $directory ) {
			if ( strpos($path, $directory) !== false ) {
				return true;
			}
		}
		return false;
	}

	public static function instance(): self {
		if ( ! ( self::$instance instanceof self ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
