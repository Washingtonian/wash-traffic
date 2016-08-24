<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Maknz\Slack\Client;
use Spatie\Analytics\AnalyticsFacade as Analytics;
use Spatie\Analytics\Period;

class StoryStatus extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'story:notify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Notifications for stories';

    /**
     * @var
     */
    protected $client;


    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        $settings          = [
            'unfurl_links'            => true,
            'unfurl_media'            => true,
            'link_names'              => true,
            'icon'                    => ':cop:',
            'allow_markdown'          => true,
            'markdown_in_attachments' => ['*', '`', '_', '~'],
        ];
        $this->slackClient = new Client('https://hooks.slack.com/services/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', $settings);

        parent::__construct();
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        //$optParams = array(
        //    'dimensions' => 'rt:medium');
        //$results = Analytics::getAnalyticsService()->data_realtime->get(
        //    'ga:1860879',
        //    'rt:activeUsers',
        //    $optParams);

        $response = Analytics::performQuery(Period::days(2), 'ga:pageviews', [
            'dimensions'  => 'ga:pagePath,ga:dimension4,ga:dimension5,ga:pageTitle',
            'sort'        => '-ga:dimension4',
            'max-results' => '20',
        ]);
        $articles = collect($response->getRows());
        $current  = Carbon::now();
        $from     = 'Washingtonian Traffic Cop';
        foreach ($articles as $row) {
            $dt               = Carbon::parse($row[1]);
            $row['humanDate'] = $dt->format('l jS \\of F Y h:i:s A');
            $days             = $dt->diffInDays($current);
            $hours            = $dt->diffInHours($current);
            $pageTitle        = $row[3];
            $pageViews        = $row[4];
            $row['days']      = $days;
            $row['hours']     = $hours;
            $row['pageviews'] = $pageViews;

            $pageTitle        = str_replace(' | Washingtonian', '', $pageTitle);
            $url              = '<https://www.washingtonian.com' . $row[0] . '|' . $pageTitle . '>';
            $fallback         = 'We wanted to tell you something, but then we forgot what to say. Holler at somebody technical, k?';
            $appName          = 'New alert from the Washingtonian Traffic Cop.';
            $niceThings       = ["Awesome!", "Sweet!", "Good stuff.", "Nice!"];
            $reallyNiceThings = ["Boom!", "Great job!", "Wow!", "Holy cow!"];
            if ($days == 1 && $pageViews > 1000) {
                $this->slackClient->from($from)->attach([
                    //'author_icon' => ':washingtonian:',
                    'fallback' => $fallback,
                    'text'     => "Hey " . $row[2] . ", your post " . $url . " has already gotten `" . $row['pageviews'] . "` pageviews. " . $niceThings[array_rand($niceThings,
                            1)] . " Keep it going by sharing your post!",

                    "mrkdwn_in"   => ["text", "pretext"],
                    //Share on Twitter, https://twitter.com/home?status=" .  $pageTitle . " " . $url . " via @washingtonian"
                    //"Share on Facebook", url: "
                    //'actions'     => [
                    //    [
                    //        "name"    => "facebook",
                    //        "text"    => "Share on Facebook",
                    //        "style"   => "success",
                    //        "type"    => "button",
                    //        "value"   => "https://www.facebook.com/sharer/sharer.php?u=" . $url,
                    //        "confirm" => [
                    //            "title"        => "Are you sure?",
                    //            "text"         => "Do you want to share this post with facebook?",
                    //            "ok_text"      => "Yes",
                    //            "dismiss_text" => "No",
                    //        ],
                    //    ],
                    //    [
                    //        "name"    => "twitter",
                    //        "text"    => "Share on Twitter",
                    //        "style"   => "success",
                    //        "type"    => "button",
                    //        "value"   => "https://twitter.com/home?status=" .  $pageTitle . " " . $url . " via @washingtonian",
                    //        "confirm" => [
                    //            "title"        => "Are you sure?",
                    //            "text"         => "Do you want to share this post with twitter?",
                    //            "ok_text"      => "Yes",
                    //            "dismiss_text" => "No",
                    //        ],
                    //    ],
                    //],
                    'footer'      => 'Washingtonian Web Team',
                    'footer_icon' => 'https://platform.slack-edge.com/img/default_application_icon.png',
                    'timestamp'   => new \DateTime(),
                ])->to('#general')->send($appName);
            }

            if ($hours == 1 && $pageViews < 1000) {
                $this->slackClient->from($from)->attach([
                    //'author_icon' => ':washingtonian:',
                    'fallback'  => $fallback,
                    'text'      => 'Hey ' . $row[2] . ', Your post ' . $url . 'is on fire! It\'s gotten ' . $row['pageviews'] . ' pageviews in an hour. ' . $niceThings[array_rand($niceThings,
                            1)] . ' Can you keep it going by sharing the post!',
                    "mrkdwn_in" => ["text", "pretext"],
                    'footer'      => 'Washingtonian Web Team',
                    'footer_icon' => 'https://platform.slack-edge.com/img/default_application_icon.png',
                    'timestamp'   => new \DateTime(),
                ])->to('@tomtom')->send($appName);
            }

            if ($hours == 1 && $pageViews > 5000) {
                $this->slackClient->from($from)->attach([
                    //'author_icon' => ':washingtonian:',
                    'fallback'  => $fallback,
                    'text'      => 'Hey ' . $row[2] . ', your post ' . $url . ' gotten `' . $row['pageviews'] . '` pageviews in an hour. ' . $reallyNiceThings[array_rand($reallyNiceThings,
                            1)],
                    "mrkdwn_in" => ["text", "pretext"],
                    'footer'      => 'Washingtonian Web Team',
                    'footer_icon' => 'https://platform.slack-edge.com/img/default_application_icon.png',
                    'timestamp'   => new \DateTime(),
                ])->to('@tomtom')->send($appName);
            }

            if ($hours == 6 && $pageViews > 1000) {
                $this->slackClient->from($from)->attach([
                    //'author_icon' => ':washingtonian:',
                    'fallback' => $fallback,
                    'text'     => 'Hey ' . $row[2] . ', your post ' . $url . ' is ' . $hours . ' hours old and gotten ' . $row['pageviews'] . ' pageviews. Any tweaks you could make to the headline or maybe even share an image?',

                    "mrkdwn_in" => ["text", "pretext"],
                    'footer'      => 'Washingtonian Web Team',
                    'footer_icon' => 'https://platform.slack-edge.com/img/default_application_icon.png',
                    'timestamp'   => new \DateTime(),
                ])->to('@tomtom')->send($appName);
            }
            //A) your post is a day old and got < 1000 pageviews. Can you share it on your social networks?
            //B) your post got 1000 pageviews in an hour. It's on fire! Can you keep it going by sharing it on your social networks?
            //C) your post got 5000 pageviews in an hour. Great job!
            //D) your post is six hours old and got < 1000 pageviews. Any tweaks you could make to the headline or share image

        }
    }
}
