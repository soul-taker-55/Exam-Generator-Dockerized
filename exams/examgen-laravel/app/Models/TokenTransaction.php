<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TokenTransaction extends Model
{
    protected $table = 'token_transactions';
    protected $primaryKey = 'transaction_id';
    public $timestamps = false;

    protected $fillable = [
        'professor_id','tokens_purchased','amount_paid',
        'payment_method','transaction_date','status','payment_reference'
    ];

    public function professor(){ return $this->belongsTo(Professor::class, 'professor_id', 'prof_ID'); }
}
