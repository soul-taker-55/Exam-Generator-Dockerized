<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ExamQuestion extends Model
{
    protected $table = 'exam_question';
    protected $primaryKey = 'exam_question_id';
    public $timestamps = false;

    protected $fillable = ['exam_id','question_id','question_order','marks','created_at'];

    public function exam(){ return $this->belongsTo(Exam::class, 'exam_id', 'exam_ID'); }
    public function question(){ return $this->belongsTo(Question::class, 'question_id', 'question_ID'); }
}
