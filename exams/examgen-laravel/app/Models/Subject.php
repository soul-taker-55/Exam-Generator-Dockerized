<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $table = 'subject';
    protected $primaryKey = 'subject_ID';
    public $timestamps = false;

    protected $fillable = ['university_ID','professor_ID','subject_name','total_mark','duration'];

    public function professor(){ return $this->belongsTo(Professor::class, 'professor_ID', 'prof_ID'); }
    public function university(){ return $this->belongsTo(University::class, 'university_ID', 'university_ID'); }
    public function questions(){ return $this->hasMany(Question::class, 'subject_ID', 'subject_ID'); }
    public function exams()    { return $this->hasMany(Exam::class, 'subject_ID', 'subject_ID'); }
}
