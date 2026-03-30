<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class Professor extends Authenticatable
{
    use Notifiable;

    protected $table = 'professor';
    protected $primaryKey = 'prof_ID';
    public $timestamps = false;

    protected $fillable = [
        'username','password','first_name','last_name','email',
        'registration_date','phone_number','verification_token',
        'is_verified','tokens','remember_token'
    ];

    protected $hidden = ['password','remember_token'];

    protected $casts = [
        'registration_date' => 'date',
        'is_verified' => 'boolean',
        'tokens' => 'integer',
    ];

    public function setPasswordAttribute($value) {
        $this->attributes['password'] = Hash::needsRehash($value) ? Hash::make($value) : $value;
    }

    public function subjects() { return $this->hasMany(Subject::class, 'professor_ID', 'prof_ID'); }
    public function questions(){ return $this->hasMany(Question::class, 'professor_ID', 'prof_ID'); }
    public function exams()    { return $this->hasMany(Exam::class, 'professor_ID', 'prof_ID'); }
    public function tokenTransactions(){ return $this->hasMany(TokenTransaction::class, 'professor_id', 'prof_ID'); }

    public function universities(){
        return $this->belongsToMany(University::class, 'professor_university', 'professor_ID', 'university_ID')
                    ->withPivot('prof_uni_ID');
    }
}
