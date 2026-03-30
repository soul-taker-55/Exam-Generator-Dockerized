<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class University extends Model
{
    protected $table = 'university';
    protected $primaryKey = 'university_ID';
    public $timestamps = false;

    protected $fillable = ['university_name_en','university_name_ar'];

    public function subjects(){ return $this->hasMany(Subject::class, 'university_ID', 'university_ID'); }
    public function exams()   { return $this->hasMany(Exam::class, 'university_ID', 'university_ID'); }

    public function professors(){
        return $this->belongsToMany(Professor::class, 'professor_university', 'university_ID','professor_ID')
                    ->withPivot('prof_uni_ID');
    }
}
