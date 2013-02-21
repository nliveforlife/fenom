<?php
namespace Aspect;

use Aspect\ProviderInterface;
/**
 * Templates provider
 * @author Ivan Shalganov
 */
class FSProvider implements ProviderInterface {
    private $_path;

    /**
     * Clean directory from files
     *
     * @param string $path
     */
    public static function clean($path) {
        if(is_file($path)) {
            unlink($path);
        } elseif(is_dir($path)) {
            $iterator = iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path,
                    \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST));
            foreach($iterator as $file) {
                /* @var \splFileInfo $file*/
                if($file->isFile()) {
                    if(strpos($file->getBasename(), ",") !== 0) {
                        unlink($file->getRealPath());
                    }
                } elseif($file->isDir()) {
                    rmdir($file->getRealPath());
                }
            }
        }
    }

    /**
     * Recursive remove directory
     *
     * @param string $path
     */
    public static function rm($path) {
        self::clean($path);
        if(is_dir($path)) {
            rmdir($path);
        }
    }

    public static function put($path, $content) {
        file_put_contents($path, $content);
    }

    public function __construct($template_dir) {
        if($_dir = realpath($template_dir)) {
            $this->_path = $_dir;
        } else {
            throw new \LogicException("Template directory {$template_dir} doesn't exists");
        }
    }

    /**
     *
     * @param string $tpl
     * @param int $time
     * @return string
     */
    public function getSource($tpl, &$time) {
        $tpl = $this->_getTemplatePath($tpl);
        clearstatcache(null, $tpl);
        $time = filemtime($tpl);
        return file_get_contents($tpl);
    }

    public function getLastModified($tpl) {
        clearstatcache(null, $tpl = $this->_getTemplatePath($tpl));
        return filemtime($tpl);
    }

    public function getList() {

    }

    /**
     * Get template path
     * @param $tpl
     * @return string
     * @throws \RuntimeException
     */
    protected function _getTemplatePath($tpl) {
        if(($path = realpath($this->_path."/".$tpl)) && strpos($path, $this->_path) === 0) {
            return $path;
        } else {
            throw new \RuntimeException("Template $tpl not found");
        }
    }

	/**
	 * @param string $tpl
	 * @return bool
	 */
	public function isTemplateExists($tpl) {
        return file_exists($this->_path."/".$tpl);
	}

    public function getLastModifiedBatch($tpls) {
        $tpls = array_flip($tpls);
        foreach($tpls as $tpl => &$time) {
            $time = $this->getLastModified($tpl);
        }
        return $tpls;
    }

    /**
     * Verify templates by change time
     *
     * @param array $templates [template_name => modified, ...] By conversation you may trust the template's name
     * @return bool
     */
    public function verify(array $templates) {
        foreach($templates as $template => $mtime) {
            clearstatcache(null, $template = $this->_path.'/'.$template);
            if(@filemtime($template) !== $mtime) {
                return false;
            }

        }
        return true;
    }
}