<?php
namespace Ostoandel\Traits;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Response;

trait LaravelResponse
{

    /**
     * @see \Controller::redirect()
     */
    public function redirect($url, $status = null, $exit = true)
    {
        $response = parent::redirect($url, $status, false) ?? $this->response;

        if ($exit) {
            throw new HttpResponseException(Response::fromCake($response));
        }

        return $response;
    }

}
