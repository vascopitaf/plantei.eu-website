<?php namespace Caravel\Commands;
use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Contracts\Bus\SelfHandling;

class fakeData extends Command implements SelfHandling {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'fake:data';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Adds fake data to database';

	/**
	 * Initiate a transaction.
	 * @param Integer
	 * @return Bool
	 */
	private function initiateAndRunTransaction($user_id)
	{
		$user = \Caravel\User::find($user_id);
		$exchange = false;
		$not_user_seed = \Caravel\Seed::where('user_id', '<>', $user_id);
		$seed_count = $not_user_seed->count();
		if (! $seed_count) { return false; }
		$counter = 0;
		while(!$exchange){
			$seed = $not_user_seed->get()[random_int(0,$seed_count - 1)];
			$data = [
				'asked_by' => $user->id,
				'asked_to' => $seed->user_id,
				'seed_id' => $seed->id
			];
			if (\Caravel\SeedsExchange::where($data)->first())
			{
				$exchange = false;
			} else {
				$exchange = $user->startTransaction([
					'asked_to'=>$seed->user_id,
					'seed_ids'=> [$seed->id]
				])->first();
			}
			$counter++;
			if ( $counter > 30 ) {
				return false;
			}
		}

		if ( ( ! $exchange->completed == 0 ) &&
			( ! $exchange->accepted == 0 ) ) { return false; }

		// Accept or reject transaction
		if (random_int(0,1))
		{
			\Caravel\User::find($exchange->asked_to)
				->acceptTransaction($exchange->id);
		} else {
			\Caravel\User::find($exchange->asked_to)
				->rejectTransaction($exchange->id);
			return true;
		}

		// Cancel or complete transaction
		if (random_int(0,1))
		{
			\Caravel\User::find($exchange->asked_by)
				->completeTransaction($exchange->id);
		} else {
			\Caravel\User::find($exchange->asked_by)
				->rejectTransaction($exchange->id);
		}
		return true;

	}

	/**
	 * Create a random seed.
	 * @param Integer
	 * @return Bool
	 */
	private function createSeed($user_id)
	{
		$faker = \Faker\Factory::create();

		$varieties = \Caravel\Variety::get();
		$variety = $varieties[
			random_int(0, $varieties->count() - 1)
		];

		for ($i = 1; $i <= random_int(1,4); $i++) {
			$file_path = $faker->file(
				storage_path('tmp'),
				'/tmp'
			);

			$image = new \Symfony\Component\HttpFoundation\File\UploadedFile(
				$file_path,
				basename($file_path),
				mime_content_type($file_path),
				filesize($file_path),
				null,
				true
			);

			$images[] = \Caravel\Picture::fromUploadedFile($image);
		}

		$seed = \Caravel\Seed::firstOrCreate([
			'common_name' => $faker->words(random_int(1,2), true),
			'local' => 'local' . str_random(3),
			//'origin' => random_int(1,3),
			'year' => random_int(2010,2018),
			'description' => $faker->text(random_int(100,500)),
			'available' => true,
			'public' => true,
			'user_id' => $user_id,
			'latin_name' => $faker->words(random_int(2,3), true),
			'species_id' => $variety->species->id,
			'variety_id' => $variety->id,
			'family_id' => $variety->species->family->id,
			'polinization' => true,
			'direct' => false
		]);

		$month1 = random_int(1,12);
		if ($month1 == 12) { $month2 = 1; } else { $month2 = $month1 + 1; }

		for ($i = 1; $i <= random_int(1,4); $i++) {
			$popnames[] = new \Caravel\Popname([
				'pop_name' => $faker->words(random_int(1,3), true),
			]);
		}
		for ($i = 1; $i <= random_int(1,4); $i++) {
			$plantuses[] = new \Caravel\PlantUsage([
				'title' => $faker->sentence(),
				'article' => $faker->text(),
				// [ "alimentar", "medicinal", "artesanal", "auxiliar, horta ou casa",
				//   "tóxico ou nocivo", "social, simbólico, ritual", "outros usos especiais"]
				'category_id' => random_int(0, 8),
			]);
		}

		$seed->pictures()->saveMany($images);
		$seed->months()->saveMany([
			new \Caravel\SeedMonth(['month' => $month1]),
			new \Caravel\SeedMonth(['month' => $month2]),
		]);
		$seed->uses()->saveMany($plantuses);
		$seed->popnames()->saveMany($popnames);
		// $entry->references()->saveMany($references);

	}

