<?php namespace Modules\Seedbank\Http\Controllers;

use Pingpong\Modules\Routing\Controller;
use Illuminate\Http\Request;
use Gate;


use Validator;
use GeoIp2\Database\Reader;


class SeedBankController extends Controller {

  /**
   * Index view
   * @param void
   * @return View
   */
  public function index()
  {
    $user = \Auth::user();

    $seeds = \Caravel\Seed::where('public', true)
      ->where('user_id', '<>', $user->id)
      ->orderBy('seeds.updated_at', 'desc')
      ->limit(3)->join('users', 'users.id', '=', 'user_id')
      ->select('seeds.id', 'seeds.common_name',
        'users.name', 'users.email', 'user_id', 'users.place_name')
        ->get();

    $myseeds = \Caravel\Seed::where('user_id', $user->id)
      ->orderBy('seeds.updated_at', 'desc')
      ->limit(3)
      ->select('seeds.id', 'seeds.common_name', 'seeds.updated_at')
      ->get();

    //$newMessagesCount = $user->newThreadsCount();
    $posts = \Riari\Forum\Models\Post::orderBy('updated_at', 'DESC')->limit(4)->get();
    foreach ($posts as $post)
    {
      $post->load('thread', 'author');
    }
    $messenger = $user->lastMessages();
    $calendarNow = \Caravel\Calendar::now()->get();
    $calendarNext = \Caravel\Calendar::nextDays()->get();

    return view('seedbank::home', compact('posts', 'messenger', 'calendarNow', 'calendarNext'))
      ->with('seeds', $seeds)
      ->with('myseeds', $myseeds)
      ->with('active', ['home' => true]);
  }

  private function getEnciclopediaForm ( $item = Null, $formErrors = Null)
  {
    $formErrors = $formErrors ?: "";
    $item = $item ?: "";

    // $use_categories = [
    //   "alimentar", "medicinal", "artesanal", "auxiliar, horta ou casa",
    //   "tóxico ou nocivo", "social, simbólico, ritual", "outros usos especiais"
    // ];
    $use_categories = \Lang::get('seedbank::forms.category_types');


    if ($item) {
      $item->load(['pictures', 'family', 'uses', 'references']);
      $categories = [];
      forEach($item->uses as $use) {
        $categories []= $use_categories[$use->category_id];
      }

      $item = $item->toArray();
      for ($i=0 ; $i < sizeof($categories) ; $i++) {
        $item['uses'][$i]['category'] = $categories[$i];
      }
    }

    return view('seedbank::modal_enciclform')
      ->with('formErrors', $formErrors)
      ->with('update', true)
      ->with('preview', true)
      ->with('admin', \Auth::user()->is_admin())
      ->with('oldInput', $item )
      ->with('item', $item )
      ->with('categories', $use_categories )
      ->with('csrfToken', csrf_token())->render();
  }

  public function getEnciclopedia(Request $request, $letter = null)
  {
    $user = \Auth::user();

    $formErrors = "";
    $item = "";

    $active = $letter;

    if ($active) {
      $items = \Caravel\Enciclopedia::where('common_name', 'ILIKE', $active . '%')
        ->orderBy('common_name');
      $path = '/' . $active;
    } else {
      $items = \Caravel\Enciclopedia::orderBy('updated_at', 'desc');
      $path = "";
    }

    $paginated = $items->paginate(15)->setPath('/enciclopedia' . $path);
    //return view('seedbank::myseeds')
    foreach ($paginated->getCollection() as $item)
    {
      $item->load('family');
      $item->load('pictures');
    }

    $alphabet = [];
    foreach(str_split('abcdefghijklmnopqrstuvwxyz') as $l){
      $letter = ['letter' => $l];
      if ($active == $l) {
        $letter['active'] = true;
      };
      $alphabet[] = $letter;
    }

    $item = \Caravel\Enciclopedia::find($request->input('id'));

    $modal_content = self::getEnciclopediaForm($item);

    $use_categories = \Lang::get('seedbank::forms.category_types');

    return view('seedbank::enciclopedia')
      ->with('modal_content', $modal_content)
      ->with('alphabet', $alphabet)
      ->with('item', $item)
      ->with('categories', $use_categories )
      ->with('pagination', \Lang::get('pagination'))
      ->with('links', $paginated->render())
      ->with('paginated', $paginated)
      ->with('active', ['enciclopedia' => true]);
  }

