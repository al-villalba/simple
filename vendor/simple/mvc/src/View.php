<?php

namespace Simple;

/**
 * Description of View
 */
class View
{
	const DEFAULT_THEME = 'default';

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
	public function renderFile($template, $locals = null)
	{
		if( !isset($this->_templatePaths[$template]) ||
			!is_readable($this->_templatePaths[$template])
		) {
			throw new \Exception("Template '$template' is not readable");
		}
		if( empty($this->_locals) && !empty($locals) ) {
			$this->_locals = $locals;
		}

		ob_start();
		ob_implicit_flush(false);
		extract($this->_locals, EXTR_OVERWRITE);
		try {
			$countStack = count($this->_renderStack);
			$this->_renderStack[] = $template;

			require $this->_templatePaths[$template];
			$output = ob_get_clean();
			array_pop($this->_renderStack);

			if( $countStack < count($this->_renderStack) ) {
				$template = array_pop($this->_renderStack);
				$output = $this->renderFile($template, $this->_locals);
			}

			return $output;

		} catch (\Exception $e) {
			// TODO error handling
			throw $e;
		} catch (\Throwable $e) {
			// TODO error handling
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
	public function requireTemplate($template)
	{
		echo $this->renderFile($template, $this->_locals);
	}

	/**
	 * Start a block (output buffer)
	 */
	public function startBlock()
	{
		ob_start();
	}

	/**
	 * Store current buffer contents into the stack and turn off ob
	 * @param string $name
	 */
	public function endBlock($name)
	{
		if( isset($this->_blockStack[$name]) ) {
			ob_end_clean();
			throw new \Exception("Block '$name' already defined");
		}
		$this->_blockStack[$name] = ob_get_clean();
	}

	/**
	 * Echo a block of the stack
	 * 
	 * @param string $name
	 * @throws Exception
	 */
	public function block($name)
	{
		if( !isset($this->_blockStack[$name]) ) {
			throw new \Exception("Block '$name' is undefined");
		}
		echo $this->_blockStack[$name];
	}

}
