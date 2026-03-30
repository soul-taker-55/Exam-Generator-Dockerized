<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ProfessorUniversity extends Model
{
    protected $table = 'professor_university';
    protected $primaryKey = 'prof_uni_ID';
    public $timestamps = false;

    protected $fillable = ['professor_ID','university_ID'];

    public function professor(){ return $this->belongsTo(Professor::class, 'professor_ID', 'prof_ID'); }
    public function university(){ return $this->belongsTo(University::class, 'university_ID', 'university_ID'); }
}
