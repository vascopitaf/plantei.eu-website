<?php namespace Modules\Seedbank\Http\Controllers;

use Pingpong\Modules\Routing\Controller;
use Illuminate\Http\Request;

class SeedBankController extends Controller {
	
	public function index()
	{
		// View for my seeds
		$user = \Auth::user();
		//dd($user->id);
		//$user = \Caravel\User::where('id', 1)->first();
		$seeds = $user->getseeds();
		$t = [];
		foreach($seeds as $seed){

			foreach(['origin', 'polinization', 'direct', 'public', 'available'] as $key){
				if(isset($seed[$key])){
					$seed[$key] = [$seed[$key] => true];
				}
			}
			$t[] = $seed;
		}
		//dd($t);
		return view('seedbank::index')
			->with('seeds', $t)
			->with('messages', \Lang::get('seedbank::messages'))
			->with('menu', \Lang::get('seedbank::menu'))
			->with('active', ['myseeds' => true]);
	}
	public function getRegister($id = null)
	{
		$update = false;
		//$errors = \Session::get('errors');
		if(\Session::hasOldInput()){
			$info =  \Session::getOldInput();
			if(!empty($errors)){
				\View::share('errors', $errors->default->toArray());
			}
		} 
		if ($id){
			if (! isset($info)) {
				$info = (array)\DB::table('seeds')
					->join('seeds_bank', 'seeds_bank.seed_id', '=', 'seeds.id')
					->where('seeds_bank.id', $id)->first();
				$info['months'] = (array)\DB::table('seed_months')->where('seed_id', $info['seed_id'])->lists('month');
			}
			$info['id'] = $id;
			$update = true;
		}
		//$t = [];
			foreach(['origin', 'polinization', 'direct', 'public', 'available'] as $key){
				if(isset($info[$key])){
					$info[$key] = [$info[$key] => true];
				}
			}
			//$t[] = $info;
		/*foreach(['origin', 'polinization', 'direct'] as $key){
			if(isset($info[$key])){
				$info[$key] = [$info[$key] => true];
			}
		}
		foreach(['public', 'available'] as $key){
			if(isset($key)){
				$info[$key] = true;
			} else {
				$info[$key] = false;
			}
		}*/

		if(isset($info['months'])){
			$o = array();
			foreach($info['months'] as $i){
				$o[$i] = true;
			}
			$info['months'] = $o;
		}
		if (! isset($info)){
			$info = [];
		}
		return view('seedbank::registerseed', ['update' => $update])
			->with('messages', \Lang::get('seedbank::messages'))
			->with('menu', \Lang::get('seedbank::menu'))
			->with('oldInput', $info); 
	}
	public function postRegister(Request $request)
	{
		// if error with form
		$this->validate($request, [
			'common_name' => 'required|max:10',
			'origin' => 'required',
		]);
		$seeds_bank_keys = ['quantity','origin','year', 'local', 'description', 'public', 'available'];
		$seeds_keys = ['sci_name','common_name','polinization','direct'];
		//,'species', 'variety','family'];
		$seeds_bank_new = [];
		$seeds_new = [];
		$months_new = [];
		foreach ( $request->input() as $key =>  $value ){
			if (in_array($key, $seeds_keys)){
				$seeds_new[$key] = $value;
			}
			if (in_array($key, $seeds_bank_keys)){
				$seeds_bank_new[$key] = $value;
			}
			if ($key == 'months'){
				$months_new = $value;
			}
		}
        // dd($request->input('months'));
        // dd($months_new);

		//$user = \Caravel\User::where('id', 1)->first();

		$user = \Auth::user();
		$seeds_bank_new['user_id'] = $user->id;

		if ($request->input('_id')){
			$seed_id = $request->input('seed_id');
			\DB::table('seeds_bank')->where('id', $request->input('_id'))
				->update($seeds_bank_new);
			\DB::table('seeds')->where('id', $seed_id)
				->update($seeds_new);
			if (! $months_new){
				\DB::table('seed_months')->where('seed_id', $seed_id)->delete();
			} else {
				$months = \DB::table('seed_months')->where('seed_id', $seed_id)->lists('month');
				\DB::table('seed_months')->where('seed_id', $seed_id)
					->whereNotIn('month', $months_new)->delete();
				foreach($months_new as $month){
					if (! in_array($month, $months)){
						\DB::table('seed_months')
							->insert(['seed_id' => $seed_id, 'month'=> $month ]);
					}
				}
			}
			// maybe flash an 'Updated $id' message
			//dd('has has ID');
		} else {
			$seeds_bank_new['available'] = true;
			$seed_id = \DB::table('seeds')
				->insertGetId($seeds_new);
			$seeds_bank_new['seed_id'] = $seed_id;
			\DB::table('seeds_bank')
				->insert($seeds_bank_new);
			foreach($months_new as $month){
				\DB::table('seed_months')
					->insert(['seed_id' => $seed_id, 'month'=> $month ]);
			}
			// maybe flash an 'Added new seed' message
		}


		//return back()->withInput();
			//->withErrors(['some error' => "Aconteceu qualquer coisa com o formulário"]);
		// all is done
		
		return redirect('/seedbank');
	}
	public function getPreferences()
	{
		return view('seedbank::preferences')
			->with('messages', \Lang::get('seedbank::messages'))
			->with('menu', \Lang::get('seedbank::menu'))
			->with('active', ['profile' => true]);
	}
	public function postpreferences()
	{
		//
	}
	public function getSearch()
	{
		return view('seedbank::search')
			->with('messages', \Lang::get('seedbank::messages'))
			->with('menu', \Lang::get('seedbank::menu'))
			->with('active', ['search' => true]);

	}
	public function postSearch(Request $request)
	{
		// $user = \Auth::user();
		$user = $request->user();
		$q = [];
		foreach($request->input() as $key => $value){
			if (in_array($key, ['common_name', 'sci_name']) && ($value)){
				$q[$key] = $value;
			}
		}
		if (! $q){ return [];}
		$query = \DB::table('seeds');
		foreach($q as $key => $value){
			$query->orWhere($key, 'like', '%' . $value . '%');
		}
		$results = $query->select('id', 'common_name', 'sci_name')->distinct()->get();
		/*$result = [];
		foreach($results as $i){
			$myarray = (array)$i;
			$result[] = $myarray;
		};*/
		return $results;
	}
	public function postAutocomplete(Request $request)
	{
		// $user = \Auth::user();
		$user = $request->user();
		$query_term = $request->input('query');
		$query_name = $request->input('query_name');
		if (! in_array($query_name, ['common_name', 'sci_name'])){
			return [];
		}
		$results = \DB::table('seeds')
			->join('seeds_bank', 'seeds_bank.seed_id', '=', 'seeds.id')
			->where($query_name, 'like', '%' . $query_term . '%')
			->where(function($query) use ($user) { $query->where('public', true)->orWhere('user_id', $user->id);})
			->select('seed_id', $query_name)->distinct()
			->get();
		$result = array();
		foreach($results as $i){
			$myarray = (array)$i;
			$myarray['value'] = $myarray[$query_name];
			$myarray['id'] = $myarray['seed_id'];

			$result[] = $myarray;
		};
		return $result;
	}
	
}
