<?php

namespace Simple;

/**
 * Description of View
 * 
 * @author Alvaro <alvaro.simplemvc@gmail.com>
 */
class View
{
	const DEFAULT_THEME = 'default';
	const BLOCK_PREPEND = 0;
	const BLOCK_APPEND  = 1;

	/**
	 * @var string
	 */
	protected $_theme;

	/**
	 * Base path of the templates
	 * @var string
	 */
	protected $_basePath;

	/**
	 * Paths of all templates
	 * @var array
	 */
	protected $_templatePaths;

	/**
	 * Local variables passed to the templates
	 * @var array
	 */
	protected $_locals;

	/**
	 * Stack of rendering templates
	 * @var array
	 */
	protected $_renderStack;

	/**
	 * Stack of blocks
	 * @var array
	 */
	protected $_blockStack;

	/**
	 * Init object attributes
	 */
	public function __construct($theme, $controllerClass)
	{
		if( empty($theme) ) {
			$theme = self::DEFAULT_THEME;
		}
		$this->_theme = $theme;

		$reflector = new \ReflectionClass($controllerClass);
		$this->_basePath = dirname($reflector->getFileName()) . '/../views';
		$this->_setTemplatePaths();
		$this->_locals = [];
		$this->_renderStack = [];
		$this->_blockStack = [];
	}

	/**
	 * Set the paths of the templates
	 * 
	 * @return void
	 */
	protected function _setTemplatePaths()
	{
		$paths = [];

		// set with default theme
		$this->_setTemplatePathsByTheme(self::DEFAULT_THEME, $paths);

		// overwrite default with custom theme
		$this->_setTemplatePathsByTheme($this->_theme, $paths);

		$this->_templatePaths = $paths;
	}

	/**
	 * Set the paths of the templates of a specific theme
	 * 
	 * @param string $theme
	 * @param array $paths
	 */
	protected function _setTemplatePathsByTheme($theme, &$paths)
	{
		$themeDir = "{$this->_basePath}/$theme/";
		if( !is_dir($themeDir) ) {
			return;
		}

		$directoryIterator = new \RecursiveCallbackFilterIterator(
			new \RecursiveDirectoryIterator($themeDir, 
				\FilesystemIterator::FOLLOW_SYMLINKS),
			function ($current) {
				if ($current->getFilename()[0] === '.') {
					return false;
				}
				return true;
			});
		$iterator = new \RecursiveIteratorIterator($directoryIterator);

		$index = strlen($themeDir);
		foreach ($iterator as $fileInfo) {
			$relPath = substr($fileInfo->getPathname(), $index);
			if( true ) {
				$paths[$relPath] = $fileInfo->getPathname();
			}
		}
	}

	/**
	 * Renders a file with local variables
	 * 
	 * @param string $template
	 * @param array $locals
	 * @return string
	 * @throws \Exception
	 */
	public function renderFile($template, $locals = null, $mergeLocals = true)
	{
		if( !isset($this->_templatePaths[$template]) ||
			!is_readable($this->_templatePaths[$template])
		) {
			throw new \Exception("Template '$template' is not readable");
		}
		$locals = array_merge($this->_locals, (array)$locals);
		if( $mergeLocals ) {
			$this->_locals = $locals;
		}

		ob_start();
		ob_implicit_flush(false);
		extract($locals, EXTR_OVERWRITE);
		try {
			$countStack = count($this->_renderStack);
			$this->_renderStack[] = $template;

			require $this->_templatePaths[$template];
			$output = ob_get_clean();
			array_pop($this->_renderStack);

			if( $countStack < count($this->_renderStack) ) {
				$template = array_pop($this->_renderStack);
				$output = $this->renderFile($template, $locals, false);
			}

			return $output;

		} catch (\Exception $e) {
			// @todo error handling
			throw $e;
		} catch (\Throwable $e) {
			// @todo error handling
			throw $e;
		}
	}

	/**
	 * Add extension to the stack
	 * 
	 * @param string $template
	 */
	public function extendTemplate($template)
	{
		$last = array_pop($this->_renderStack);
		$this->_renderStack[] = $template;
		$this->_renderStack[] = $last;
	}

	/**
	 * Require a template.
	 * (To be called within a template)
	 * 
	 * @param string $template
	 * @return string
	 */
	public function requireTemplate($template, $locals = null)
	{
		echo $this->renderFile($template, $locals, false);
	}

	/**
	 * Include a widget.
	 * (To be called within a template)
	 * 
	 * @param string $widget
	 * @return string
	 */
	public function includeWidget($widget)
	{
		$className = "\\Poch\\Controller\\Widget\\$widget";
		$widget = new $className($this);
		// @todo
		$widget->render();
//		echo $this->renderFile($template, $this->_locals);
	}

	/**
	 * Start a block (output buffer)
	 */
	public function startBlock()
	{
		ob_start();
	}

	/**
	 * Store current buffer contents into the stack and turn ob off
	 * @param string $name
	 */
	public function endBlock($name, $position = self::BLOCK_APPEND, $overwrite = false)
	{
		$block = ob_get_clean();

		if( $overwrite ) {
			$this->_blockStack[$name] = [];
		}

		if( $position == self::BLOCK_PREPEND ) {
			array_unshift($this->_blockStack[$name], trim($block));
		} else {
			$this->_blockStack[$name][] = trim($block);
		}
	}

	/**
	 * Echo a block of the stack
	 * 
	 * @param string $name
	 * @throws Exception
	 */
	public function block($name)
	{
		if( isset($this->_blockStack[$name]) ) {
			if( is_array($this->_blockStack[$name]) ) {
				echo implode("\n", $this->_blockStack[$name]) . "\n";
			} else {
				echo $this->_blockStack[$name] . "\n";
			}
		}
	}

}
