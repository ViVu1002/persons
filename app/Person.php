<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
class Person extends Model
{
    const FINISH = '1';
    const UN_FINISH = '2';
    const FINISH_UN = '0';
    const FINISH_3 = '3';
    protected $table = "persons";
    protected $fillable = [
        'name', 'email', 'address', 'gender', 'phone', 'faculty_id', 'date', 'image','slug','user_id'
    ];

    protected $primaryKey = 'id';

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'points')->withPivot('person_id', 'subject_id', 'point');
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class, 'faculty_id');
    }

    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = bcrypt($password);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function user(){
        return $this->belongsTo(User::class,'user_id');
    }
}