  public function getHorta()
  {
    $user = \Auth::user();

    return view('seedbank::horta')
      ->with('active', ['horta' => true]);
  }

  private function getMySeedForm ( $myseed = Null, $formErrors = Null) {
    $monthsTable = [];
    foreach (range(0, 11) as $number) {
      $monthsTable[$number] = false;
    }
    $myseed = $myseed ?: "";
    if ($myseed){
      $myseed->load(['months', 'species', 'variety', 'family', 'pictures']);
      foreach ( $myseed->months as $month) {
        $monthsTable[$month->month - 1] = true;
      }
      $myseed = $myseed->toArray();
      $origin = $myseed["origin"];
      $myseed['origin'] = [];
      for ($i=0; $i < 4 ; $i++) {
        $myseed['origin'][$i] = ($origin == $i);
      }
      $traditionalrisk = $myseed["traditionalrisk"];
      $myseed['traditionalrisk'] = [];
      for ($i=0; $i < 4 ; $i++) {
        $myseed['traditionalrisk'][$i] = ($traditionalrisk == $i);
      }
    };

    $formErrors = $formErrors ?: "";
    return view('seedbank::modal_seedform')
      ->with('formErrors', $formErrors)
      ->with('update', true)
      ->with('preview', true)
      ->with('oldInput', $myseed )
      ->with('seed', $myseed )
      ->with('monthstable', $monthsTable)
      ->with('csrfToken', csrf_token())->render();
    }

  public function getMySeeds(Request $request)
  {

    $user = \Auth::user();
    //$seeds = $user->seeds()->orderBy('updated_at', 'desc');
    //$pages = $seeds->paginate(5)->setPath('/seedbank/myseeds');
    $paginated = $user->seeds()->orderBy('updated_at', 'desc')->paginate(5)->setPath('/seedbank/myseeds');
    //return view('seedbank::myseeds')
    foreach ($paginated->getCollection() as $seed)
    {
      // 'cookings', 'medicines',
      $seed->load(
        ['variety', 'species', 'family', 'months',
        'pictures']
      );
    }

    $myseed_id = $request->input('seed_id', null);
    $myseed = $user->seeds->find($myseed_id);

    // Just to create the div for the submition errors
    $formErrors = true;

    $modal_content = self::getMySeedForm(
      $myseed = $myseed,
      $formErrors = $formErrors
    );


    $part = [ 'myseeds' => true ];

    $view = view('seedbank::myseeds', compact('part'))
      ->with('pagination', \Lang::get('pagination'))
      ->with('paginated', $paginated)
      ->with('links', $paginated->render())
      ->with('modal_content', $modal_content)
      ->with('active', ['myseeds' => true])
      ->with('preview', true);

    if ($myseed) {
      $view = $view->with('modal', true)
        ->with('modal-title', $myseed->common_name);
    }
//dd($paginated);
    return $view;
  }

  public function getAllSeeds(Request $request)
  {
    // View for seeds
    $user = \Auth::user();
    $seeds = \Caravel\Seed::where('user_id', '<>', $user->id)->where('public', true)->orderBy('updated_at', 'desc');
    //$seeds = $user->seeds()->orderBy('updated_at', 'desc');
    //$pages = $seeds->paginate(5)->setPath('/seedbank/myseeds');
    $paginated = $seeds->paginate(15)->setPath('/seedbank/allseeds');
    //return view('seedbank::myseeds')
    foreach ($paginated->getCollection() as $seed)
    {
      $seed->load('family');
      $seed->load('pictures');
      $seed->load('user');
    }
    $part = [ 'myseeds' => true ];

    $seed_id = $request->input('seed_id', null);
    $seed = \Caravel\Seed::find($seed_id);
    $user_id = $request->input('user_id', null);




    $monthsTable = [];
    foreach (range(0, 11) as $number) {
      $monthsTable[$number] = false;
    }
    if ($seed) {
      $seed->load(['months', 'species', 'variety', 'family', 'pictures', 'user']);
      foreach ( $seed->months as $month) {
        $monthsTable[$month->month - 1] = true;
      }
    };

    $modal_content = view('seedbank::modal_userseedpreview')
      ->with('user_id', $user_id)
      ->with('preview', true)
      ->with('seed', $seed )
      ->with('monthstable', $monthsTable)
      ->with('viewonly', true)
      ->with('csrfToken', csrf_token())->render();

    return view('seedbank::seeds', compact('part', 'modal_content'))
      ->with('user_id', $user_id)
      ->with('pagination', \Lang::get('pagination'))
      ->with('paginated', $paginated)
      ->with('links', $paginated->render())
      //->with('myseeds', $seeds->get())
      ->with('modal', ($seed) )
      ->with('active', ['seeds' => true]);
  }

