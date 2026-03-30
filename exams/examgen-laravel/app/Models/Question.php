<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $table = 'question';
    protected $primaryKey = 'question_ID';
    public $timestamps = false;

    protected $fillable = [
        'professor_ID','subject_ID','question_text','img_path','difficulty',
        'ans_A','is_correct_A','ans_B','is_correct_B','ans_C','is_correct_C',
        'ans_D','is_correct_D','ans_E','is_correct_E',
        'group_num','is_sub','date','mark'
    ];

    public function professor(){ return $this->belongsTo(Professor::class, 'professor_ID', 'prof_ID'); }
    public function subject()  { return $this->belongsTo(Subject::class,   'subject_ID',   'subject_ID'); }

    public function exams(){
        return $this->belongsToMany(Exam::class, 'exam_question', 'question_id', 'exam_id')
                    ->withPivot(['question_order','marks','created_at']);
    }
}
