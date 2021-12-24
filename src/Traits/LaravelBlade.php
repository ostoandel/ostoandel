<?php
namespace Ostoandel\Traits;

use Illuminate\Container\Container;
use Ostoandel\View\BladeViewBlock;

trait LaravelBlade
{
    /**
     * @var \Illuminate\View\Compilers\BladeCompiler
     */
    protected $compiler;

    /**
     * @var \Ostoandel\View\Factory
     */
    protected $factory;

    protected $lastCompiled = [];

    public function __construct($controller = null)
    {
        parent::__construct($controller);

        $factory = Container::getInstance()->make(\Illuminate\View\Factory::class);
        $factory->share('__viewInstance', $this);

        $this->factory = $factory;
        $this->compiler = $factory->getEngineFromPath('.blade.php')->getCompiler();
        $this->Blocks = new BladeViewBlock($factory);
    }

    /**
     *
     * {@inheritDoc}
     * @see \View::_evaluate()
     */
    public function _evaluate($path, $data)
    {
        $this->__viewFile = $path;

        $this->factory->incrementRender();
        $this->factory->callComposer($this);

        $data = array_merge($this->factory->getShared(), (array)$data);

        $this->lastCompiled[] = $path;

        if ($this->compiler->isExpired($path)) {
            $this->compiler->compile($path);
        }

        $compiled = $this->compiler->getCompiledPath($path);

        $results = $this->evaluatePath($compiled, $data);

        array_pop($this->lastCompiled);

        $this->factory->decrementRender();
        return $results;
    }

    protected function evaluatePath($__path, $__data)
    {
        $obLevel = ob_get_level();

        ob_start();

        extract($__data, EXTR_SKIP);

        try {
            include $__path;
        } catch (\Throwable $e) {
            while (ob_get_level() > $obLevel) {
                ob_end_clean();
            }
            throw $e;
        }

        return ltrim(ob_get_clean());
    }

    public function with($key, $value = null)
    {
        if (is_array($key)) {
            $this->viewVars = array_merge($this->viewVars, $key);
        } else {
            $this->viewVars[$key] = $value;
        }

        return $this;
    }

    public function getName()
    {
        $path = $this->__viewFile;
        if (strpos($path, ROOT) === 0) {
            $path = substr($path, strlen(ROOT));
        }
        return str_replace(DS, '/', $path);
    }

    public function getPath()
    {
        return null;
    }

    public function name()
    {
        return $this->getName();
    }

    public function getData()
    {
        return $this->viewVars;
    }
}

