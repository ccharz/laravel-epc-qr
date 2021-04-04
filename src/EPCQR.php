<?php

namespace Ccharz\LaravelEpcQr;

use Illuminate\Support\Facades\Facade;

class EPCQR extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'epcqr';
    }
}
