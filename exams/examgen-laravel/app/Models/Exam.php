<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $table = 'exam';
    protected $primaryKey = 'exam_ID';
    public $timestamps = false;

    protected $fillable = ['professor_ID','university_ID','subject_ID','creation_date','exam_date','pdf_file_path'];

    public function professor(){ return $this->belongsTo(Professor::class, 'professor_ID', 'prof_ID'); }
    public function university(){ return $this->belongsTo(University::class,'university_ID','university_ID'); }
    public function subject()  { return $this->belongsTo(Subject::class,   'subject_ID',   'subject_ID'); }

    public function questions(){
        return $this->belongsToMany(Question::class, 'exam_question', 'exam_id', 'question_id')
                    ->withPivot(['question_order','marks','created_at']);
    }
}
