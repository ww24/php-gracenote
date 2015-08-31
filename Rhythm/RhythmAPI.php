<?php
/**
 * Gracenote Rythm API Wrapper
 *
 */

namespace Gracenote\Rhythm;

require 'vendor/autoload.php';

use GuzzleHttp\Client;

/**
 * Gracenote Rhythm API Wrapper Class
 */
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

    /**
     * string $lang レスポンス言語の指定 (3文字)
     * http://en.wikipedia.org/wiki/List_of_ISO_639-2_codes
     */
    public $lang = 'jpn';
    /**
     * string $country リージョンの指定 (3文字)
     * http://en.wikipedia.org/wiki/ISO_3166-1_alpha-3#Current_codes
     */
    public $country = 'jpn';

    /**
     * $user_id が与えられなかった場合は自動で登録を行う
     *
     * @param string $client_id
     * @param string|null $user_id
     */
    public function __construct($client_id, $user_id = null)
    {
        $this->client_id = $client_id;
        $this->user_id = $user_id;
        if ($user_id === null) {
            $this->user_id = static::register();
        }
    }

    /**
     * PHP magick method for private property getter
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->$name;
    }

    /**
     * UserID を取得するために Gracenote への登録を行います
     *
     * @return string
     */
    public function register()
    {
        $data = $this->request('register');
        return $data->USER[0]->VALUE;
    }

    /**
     * ラジオステーションを作成します
     * ジャンル, ムード, 年代は ID で指定する必要があります
     * getFieldvalues method で ID 一覧を事前に取得してください
     *
     * @param array $param
     * @param string $param['artist_name'] アーティスト名
     * @param string $param['track_title'] 曲名 (アーティスト名と共に指定する必要あり)
     * @param number $param['genre'] ジャンル
     * @param number $param['mood'] ムード
     * @param number $param['era'] 年代
     *
     * # 以下共通オプション
     * @param number $param['focus_similarity'] 類似度
     * @param number $param['focus_popularity'] 人気度
     * @param string $param['dmca'] DMCA rule ('yes' or 'no')
     * @param number $param['return_count'] 一度に受け取る曲数
     * @param string $param['select_extended'] ('cover' or 'link')
     */
    public function create($params)
    {
        $data = $this->request('radio/create', $params);
        return $data;
    }

    /**
     * フィードバックの送信を行い新しいトラックを取得します
     * event_name = ('track_played' or 'track_skipped' or 'track_like' or 'track_dislike' or 'artist_like' or 'artist_dislike')
     *
     * @param array $param
     * @param string $params['radio_id'] RadioID を指定する
     * @param string $params['event'] event_name + '_' + GN_ID
     */
    public function feedback($params)
    {
        $data = $this->request('radio/event', $params);
        return $data;
    }

    public function fieldvalues($mode)
    {
        $data = $this->request('fieldvalues', [
            'fieldname' => $mode,
        ]);
        return $data;
    }

    /**
     * GENRE, MOOD, ERA を取得して property を更新します
     *
     * @return array
     */
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

    /**
     * request wrapper
     * GuzzleHttp\Client を使って Gracenote API へ HTTP GET リクエストを発行します
     *
     * @param string $path request path
     * @param array $params HTTP GET queries
     * @return object
     */
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
            'query' => $params,
        ]);

        return json_decode($res->getBody())->RESPONSE[0];
    }
}
