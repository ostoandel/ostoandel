<?php
namespace Ostoandel\View;

class Factory extends \Illuminate\View\Factory
{
    public function make($view, $data = [], $mergeData = [])
    {
        $data = array_merge($mergeData, $this->parseData($data));

        $viewInstance = $data['__viewInstance'] ?? null;
        if ($viewInstance instanceof BladeView) {
            $path = $viewInstance->dispatchMethod('_getViewFileName', [ $view ]);

            $newInstance = clone $viewInstance;
            $newInstance->view = $view;
            $newInstance->__viewFile = $path;
            $newInstance->viewVars = $data;
            $newInstance->layout = false;

            return $newInstance;
        }

        return parent::make($view, $data);
    }

    public function getSectionStack()
    {
        return $this->sectionStack;
    }
}
