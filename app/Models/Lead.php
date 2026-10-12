<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    /**
     * Sin $fillable, `Lead::create($request->all())` lanzaba MassAssignmentException.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'message',
    ];
}
