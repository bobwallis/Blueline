<?php
namespace Blueline\Helpers;

class URL
{
    private static $corrections = array(
        'Sutton_cum_Lound_S_Minor' => 'Sutton-cum-Lound_Surprise_Minor',
        'Willoughby_on_the_Wolds_S_Minor' => 'Willoughby-on-the-Wolds_Surprise_Minor',
        'Ferret-replacement_Cat_No1_Differential_Bob_Minor' => 'Ferret-Replacement_Cat_No_1_Differential_Bob_Minor',
        'Dead_Ringer_For_Love_Minimus' => 'Dead_Ringer_for_Love_Minimus',
        'No_Place_Like_Kent_Treble_Bob_Minor' => 'No_Place_like_Kent_Treble_Bob_Minor',
        'Armitage-is-the-name_Bob_Minor' => 'Armitage-Is-the-Name_Bob_Major',
        'Tee-jay_Surprise_Major' => 'Tee-Jay_Surprise_Major',
        'Sgurr_Fhuaran_Surprise_Minor' => 'Sgrr_Fhuaran_Surprise_Minor'
    );

    public static function canonical($url)
    {
        // Manual redirects to correct lingering errors in search engine indexes
        if (isset(self::$corrections[$url])) {
            return self::$corrections[$url];
        }

        // Replace S with Surprise, etc...
        $classificationsInitials = array_map(function ($c) {
            return implode('', array_map(function ($w) { return $w[0]; }, explode(' ', $c)));
        }, Classifications::toArray());
        $matches = array();
        if (preg_match('/_('.implode('|', $classificationsInitials).')_('.implode('|', Stages::toArray()).')$/', $url, $matches)) {
            $initial = $matches[1];
            $classification = str_replace(' ', '_', Classifications::toArray()[array_search($initial, $classificationsInitials)]);
            $url = preg_replace('/'.$initial.'_('.implode('|', Stages::toArray()).')$/', $classification.'_$1', $url);
        }

        // Replace "No." with "No. "
        $url = preg_replace( '/No([0-9]+)/', 'No_\1', $url );

        // If the title contains no spaces, then add them in (messed up a sitemap import in the distant past and still get crawler errors from it)
        if (strpos($url, '_') === false) {
            $url = trim(preg_replace('/([A-Z]{1})/', '_\1', $url), '_');
        }

        // Re-do the ASCII conversion
        $url = str_replace([' ', '$', '&', '+', ',', '/', ':', ';', '=', '?', '@', '"', "'", '<', '>', '#', '%', '{', '}', '|', "\\", '^', '~', '[', ']', '.'], ['_'], iconv(mb_detect_encoding($url, 'UTF-8, ISO-8859-1, ASCII', true), 'ASCII//TRANSLIT', $url));

        return $url;
    }
}
