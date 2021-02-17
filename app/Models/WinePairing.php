<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WinePairing extends Model
{    
   protected $table = 'wine_pairing';
   protected $fillable = [
        'id', 
        'wine', 
        'wine_description', 
        'cheese', 
        'cheese_description', 
        'pairing_notes'
   ];
   protected $hidden = [];
}
