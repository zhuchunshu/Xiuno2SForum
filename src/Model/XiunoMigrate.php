<?php

declare (strict_types=1);
namespace App\Plugins\Xiuno\src\Model;

use App\Model\Model;

/**
 * @property int $id 
 * @property string $table 
 * @property string $_id 
 */
class XiunoMigrate extends Model
{
    public $timestamps = false;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'xiuno_migrate';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id','table','_id','sf_id'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer'];
}