	/**
	 * Create a random enclopedia entry.
	 * @param Integer
	 * @return Bool
	 */
	private function createEnciclopediaEntry($user_id)
	{
		$faker = \Faker\Factory::create();

		$families = \Caravel\Family::get();
		$family = $families[
			random_int(0, $families->count() - 1)
		];

		for ($i = 1; $i <= random_int(1,4); $i++) {
			$file_path = $faker->file(
				storage_path('tmp'),
				'/tmp'
			);
			$image = new \Symfony\Component\HttpFoundation\File\UploadedFile(
				$file_path,
				basename($file_path),
				mime_content_type($file_path),
				filesize($file_path),
				null,
				true
			);
			$images[] = \Caravel\Picture::fromUploadedFile($image);
		}

		$entry = \Caravel\Enciclopedia::firstOrCreate([
			'user_id' => $user_id,
			'description' => $faker->text(random_int(100,500)),
			'common_name' => $faker->words(random_int(1,3), true),
			'latin_name' => $faker->words(random_int(1,3), true),
			'family_id' => $family->id,
    ]);

		// $month1 = random_int(1,12);
		// if ($month1 == 12) { $month2 = 1; } else { $month2 = $month1 + 1; }

		for ($i = 1; $i <= random_int(1,4); $i++) {
			$popnames[] = new \Caravel\Popname([
				'pop_name' => $faker->words(random_int(1,3), true),
			]);
		}
		for ($i = 1; $i <= random_int(1,4); $i++) {
			$plantuses[] = new \Caravel\PlantUsage([
				'title' => $faker->sentence(),
				'article' => $faker->text(),
				// [ "alimentar", "medicinal", "artesanal", "auxiliar, horta ou casa",
				//   "tóxico ou nocivo", "social, simbólico, ritual", "outros usos especiais"]
				'category_id' => random_int(0, 8),
			]);
		}
		for ($i = 1; $i <= random_int(1,4); $i++) {
			$references[] = new \Caravel\Reference([
	      'type' => 1, // url type
	      'content' => $faker->url()
			]);
		}

		$entry->pictures()->saveMany($images);
		// $seed->months()->saveMany([
		// 	new \Caravel\SeedMonth(['month' => $month1]),
		// 	new \Caravel\SeedMonth(['month' => $month2]),
		// ]);
		$entry->uses()->saveMany($plantuses);
		$entry->popnames()->saveMany($popnames);

		$entry->references()->saveMany($references);
	}

	/**
	 * Execute the console command.
	 *
	 * @return Void
	 */
	public function handle()
	{

		if (! is_dir(storage_path('tmp'))) {
			mkdir(storage_path('tmp'));
		}
		if (count(scandir(storage_path('tmp'))) < 10) {
			for ($i = 1; $i <= 10; $i++) {
				$faker->image(storage_path('/tmp'), 640, 480, 'nature');
			}
		}

		$user = \Caravel\User::find(1);
		for ($i=0; $i < 30; $i++) {
			$this->createEnciclopediaEntry($user->id);
		}
		foreach (\Caravel\User::all() as $key => $value) {
			$all_user_ids[] = $value->id;
		}
		for ($i=0; $i < 30; $i++) {
			$user_id = $all_user_ids[random_int(0, sizeof($all_user_ids) - 1)];
			$this->createSeed($user_id);
		}
		for ($i=0; $i < 30; $i++) {
			$user_id = $all_user_ids[random_int(0, sizeof($all_user_ids) - 1)];
			$this->initiateAndRunTransaction($user_id);
		}
	}

}
