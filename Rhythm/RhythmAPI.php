<?php
/**
 * Gracenote Rythm API Wrapper
 *
 *
 */

namespace Gracenote\Rhythm;

require 'vendor/autoload.php';

use GuzzleHttp\Client;

class RhythmAPI
{
    const END_POINT = 'https://cXXXXXXX.web.cddbp.net/webapi/json/1.0';
    const FIELDS = ['RADIOGENRE', 'RADIOMOOD', 'RADIOERA'];
    const EVENTS = ['track_played', 'track_skipped', 'track_like', 'track_dislike', 'artist_like', 'artist_dislike'];

    private $client_id;
    private $user_id;
    private $genres = [];
    private $moods = [];
    private $eras = [];

    public $lang = 'jpn';
    public $country = 'jpn';

    public function __construct($client_id, $user_id = null)
    {
        $this->client_id = $client_id;
        if ($user_id === null) {
            $this->user_id = static::register();
        }
    }

    public function __get($name)
    {
        return $this->$name;
    }

    public function register()
    {
        $data = $this->request('register');
        return $data->USER[0]->VALUE;
    }

    public function create($params)
    {
        $data = $this->request('radio/create', $params);
        return $data;
    }

    public function feedback($params)
    {
        $data = $this->request('radio/event', $params);
        return $data;
    }

    public function fieldvalues($mode)
    {
        $data = $this->request('fieldvalues', [
            'fieldname' => $mode
        ]);
        return $data;
    }

    public function getFieldvalues()
    {
        foreach (['GENRE', 'MOOD', 'ERA'] as $type) {
            $list = $this->fieldvalues('RADIO' . $type)->$type;
            $key = strtolower($type) . 's';
            $keyValue = &$this->$key;
            foreach ($list as $item) {
                $keyValue[$item->ID] = $item->VALUE;
            }
        }

        return [
            'genre' => $this->genres,
            'mood' => $this->moods,
            'era' => $this->eras,
        ];
    }

    private function request($path, array $params = [])
    {
        $c = explode('-', $this->client_id)[0];
        $end_point = str_replace('XXXXXXX', $c, static::END_POINT);
        
        if (isset($this->user_id)) {
            $params['user'] = $this->user_id;
        }

        $params = array_merge($params, [
            'client' => $this->client_id,
            'lang' => $this->lang,
            'country' => $this->country,
        ]);

        $client = new Client();
        $res = $client->get("$end_point/$path", [
            'query' => $params
        ]);

        return json_decode($res->getBody())->RESPONSE[0];
    }
}
