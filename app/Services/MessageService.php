<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class MessageService
{


    public static function abort($status, $message, $replace = [])
    {
        abort(
            response()->json(
                [
                    'success' => false,
                    'message' => trans($message, $replace),
                    'key' => $message,
                ],
                $status
            )
        );
    }


    


}
