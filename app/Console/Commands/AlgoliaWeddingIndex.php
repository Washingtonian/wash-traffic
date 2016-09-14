<?php

namespace App\Console\Commands;

use AlgoliaSearch\Client;
use App\Post;
use Carbon\Carbon;
use Corcel\Database;
use Illuminate\Console\Command;

class AlgoliaWeddingIndex extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'index:weddings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Index Agolia Wedding providers with a random sort id';


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {

        $this->client = new Client(getenv('ALGOLIA_ID'), getenv('ALGOLIA_KEY'));
        parent::__construct();
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $index = $this->client->initIndex("dev_WEDDINGS");

        $posts = Post::taxonomy('provider_type', 'wedding-vendors')->status('publish')->get()->reject(function ($item) {
            return (boolean)$item->meta->enhanced_check != true;
        });

        if ($posts->count() > 0) {
            foreach ($posts as $key => $item) {
                if ($item->meta->agolia_id) {

                    $indexDate = Carbon::now();

                    $data = [
                        'sorted_date' => $indexDate->toDateTimeString(),
                        'sort_order'  => mt_rand(),
                        'objectID'    => $item->meta->agolia_id,
                    ];
                    $index->partialUpdateObject($data, false);
                }
            }
        }
    }
}
