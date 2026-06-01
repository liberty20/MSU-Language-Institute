<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function log($action, $description, $subject = null, $properties = null, $userId = null)
    {
        $userId = $userId ?? (\Auth::check() ? \Auth::id() : null);
        $request = request();

        return self::create([
            'user_id'      => $userId,
            'action'       => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => ($subject && is_numeric($subject->id)) ? $subject->id : null,
            'description'  => $description,
            'properties'   => $properties ? (is_array($properties) ? json_encode($properties) : $properties) : null,
            'ip_address'   => $request ? $request->ip() : null,
            'user_agent'   => $request ? $request->userAgent() : null,
        ]);
    }
}

