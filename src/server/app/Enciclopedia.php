<?php

namespace Caravel;

use Illuminate\Database\Eloquent\Model;

class Enciclopedia extends Model
{
  protected $table = 'enciclopedias';
  protected $fillable = [
    'user_id', 'description', 'common_name',
    'family_id'
  ];

  public function pictures()
  {
    return $this->morphMany('Caravel\Picture', 'imageable');
  }
  public function uses()
  {
    return $this->morphMany('Caravel\PlantUsage', 'plantuseable');
  }
  public function popnames()
  {
    return $this->morphMany('Caravel\Popname', 'popnameable');
  }
  public function references()
  {
    return $this->morphMany('Caravel\Reference', 'referenceable');
  }

}

class Popname extends Model
{
  protected $table = 'popnames';
  protected $fillable = [ 'pop_name' ];
  public $timestamps = false;

  public function popnameable()
  {
    return $this->morphTo();
  }
}

class Reference extends Model
{
  protected $table = 'references';
  protected $fillable = [ 'content', 'type' ];
  public $timestamps = false;


  public function referenceable()
  {
    return $this->morphTo();
  }
}

class PlantUsage extends Model
{
  protected $table = 'plantuses';
  protected $fillable = [ 'title', 'article', 'category_id' ];
  public $timestamps = false;

  public function plantuseable()
  {
    return $this->morphTo();
  }
}
