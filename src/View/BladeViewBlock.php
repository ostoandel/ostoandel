<?php
namespace Ostoandel\View;

use Illuminate\Support\Arr;

class BladeViewBlock extends \ViewBlock
{

    protected $factory;

    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    /**
     *
     * {@inheritDoc}
     * @see \ViewBlock::start()
     */
    public function start($name)
    {
        return $this->factory->startSection($name);
    }

    public function startIfEmpty($name)
    {
        throw new \BadMethodCallException(__METHOD__);
    }

    /**
     *
     * {@inheritDoc}
     * @see \ViewBlock::concat()
     */
    public function concat($name, $value = null, $mode = self::APPEND)
    {
        if ($value !== null) {
            $content = $this->get($name);
            if ($mode === self::PREPEND) {
                $content = $value . $content;
            } else {
                $content .= $value;
            }
            $this->set($name, $value);
        } else {
            $this->start($name);
        }
    }

    /**
     *
     * {@inheritDoc}
     * @see \ViewBlock::end()
     */
    public function end()
    {
        return $this->factory->appendSection();
    }

    /**
     *
     * {@inheritDoc}
     * @see \ViewBlock::set()
     */
    public function set($name, $value)
    {
        $this->factory->startSection($name);
        echo $value;
        $this->factory->stopSection(true);
    }

    /**
     *
     * {@inheritDoc}
     * @see \ViewBlock::get()
     */
    public function get($name, $default = '')
    {
        return $this->factory->yieldContent($name, $default);
    }

    /**
     *
     * {@inheritDoc}
     * @see \ViewBlock::exists()
     */
    public function exists($name)
    {
        return $this->factory->hasSection($name);
    }

    /**
     *
     * {@inheritDoc}
     * @see \ViewBlock::keys()
     */
    public function keys()
    {
        return array_keys( $this->factory->getSections() );
    }

    public function active()
    {
        return Arr::last( $this->factory->getSectionStack() );
    }

    public function unclosed()
    {
        return $this->factory->getSectionStack();
    }

}
