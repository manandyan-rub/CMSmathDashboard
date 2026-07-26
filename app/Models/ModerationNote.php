<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModerationNote extends Model
{
    protected $fillable = ['document_id', 'administrator_id', 'status', 'note'];

    public function administrator()
    {
        return $this->belongsTo(User::class, 'administrator_id');
    }
}
