<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
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


        $response = Analytics::performQuery(Period::create((new \DateTime('yesterday')), new \DateTime('today')), 'ga:pageviews', [
            'dimensions'  => 'ga:pagePath,ga:dimension4,ga:dimension5,ga:pageTitle',
            'sort'        => '-ga:dimension4',
            'max-results' => '300',
        ]);

        $articles = collect($response->getRows());

        $sorted = $articles->sortByDesc(function ($values) {
            return $values['4'];
        })->unique('3');

        $current = Carbon::now();
        $from    = 'Washingtonian Traffic Cop';

        foreach ($sorted as $row) {

            list($row, $hours, $pageViews, $url, $fallback, $appName, $niceThings, $reallyNiceThings, $links, $userOrChannel) = $this->initVars($row, $current);

            if ($hours > 48) {
                continue;
            }

            $this->messageOne($hours, $pageViews, $from, $fallback, $row, $url, $reallyNiceThings, $links, $userOrChannel, $appName);

            $this->messageTwo($hours, $pageViews, $from, $fallback, $row, $url, $niceThings, $links, $userOrChannel, $appName);

            $this->messageThree($hours, $pageViews, $from, $fallback, $row, $url, $niceThings, $links, $userOrChannel, $appName);

        }
    }


    /**
     * @param $row
     * @param $current
     *
     * @return array
     */
    private function initVars($row, $current)
    {
        $slackUsernames = [
            'Amanda Whiting'             => 'awhiting',
            'Andrew Beaujon'             => 'abeaujon',
            'Andrew Propp '              => 'apropp',
            'Ann Limpert'                => 'annlimpert',
            'Anna Spiegel'               => 'aspiegel',
            'Benjamin Freed'             => 'brfreed',
            'Briana Thomas'              => 'bthomas',
            'Caroline Cunningham'        => 'carolinecunningham',
            'Chris Combs'                => 'chriscombs',
            'Claire Donnelly'            => 'cdonnelly',
            'Dean Essner'                => 'deanessner',
            'Diane Rice'                 => 'dianevr',
            'Elaina Plott'               => 'eplott',
            'Evy Mages'                  => 'evy',
            'Greta Weber'                => 'gweber',
            'Hayley Phillips'            => 'hay_phillips',
            'Hillary Kelly'              => 'hillary_',
            'Jackson Knapp'              => 'jknapp',
            'Jason Lancaster'            => 'lancaster',
            'Jeff Elkins'                => 'jelkins',
            'Jennifer Ortiz'             => 'jortiz',
            'Jessi Taff'                 => 'jtaff',
            'Jessica Sidman'             => 'jsidman',
            'Kellie Duff'                => 'kellieduff',
            'Kristen Hinman'             => 'khinman',
            'Lauren Joseph'              => 'laurenjoseph',
            'Luke Mullins'               => 'mullinsluke ',
            'Mandy Zou'                  => 'mandyzou',
            'Marisa Dunn'                => 'marisadunn',
            'Meredith Ellison'           => 'meredith',
            'Michael Gaynor'             => 'mgaynor',
            'Michael Schaffer'           => 'mikeschaffer',
            'Mollie Bloudoff-Indelicato' => 'mollie',
            'Patrick Leddy'              => 'pleddy',
            'Paul Chernoff'              => 'pchernoff',
            'Philip Garrity'             => 'philipgarrity',
            'Ryan Weisser'               => 'rweisser',
            'Sarah Lindner'              => 'slindner',
            'Sarah Stodder'              => 'sstodds',
            'Sarah Zlotnick'             => 'sarahzlot',
            'Sherri Dalphonse'           => 'sdalphonse',
            'Sydney MaHan'               => 'smahan',
            'Tom Shafer'                 => 'tomtom',
            'Vanessa McDonald'           => 'vmcdonald',
            'Will Grunewald'             => 'will_grunewald',
            'Marisa M. Kashino'          => 'mkashino',
            'Phong Nguyen'               => 'hello_phong',
        ];

        $dt = Carbon::parse($row[1]);
        //$dt->setTimezone('America/New_York');
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
        $niceThings       = ["Awesome!", "Sweet!", "Good stuff.", "Nice!", "Great!", "Super!", "Peachy keen!", "Cool!", "Excellent!", "Ace!", "Solid!", "That's A-OK!", "Very fine!", "That's hunky dory!"];
        $reallyNiceThings = ["Boom!", "Great job!", "Wow!", "Holy cow!", "That's great!", "Most excellent!", "Very nice!", "That's the cat's pajamas!", "Wonderful!", "Commendable!", "Out of sight!", "Out of the park!", "We're over the moon!"];
        $shareTwitter     = "<https://twitter.com/home?status=" . urlencode($pageTitle) . " https://www.washingtonian.com" . $row[0] . " via @washingtonian|Share on Twitter>";
        $shareFacebook    = "<https://www.facebook.com/sharer/sharer.php?u=https://www.washingtonian.com" . $row[0] . "|Share on Facebook>";
        $links            = $shareTwitter . " | " . $shareFacebook;
        $userOrChannel    = in_array($row[2], array_keys($slackUsernames)) ? '@' . $slackUsernames[$row[2]] : '#webonauts-';
        //$userOrChannel = '#webonauts-';

        return [$row, $hours, $pageViews, $url, $fallback, $appName, $niceThings, $reallyNiceThings, $links, $userOrChannel];
    }


    /**
     * @param $hours
     * @param $pageViews
     * @param $from
     * @param $fallback
     * @param $row
     * @param $url
     * @param $reallyNiceThings
     * @param $links
     * @param $userOrChannel
     * @param $appName
     */
    private function messageOne($hours, $pageViews, $from, $fallback, $row, $url, $reallyNiceThings, $links, $userOrChannel, $appName)
    {
        if ( $pageViews > 5000) {
            $this->slackClient->from($from)->attach([
                //'author_icon' => ':washingtonian:',
                'fallback'    => $fallback,
                'text'        => "Hey, " . $row[2] . ", your post " . $url . " gotten about `" . $row['pageviews'] . "` pageviews. " . $reallyNiceThings[array_rand($reallyNiceThings, 1)] ." \n\n ".$links,
                "mrkdwn_in"   => ["text", "pretext"],
                'footer'      => 'Washingtonian Web Team  - 1',
                'footer_icon' => 'https://emoji.slack-edge.com/T03GDG7JA/washingtonian/998ab1a169101f53.png',
                'timestamp'   => new \DateTime(),
            ])->to($userOrChannel)->send($appName);
        }
    }


    /**
     * @param $hours
     * @param $pageViews
     * @param $from
     * @param $fallback
     * @param $row
     * @param $url
     * @param $niceThings
     * @param $links
     * @param $userOrChannel
     * @param $appName
     */
    private function messageTwo($hours, $pageViews, $from, $fallback, $row, $url, $niceThings, $links, $userOrChannel, $appName)
    {
        if ($pageViews > 1000) {
            $this->slackClient->from($from)->attach([
                //'author_icon' => ':washingtonian:',
                'fallback' => $fallback,
                'text'     => "Hey, " . $row[2] . ", your post " . $url . " has already gotten about `" . $row['pageviews'] . "` pageviews. " . $niceThings[array_rand($niceThings, 1)] . " Keep it going by sharing your post!  \n\n". $links,

                "mrkdwn_in"   => ["text", "pretext"],
                //Share on Twitter, https://twitter.com/home?status=" . $pageTitle . " " . $url . " via @washingtonian"
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
                'footer'      => 'Washingtonian Web Team - 2',
                'footer_icon' => 'https://emoji.slack-edge.com/T03GDG7JA/washingtonian/998ab1a169101f53.png',
                'timestamp'   => new \DateTime(),
            ])->to($userOrChannel)->send($appName);
        }
    }


    /**
     * @param $hours
     * @param $pageViews
     * @param $from
     * @param $fallback
     * @param $row
     * @param $url
     * @param $niceThings
     * @param $links
     * @param $userOrChannel
     * @param $appName
     */
    private function messageThree($hours, $pageViews, $from, $fallback, $row, $url, $niceThings, $links, $userOrChannel, $appName)
    {
        if ( $pageViews > 50 &&  $pageViews < 1000) {
            $this->slackClient->from($from)->attach([
                //'author_icon' => ':washingtonian:',
                'fallback'    => $fallback,
                'text'        => "Hey, " . $row[2] . ", your post " . $url . " is `" . $hours . "` hours old and has gotten about `" . $row['pageviews'] . "` pageviews. Any tweaks you can make to the headline or share image?". $links,
                "mrkdwn_in"   => ["text", "pretext"],
                'footer'      => 'Washingtonian Web Team - 3',
                'footer_icon' => 'https://emoji.slack-edge.com/T03GDG7JA/washingtonian/998ab1a169101f53.png',
                'timestamp'   => new \DateTime(),
            ])->to($userOrChannel)->send($appName);
        }
    }
}
