<?php


namespace App\Traits;

use App\Models\Logs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;


trait Loggable
{
    public static function bootLoggable() {
        static::saved(function (Model $model) {
            // create or update?
            if( $model->wasRecentlyCreated ) {
                static::logChange( $model, 'CREATED' );
            } else {
                if( !$model->getChanges() ) {
                    return;
                }
                static::logChange( $model, 'UPDATED' );
            }
        });

        static::deleted(function (Model $model) {
            static::logChange( $model, 'DELETED' );
        });
    }

    public static function logChange( Model $model, string $action ) {
        Logs::create([
            'user_id' => Auth::check() ? Auth::user()->id : null,
            'model'   => static::class,
            'action'  => $action,
            'message' => static::logSubject($model),
            'models'  => json_encode([
                'new'     => $action !== 'DELETED' ? $model->getAttributes() : null,
                'old'     => $action !== 'CREATED' ? $model->getOriginal()   : null,
                'changed' => $action === 'UPDATED' ? $model->getChanges()    : null,
            ])
        ]);
    }
    /**
     * String to describe the model being updated / deleted / created
     * Override this in the model class
     * @return string
     */
    public static function logSubject(Model $model)
    {
        return static::logImplodeAssoc($model->attributesToArray());
    }

    public static function logImplodeAssoc(array $attrs) {
        $str = '';

        foreach( $attrs as $key => $value ) {
            $str .= "{ $key => $value } ";
        }
        return $str;
    }
}