  public function getUserSeeds(Request $request)
  {
    $userToView = \Caravel\User::findOrFail($request->input('id'));

    // View for seeds
    $user = \Auth::user();
    $seeds = $userToView->seeds()->where('available', true)->orderBy('updated_at', 'desc');
    //$seeds = $user->seeds()->orderBy('updated_at', 'desc');
    //$pages = $seeds->paginate(5)->setPath('/seedbank/myseeds');
    $paginated = $seeds->paginate(15)->setPath('/seedbank/allseeds');
    //return view('seedbank::myseeds')
    foreach ($paginated->getCollection() as $seed)
    {
      $seed->load('family');
      $seed->load('pictures');
      $seed->load('user');
    }

    return view('seedbank::userseeds', compact('part', 'modal_content'))
      ->with('pagination', \Lang::get('pagination'))
      ->with('paginated', $paginated)
      ->with('links', $paginated->render())
      //->with('myseeds', $seeds->get())
      ->with('modal', ($seed) )
      ->with('active', ['seeds' => true]);
  }

  public function getMessages()
  {
    $user = \Auth::user();

    $userMessages = $user->lastMessages(10)->toArray();
    //->get()->sortByDesc('created_at')->chunk(4)[0]->toArray();
    $unreadmessages = 0;
    foreach($userMessages as &$m) {
      if (($m['sender_id'] != $user->id) && ($m['read'])){
        $unreadmessages++;
        $m['enabled'] = true;
      }
      if ($m['sender_id'] == $user->id){
        $m['sent'] = true;
      }
    }
    return view('seedbank::messages')
      ->with('usermessages', $userMessages)
      ->with('unreadmessages', $unreadmessages)
      ->with('active', ['messages' => true]);
  }

  public function getExchanges()
  {
    $user = \Auth::user();
    $transactions = $user->transactionsPending();
    //oneday ago- P1D  on week ago- P7D
    $oneweekago  = date_create()->sub(new \DateInterval('P1D'))->getTimeStamp();
    //$oneweekago  = date_create()->sub(new \DateInterval('PT2M'))->getTimeStamp();
    foreach(['asked_to', 'asked_by'] as $asked) {
      if ($transactions[$asked]){
        foreach($transactions[$asked] as $key => $value){
          if (($transactions[$asked][$key]['completed'] == '1') || ($transactions[$asked][$key]['accepted'] == '1')) {
            $v = $transactions[$asked][$key];
            unset($transactions[$asked][$key]);
            if (strtotime($v['updated_at']) > $oneweekago){
              $transactions[$asked][$key] = $v;
            }

          }
        }
        foreach($transactions[$asked] as &$tr) {
          $ta=[]; $tc=[];
          foreach (["0","1","2"] as $i){
            $ta[$i]= ($tr['accepted'] == $i);
            $tc[$i]= ($tr['completed'] == $i);
          }
          $tr['accepted'] = $ta;
          $tr['completed'] = $tc;
        }
      }
    }
    //return view('seedbank::exchanges')
    $part = [ 'exchanges' => true ];
    return view('seedbank::userarea', compact('part'))
      ->with('bodyId', 'myseeds')
      ->with('transactionsBy', $transactions['asked_by'])
      ->with('transactionsTo', $transactions['asked_to'])
      ->with('active', ['myseeds' => true]);
  }


