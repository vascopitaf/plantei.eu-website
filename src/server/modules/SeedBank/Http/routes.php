<?php

use Illuminate\Http\Request;


Route::group(['prefix' => 'seedbank', 'namespace' => 'Modules\SeedBank\Http\Controllers'], function()
{
	Route::group(['middleware' => 'auth'], function(){
		Route::get('/', 'SeedBankController@index');
		Route::get('/myseeds', 'SeedBankController@mySeeds');
		Route::get('/messages', 'SeedBankController@getMessages');
		Route::get('/register/{id?}', 'SeedBankController@getRegister');
		Route::post('/register/{id?}', 'SeedBankController@postRegister');
		Route::get('/search', 'SeedBankController@getSearch');
		Route::post('/search', 'SeedBankController@postSearch');
		Route::post('/autocomplete', 'SeedBankController@postAutocomplete');
		Route::get('/preferences', 'SeedBankController@getPreferences');
		Route::post('/preferences', 'SeedBankController@postPreferences');
		Route::post('/add-pictures', 'SeedBankController@postAddPicture');
		//Route::post('/seed/{id}', 'SeedBankController@postSeed');
		Route::get('/seed/{id}', function ($id) {
			$user = \Auth::user();
			$seed = \Caravel\Seed::findOrFail($id);
			if (($seed->public) && ($seed->user_id != $user->id))
			{
				$seed->variety;
				$seed->species;
				$seed->family;
				$seed->cookings();
				$seed->medicines();

				return $seed;
			}
			return [];
		});
		Route::get('/seedm/{id}', function ($id) {
			$user = \Auth::user();
			$seed = \Caravel\Seed::findOrFail($id);
			$update_seed = ($seed->user_id == $user->id);
			if (($seed->public) || ($update_seed))
			{
				$seed->variety;
				$seed->species;
				$seed->family;
				$seed->cookings();
				$seed->medicines();
				if ($seed->pictures->count()){
					$picture = $seed->pictures->first();
				} else {
					$picture = false;
				}

				return view('seedbank::seed_modal')
					->with('seed', $seed)
					->with('picture', $picture)
					->with('update_seed', $update_seed);
			}
			return [];
		});
		Route::get('/message/get/{id}', function ($id) {
			//$message = \Caravel\Message::findOrFail($id);
			$user = \Auth::user();
			//$message = \Caravel\Message::findOrFail($id);
			$message = $user->messageById($id);
			if (!$message->user_id == $user->id)
			{
				$message->pivot->read = true;
				$message->pivot->save();
			}
			return $message;
		});
		Route::post('/message/reply', 'SeedBankController@postMessageReply');
		Route::post('/message/send', 'SeedBankController@postMessageSend');
		Route::get('/exchanges', 'SeedBankController@getExchanges');
		Route::get('/user_seeds/{id}', function ($id) {
			$user = \Auth::user();
			$seed_owner = \Caravel\User::findOrFail($id);
			$seeds = $seed_owner->seeds()
				->where('public', true)->where('available', true)
				->leftJoin('seeds_exchanges', function($join) use ($user)
				{
					$join->on('seeds.id', '=', 'seed_id')
						->where('asked_by', '=', $user->id)
						->where('completed', '<', 2)
						->where('accepted', '<>', 1);
				})
				->select('seeds_exchanges.*', 'seeds.id as id', 'seeds.common_name')->get();
			return [
				"seeds" => $seeds,
				"user" => $seed_owner
			];

		});
		Route::post('/startexchange', function (Request $request) {
			$user = \Auth::user();
			$seed_ids = $request->input('seed_ids');
			$owner_id = $request->input('user_id');

			if ((! $seed_ids) || (! $owner_id)) { return []; }
				$data = [
					'asked_to' => $owner_id,
					'seed_ids' => $seed_ids
				];
			return $user->startTransaction($data)
				->join('seeds', 'seed_id', '=', 'seeds.id')
				->select('seeds_exchanges.*', 'seeds.common_name')->get();
		});
		Route::get('/seeds', function () {
			$user = \Auth::user();
			return $user->seeds()->paginate(5);
		});
		Route::get('/pictures/delete/{id}', function ($id) {
			$user = \Auth::user();
			$picture = \Caravel\Picture::findOrFail($id);
			if ($picture->seed){
				if (!$picture->seed->user_id == $user->id) { return [ "files" => [[ $picture->md5sum => false]] ]; }
			}
			$deleted = \File::delete($picture->path);
			// TODO: Might not be able to delete for some reason (permissions?)
			$picture->delete();
			return [ "files" => [[ $picture->md5sum => true]] ];
		});
		Route::get('/pictures/{md5sum}', function ($md5sum) {
			// TODO: Pass this work to nginx?
			$user = \Auth::user();
			$picture = \Caravel\Picture::where('md5sum', $md5sum)->firstOrFail();
			$file = \File::get($picture->path);
			$response = \Response::make($file,200);
			$response->header('Content-Type', 'image/jpg');
			return $response;
		});
		Route::get('/exchange/{action}/{id}', function ($action, $id) {
			$user = \Auth::user();
			if (in_array($action, ['accept', 'reject', 'complete'])) {
				if (!method_exists($user,$action.'Transaction')){
					return 'false';
				}
				$output = call_user_func_array(array($user, $action.'Transaction'),array($id));
				return "ok";
			}
			//return true || error;
			return (null);
		});
	});
});
Route::group(['prefix' => 'events', 'namespace' => 'Modules\SeedBank\Http\Controllers'], function()
{
	Route::group(['middleware' => 'auth'], function(){
		Route::get('/', 'SeedBankController@getEvents');
		Route::post('/', 'SeedBankController@postEvents');
		Route::get('/form/{id}', function ($id) {
			if ( $id ==  'new' ) {
            	return view('seedbank::event-modal')
					->with('event', [ 'description' => 'CHANGEME eventDescripitionLabel' ])
        			->with('messages', \Lang::get('seedbank::messages'));
			}
			$event = \Caravel\Calendar::find($id);
			if ( ! $id ){
				$event = false;
			}
            return view('seedbank::event-modal')
				->with('event', $event)
        		->with('messages', \Lang::get('seedbank::messages'));
			
		});

		Route::get('/del/{id}', function ($id) {
			//TODO: Check that user can add events (belongs to admin group)
			\Caravel\Calendar::destroy($id);
			//return redirect('/events');
			return '';
		});
	
		Route::post('/add', 'SeedBankController@postAddEvent');
		Route::post('/calendar', function (Request $request) {
			$events = \Caravel\Calendar::interval($request)->get();
			return $events;
		});
	});
});
Route::group(['prefix' => 'sementecas', 'namespace' => 'Modules\SeedBank\Http\Controllers'], function()
{
	Route::group(['middleware' => 'auth'], function(){
		Route::get('/', function () {
			return \Caravel\Sementeca::paginate(5);
		});
		Route::get('/{id}', function ($id) {
			if ( ! $id )
			{
				return \Caravel\Sementeca::paginate(5);
			}
			$sementeca = \Caravel\Sementeca::findOrFail($id);
			return $sementeca;
		});
	});
});

Route::group(['prefix' => 'api', 'namespace' => 'Modules\SeedBank\Http\Controllers'], function()
{
	Route::group(['middleware' => 'auth'], function(){
    Route::get('/location', function (Request $request) {
			return array_keys(config('concelhos_portugal'));
      $response = [];
      foreach(array_keys(config('concelhos_portugal')) as $key) {
        if (strpos($key, $request->input('term')) !== false) {
              $response[] = $key;
        }
      };
      return $response;
		});
		Route::get('/location/{concelho}', function ($concelho) {
			return config('concelhos_portugal.' . $concelho);
		});
	});
});
