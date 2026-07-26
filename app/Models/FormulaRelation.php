<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormulaRelation extends Model
{
    protected $fillable = ['formula_id', 'related_type', 'related_key', 'sort_order'];
}