  public function postRegister(Request $request)
  {
    $this->validate($request, [
      'common_name' => 'required',
      //'origin' => 'required',
    ]);

    $seed_keys = [
      'quantity','year', 'local', 'description', 'public', 'available', 'description',
      'latin_name','common_name','polinization','direct', 'untilharvest', 'origin',
      'available', 'units', 'quantity', 'traditionalrisk', 'seedtype'
    ];

    $seed_taxonomy = ['species', 'variety','family'];
    $taxonomy_model = [
      'species' => '\Caravel\Species',
      'variety' => '\Caravel\Variety',
      'family' => '\Caravel\Family'
    ];
    $seed_new = [];
    $months_new = [];
    $t = [];
    foreach ( $request->input() as $key =>  $value ){
      if (in_array($key, $seed_keys)){
        if ( $value != "") {
          $seed_new[$key] = $value;
        } else {
          if ($key == 'description') {
            $seed_new[$key] = "";
          }
        }

      }
      if (in_array($key, $seed_taxonomy)){
        // TODO: Should do a special function to work this out
        if ($value) {
          $t = $taxonomy_model[$key]::firstOrCreate(['name' => $value]);
          $seed_new[$key . '_id'] = $t->id;
        } else {
          if ($request->input('seed_id')) {
            $seedt = \Caravel\Seed::findOrFail($request->input('seed_id'));
            $seedt->update([$key . '_id' => Null]);
          }
        }
      }
      if ($key == 'months'){
        $months_new = $value;
      }
    }

    if ($request->input('seed_id')){
      $seed_id = $request->input('seed_id');
      $seed = \Caravel\Seed::findOrFail($seed_id);
      if (Gate::denies('update-seed', $seed)){
        abort(403);
      }
      $seed->syncMonths($months_new);
      $seed->update($seed_new);
    } else {
      $seed_new['user_id'] = $request->user()->id;
      $seed = \Caravel\Seed::create($seed_new);
      foreach($months_new as $month){
        $seed->months()->save(new \Caravel\SeedMonth(['month'=> $month ]));
      }
      if ($request->input('pictures_id')){
        foreach($request->input('pictures_id') as $picture_id){
          $picture = \Caravel\Picture::findOrFail($picture_id);
          $seed->pictures()->save($picture);
        }
      }
      //FIXME: maybe flash an 'Added new seed' message
    }

    //return redirect('/seedbank/myseeds');
    $seed->load(
      ['variety', 'species', 'family', 'months',
      'pictures']
    );
    return $seed;
  }

  public function getPreferences()
  {
    $user = \Auth::user();
    if(\Session::hasOldInput()){
      $oldInput =  \Session::getOldInput();
      foreach($oldInput as $key => $val) {
        if (( $user[$key] == $oldInput[$key] ) || (! $oldInput[$key])) {
          unset($oldInput[$key]);
        }
      }
    } else {
      $oldInput = [];
    }
    if ( isset($oldInput['locale']) )
    {
      $locale = $oldInput['locale'];
    } else {
      $locale = $user->locale ?: config('app.locale');
    }

    $updatelocation = false;
    $location = false;
    if ($locale == 'pt'){
      $preflocale = array('pt', 'pt-BR', 'en');
    } else {
      $preflocale = array($locale, 'en');
    }
    $geoipreader = new Reader(config('geoip.maxmind.database_path'), $preflocale);
    try {
      $geoipdata = $geoipreader->city(request()->ip());
      $updatelocation = [ 'lat' => $geoipdata->location->latitude,
        'lon' => $geoipdata->location->longitude,
        'place_name' => $geoipdata->city->name ?: \Lang::get("auth::messages.unknowncity")];
      $location = true;
    }
    catch(\GeoIp2\Exception\AddressNotFoundException $e){
      // for testing
      //$geoipdata = $geoipreader->city('81.193.130.25');
      //$updatelocation = [ 'lat' => $geoipdata->location->latitude,
      //  'lon' => $geoipdata->location->longitude,
      //  'place_name' => $geoipdata->city->name ?: \Lang::get("auth::messages.unknowncity")];
      //    $location = true;

      if ($user->place_name){
        $location = true;
      }
      /*  $updatelocation = [ 'lat' => 12.2,
                              'lon' => 121.1,
                              'place_name' => 'Porto' ];*/
    }
    $availableLangs = [];
    foreach(config('app.availableLanguagesFull') as $key => $value ) {
        array_push($availableLangs, ["value" => $key, "label" => $value, "selected" => ($key == $locale)]);
    }

    return view('seedbank::preferences', compact('oldInput', 'availableLangs'))
      ->with('messages', \Lang::get('authentication::messages'))
      ->with('csrfToken', csrf_token())
      ->with('user', $user)
      ->with('updatelocation', $updatelocation)
      ->with('location', $location)
      ->with('active', ['settings' => true]);
  }

