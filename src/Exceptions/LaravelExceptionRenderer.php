<?php
namespace Ostoandel\Exceptions;

use Illuminate\Support\Facades\Response;

\App::uses('ExceptionRenderer', 'Error');

class LaravelExceptionRenderer extends \ExceptionRenderer
{

    /**
     *
     * {@inheritDoc}
     * @see \ExceptionRenderer::_outputMessage()
     */
    protected function _outputMessage($template)
    {
        $this->controller->render($template);
    }

    /**
     *
     * {@inheritDoc}
     * @see \ExceptionRenderer::render()
     */
    public function render()
    {
        parent::render();
        return Response::fromCake($this->controller->response);
    }
}
