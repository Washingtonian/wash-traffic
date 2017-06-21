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
     *
     */
    protected $sent;


    /**
     * Create a new command instance.
     */
    public function __construct()
    {

        $this->sent = false;

        $settings          = [
            'unfurl_links'            => true,
            'unfurl_media'            => true,
            'link_names'              => true,
            'icon'                    => ':cop:',
            'allow_markdown'          => true,
            'markdown_in_attachments' => ['*', '`', '_', '~'],
        ];
        $this->slackClient = new Client(env('SLACK_URL'), $settings);

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

            list($row, $hours, $pageViews, $url, $fallback, $appName, $niceThings, $reallyNiceThings, $links, $userOrChannel, $suggestions) = $this->initVars($row, $current);

            if ($hours > 48 || $hours < 24) {
                continue;
            }

            $this->messageOne($hours, $pageViews, $from, $fallback, $row, $url, $reallyNiceThings, $links, $userOrChannel, $appName);

            $this->messageTwo($hours, $pageViews, $from, $fallback, $row, $url, $niceThings, $links, $userOrChannel, $appName);

            $this->messageThree($hours, $pageViews, $from, $fallback, $row, $url, $niceThings, $links, $userOrChannel, $appName, $suggestions);

            //$this->messageDebug($hours, $pageViews, $from, $fallback, $row, $url, $niceThings, $links, $userOrChannel, $appName);
            $this->sent = false;
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
            'Hayley Garrison Phillips'   => 'hay_phillips',
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
            'Manyun Zou'                 => 'mandyzou',
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
        $niceThings       = [
            "Awesome!",
            "Sweet!",
            "Good stuff.",
            "Nice!",
            "Great!",
            "Super!",
            "Peachy keen!",
            "Cool!",
            "Excellent!",
            "Ace!",
            "Solid!",
            "That's A-OK!",
            "Very fine!",
            "That's hunky dory!",
        ];
        $reallyNiceThings = [
            "Boom!",
            "Great job!",
            "Wow!",
            "Holy cow!",
            "That's great!",
            "Most excellent!",
            "Very nice!",
            "That's the cat's pajamas!",
            "Wonderful!",
            "Commendable!",
            "Out of sight!",
            "Out of the park!",
            "We're over the moon!",
        ];

        $suggestions = [
            "Any tweaks you can make to the headline or share image?",
            "Try submitting it to Digg.",
            "Want to try sending the link to an editor at another publication?",
            "Are there any relevant blogs that might be willing to link to it?",
            "Could you share it on Facebook?",
            "Could you link to it from another relevant story?",
            "Anything you can add to the story that will help seal the deal for readers?",
            "Does the headline tell enough of the story--or too much of the story?",
            "Does it have a strong featured image?",
            "Would you click the link if you saw it on another site? Anything you can do to juice it up?",
            "Anything you can cut to make it punchier?",
            "Does it get to the point quickly enough?",
            "Is there a more controversial angle you could take?",
            "Does the story have enough conflict?",
            "Was the story surprising for readers?",
            "Any personalities you can @ on Twitter?",
            "Think Drudge would take it?",
            "Any subreddits that would be interested?",
            "Does it solve a problem for the reader?",
            "Any juicy details you could add?",
            "Can you simplify the headline? (How would you describe the story to a friend?)",
        ];

        $shareTwitter  = "<https://twitter.com/home?status=" . urlencode($pageTitle) . " https://www.washingtonian.com" . $row[0] . " via @washingtonian|Share on Twitter>";
        $shareFacebook = "<https://www.facebook.com/sharer/sharer.php?u=https://www.washingtonian.com" . $row[0] . "|Share on Facebook>";
        $links         = $shareTwitter . " | " . $shareFacebook;
        $userOrChannel = in_array($row[2], array_keys($slackUsernames)) ? '@' . $slackUsernames[$row[2]] : '#trafficcop';

        //$userOrChannel = '#webonauts-';

        return [$row, $hours, $pageViews, $url, $fallback, $appName, $niceThings, $reallyNiceThings, $links, $userOrChannel, $suggestions];
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
        if ($pageViews > 5000 && $this->sent == false) {
            $this->slackClient->from($from)->attach([
                'fallback'    => $fallback,
                'text'        => "Hey, " . $row[2] . ", your post " . $url . " gotten about `" . $row['pageviews'] . "` pageviews. " . $reallyNiceThings[array_rand($reallyNiceThings,
                        1)] . " \n\n " . $links,
                "mrkdwn_in"   => ["text", "pretext"],
                'footer'      => 'Washingtonian Web Team  - 1',
                'footer_icon' => 'https://emoji.slack-edge.com/T03GDG7JA/washingtonian/998ab1a169101f53.png',
                'timestamp'   => new \DateTime(),
            ])->to($userOrChannel)->send($appName);

            $this->slackClient->from($from)->attach([
                'fallback'    => $fallback,
                'text'        => "Hey, " . $row[2] . ", your post " . $url . " gotten about `" . $row['pageviews'] . "` pageviews. " . $reallyNiceThings[array_rand($reallyNiceThings,
                        1)] . " \n\n " . $links,
                "mrkdwn_in"   => ["text", "pretext"],
                'footer'      => 'Washingtonian Web Team  - 1',
                'footer_icon' => 'https://emoji.slack-edge.com/T03GDG7JA/washingtonian/998ab1a169101f53.png',
                'timestamp'   => new \DateTime(),
            ])->to('#trafficcop')->send($appName);

            $this->sent = true;
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
        if ($pageViews > 1000 && $this->sent == false) {
            $this->slackClient->from($from)->attach([
                'fallback' => $fallback,
                'text'     => "Hey, " . $row[2] . ", your post " . $url . " has already gotten about `" . $row['pageviews'] . "` pageviews. " . $niceThings[array_rand($niceThings,
                        1)] . " Keep it going by sharing your post!  \n\n" . $links,

                "mrkdwn_in"   => ["text", "pretext"],
                'footer'      => 'Washingtonian Web Team - 2',
                'footer_icon' => 'https://emoji.slack-edge.com/T03GDG7JA/washingtonian/998ab1a169101f53.png',
                'timestamp'   => new \DateTime(),
            ])->to($userOrChannel)->send($appName);

            $this->slackClient->from($from)->attach([
                'fallback' => $fallback,
                'text'     => "Hey, " . $row[2] . ", your post " . $url . " has already gotten about `" . $row['pageviews'] . "` pageviews. " . $niceThings[array_rand($niceThings,
                        1)] . " Keep it going by sharing your post!  \n\n" . $links,

                "mrkdwn_in"   => ["text", "pretext"],
                'footer'      => 'Washingtonian Web Team - 2',
                'footer_icon' => 'https://emoji.slack-edge.com/T03GDG7JA/washingtonian/998ab1a169101f53.png',
                'timestamp'   => new \DateTime(),
            ])->to('#trafficcop')->send($appName);

            $this->sent = true;
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
    private function messageThree($hours, $pageViews, $from, $fallback, $row, $url, $niceThings, $links, $userOrChannel, $appName, $suggestions)
    {
        if ($pageViews > 50 && $pageViews < 1000 && $this->sent == false) {
            $this->slackClient->from($from)->attach([
                'fallback'    => $fallback,
                'text'        => "Hey, " . $row[2] . ", your post " . $url . " is `" . $hours . "` hours old and has gotten about `" . $row['pageviews'] . "` pageviews. " . $suggestions[array_rand($suggestions,
                        1)] . " \n\n" . $links,
                "mrkdwn_in"   => ["text", "pretext"],
                'footer'      => 'Washingtonian Web Team - 3',
                'footer_icon' => 'https://emoji.slack-edge.com/T03GDG7JA/washingtonian/998ab1a169101f53.png',
                'timestamp'   => new \DateTime(),
            ])->to($userOrChannel)->send($appName);

            $this->slackClient->from($from)->attach([
                'fallback'    => $fallback,
                'text'        => "Hey, " . $row[2] . ", your post " . $url . " is `" . $hours . "` hours old and has gotten about `" . $row['pageviews'] . "` pageviews. " . $suggestions[array_rand($suggestions,
                        1)] . " \n\n" . $links,
                "mrkdwn_in"   => ["text", "pretext"],
                'footer'      => 'Washingtonian Web Team - 3',
                'footer_icon' => 'https://emoji.slack-edge.com/T03GDG7JA/washingtonian/998ab1a169101f53.png',
                'timestamp'   => new \DateTime(),
            ])->to('#trafficcop')->send($appName);

            $this->sent = true;
        }
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
    private function messageDebug($hours, $pageViews, $from, $fallback, $row, $url, $reallyNiceThings, $links, $userOrChannel, $appName)
    {

        $this->slackClient->from($from)->attach([
            'fallback'    => $fallback,
            'text'        => "Hey, " . $row[2] . ", your post " . $url . " gotten about `" . $row['pageviews'] . "` pageviews. " . $reallyNiceThings[array_rand($reallyNiceThings,
                    1)] . " \n\n " . $links,
            "mrkdwn_in"   => ["text", "pretext"],
            'footer'      => 'Washingtonian Web Team  - Debug',
            'footer_icon' => 'https://emoji.slack-edge.com/T03GDG7JA/washingtonian/998ab1a169101f53.png',
            'timestamp'   => new \DateTime(),
        ])->to('#trafficcop')->send($appName);

        $this->sent = true;
    }
}

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