  private function prefValidationRules($data) {

    $user = \Auth::user();
    $rules = [
      'lon' => 'required_with:lat|regex:/^-?\d+([\,]\d+)*([\.]\d+)?$/|between:-180,180',
      'lat' => 'required_with:lon|regex:/^-?\d+([\,]\d+)*([\.]\d+)?$/|between:-180,180',
      'place_name' => 'max:255|required_with:lon,lat',
    ];
    if (! $user->name == $data['name']){
      $rules['name'] = 'required|max:255|unique:users';
    }
    if (( $user->email !== $data['email']) && ($data['email'])) {
      $rules['email'] = 'sometimes|required|email|max:255|unique:users';
    }
    if ($data['password']){
      $rules['password'] = 'required|confirmed|min:6';
    }
    return $rules;

  }

  public function postPreferences(Request  $request)
  {
    $user = \Auth::user();

    $this->validate($request, $this->prefValidationRules($request->all()));

    if (!$request->input('password')){
      unset($request['password']);
    } else {
      $request['password'] = bcrypt($request->password);
    };
    foreach(['email', 'lat', 'lon', 'place_name'] as $field)
    {
      if (!$request->input($field))
      {
        unset($request[$field]);
      }
    }
    $user = $user->update($request->all());
    return redirect('/seedbank');
  }

  public function getSearch()
  {
    return view('seedbank::search')
      ->with('active', ['search' => true]);
  }

