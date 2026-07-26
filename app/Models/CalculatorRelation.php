<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalculatorRelation extends Model
{
    protected $fillable = ['calculator_content_id', 'related_type', 'related_key'];
}