  public function postSearch(Request $request)
  {
    // $user = \Auth::user();
    $user = $request->user();
    $q = [];
    foreach($request->input() as $key => $value){
      if (in_array($key, ['common_name', 'latin_name']) && ($value)){
        $q[$key] = $value;
      }
    }
    if (! $q){ return [];}
    $query = \Caravel\Seed::query()
      ->where('public', true)
      ->where('available', true)
      ->where('user_id', '!=', $user->id);
    $query->where(function($qu) use ($q){

      foreach($q as $key => $value){
        $qu->orWhere($key, 'like', '%' . $value . '%');
      }
    });
    $results = $query->select('id', 'common_name', 'latin_name', 'user_id')->distinct()->get();
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
    if (! in_array($query_name, ['common_name', 'latin_name'])){
      return [];
    }
    $results = \DB::table('seeds')
      ->join('seeds_banks', 'seeds_banks.seed_id', '=', 'seeds.id')
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

  public function postMessageReply(Request $request)
  {
    // if error with form
    $this->validate($request, [
      'body' => 'required',
      'message_id' => 'required',
    ]);
    $message_id = $request->input('message_id');
    $message = $request->user()->messages()->where('id', $message_id)->first();
    if (Gate::denies('reply-message', $message)){
      abort(403);
    }
    $reply = $message->reply(['body' => $request->input('body')]);
    $message->pivot->replied = true;
    $message->pivot->save();

    if ($reply){
      return ["response" => "Message sent"];
    }
    return false;



    // maybe flash an 'Added new seed' message
    //return redirect('/seedbank');
  }

  public function postMessageSend(Request $request)
  {
    // if error with form
    $this->validate($request, [
      'body' => 'required',
      'seed_id' => 'required',
    ]);
    $subject = $request->input('subject');
    $body = $request->input('body');
    $seed_id = $request->input('seed_id');
    $seed = \Caravel\Seed::findOrFail($seed_id);
    $user_id = $seed->user_id;
    if (!$subject)
    {
      $subject = $seed->common_name;
    }
    $message = \Caravel\Message::create(
      [
        'user_id' => $request->user()->id,
        'subject' => $subject,
        'body' => $body,
      ]
    );
    $message->save();
    $message->root_message_id = $message->id;
    $request->user()->transactionStart(['asked_to'=>$user_id, 'seed_id'=>$seed_id]);

    // maybe flash an 'Added new seed' message
    return redirect('/seedbank/search');
  }

  public function postAddPicture (Request $request) {
    // TODO: Limit number of picture by seed?
    $user = \Auth::user();
    if ($request->has('seed_id')){
      $seed = \Caravel\Seed::findOrFail($request->input('seed_id'));
    } else {
      $seed = false;
    }
    if ($request->hasFile('pictures')) {
      $uploadedimage = $request->file('pictures')[0];

      $picture = \Caravel\Picture::fromUploadedFile($uploadedimage);
      $status = [ "error" => 'Not saved!'];
      if ($picture) {
        $status = [ "picture" => $picture];
      }

      if (isset($status['error'])) {
        return [ 'files' => [ ['error' => $status['error']]]];
      } else {
        if ($seed) {
          if (!$seed->user_id == $user->id){
            return [ 'files' => [ ['error' => 'File is owned by other user']]];
          } else {
            $seed->pictures()->save($status['picture']);
          }
        }
        return [ 'files' => [
          ['md5sum' => $status['picture']->md5sum,
          'id' => $status['picture']->id,
          'url' => $status['picture']->url,
          'deleteUrl' => '/seedbank/pictures/delete/' . $status['picture']->id,
          'deleteType' => 'GET'
        ]]
      ];
      }
    }
    return [ 'files' => [['error' => 'No files sent']]];
  }

  private function getEventForm ( $event = Null, $formErrors = Null) {
    $event = $event ?: "";
    $formErrors = $formErrors ?: "";

    $event_type = \Caravel\Calendar::getEventTypes();

    return view('seedbank::modal_eventform')
      ->with('formErrors', $formErrors)
      ->with('update', true)
      ->with('preview', $event)
      ->with('oldInput', $event )
      ->with('event_type', $event_type )
      ->with('csrfToken', csrf_token())->render();
    }

  public function getEvents (Request $request) {

    $user = \Auth::user();

    $event_id = $request->input('id', null);

    //FIXME TEST TODO
    //$event = $user->seeds->find($event_id);
    if ($request->input('events', null)) {
      $events = $user->getEvents();
      return $events;
    }

    if ( $event_id ) {
      $event = \Caravel\Calendar::find($event_id);
      /*  )[
        'id' => 1,
        'title' => 'Um título',
        'location' => 'Lisboa',
        'postal' => '1900-177 Lisboa',
        'description' => 'Uma descrição do evento',
        'type' => 'AllTypes'
      ];*/
      $title = $event->title;
    } else {
      $title = Null;
      $event = Null;
    }

    // Just to create the div for the submition errors
    $formErrors = true;

    $modal_content = self::getEventForm(
      $event = $event,
      $formErrors = $formErrors
    );


    return view('seedbank::events')
      ->with('modal', true)
      ->with('update', true)
      ->with('modal_content', $modal_content)
      ->with('user', $user)
      ->with('active', ['events' => true]);
  }

  public function getSementecas (Request $request) {
    $user = \Auth::user();
    $lat = sprintf("%.5F", $user->lat);
    $lon = sprintf("%.5F", $user->lon);

    return view('seedbank::sementecas', compact('lat', 'lon'))
      ->with('active', [ 'sementecas' => true ])
      ->with('modal_content', view('seedbank::modal_sementecaform')
        ->with('csrfToken', csrf_token())
        ->with('sementeca', \Caravel\Sementeca::first())
        ->with('preview', true)->render())
      ->with('bodyId', 'mainapp');
    $user = \Auth::user();

  }


  public function setLocale($locale = null)
  {
    $availableLanguages = config('app.availableLanguages');
    $request = app('request');

    if (in_array($locale, $availableLanguages )){
      $user = \Auth::user();
      if (isset($user->locale)){
        if ($locale != $user->locale) {
          $user->locale = $locale;
          $user->save();
        };
      }
    };

    return redirect($request->header('referer'));
  }
}
