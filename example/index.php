<?php

$apikey = file_get_contents(__DIR__ . "/apikey.json");
$apikey = json_decode($apikey);

if ($apikey === null) {
    die(json_encode([
        'status' => 'ERROR',
        'error' => 'apikey.json is required!',
    ]));
}

if (isset($apikey->gracenote->user_id) === false) {
    $apikey->gracenote->user_id = null;
}

// iTunes API
define('ITUNES_API_END_POINT', 'https://itunes.apple.com/search');

require 'vendor/autoload.php';

use Gracenote\Rhythm\RhythmAPI;
use GuzzleHttp\Client;

$app = new \Slim\Slim();

// CORS
$app->response->headers->set('Access-Control-Allow-Origin', '*');

$api = new RhythmAPI($apikey->gracenote->client_id, $apikey->gracenote->user_id);

$app->get('/', function () use ($api) {
    echo json_encode([
        'status'=> 'ok',
        'user_id' => $api->user_id,
    ]);
});

/**
 * iThunes API
 * Get Params
 * term: 曲名
 * artist: アーティスト名で絞り込み、 correct_result に結果を出す
 *
 * Response JSON
 * status: "OK" or "ERROR"
 * correct_result: null or Object (term と artist が一致した曲が入る)
 * results: Array<Object> 検索結果の曲一覧
 */
$app->get('/itunes', function () use ($app) {
    $params = $app->request()->params();

    $params = array_merge([
        'country' => 'JP',
        'entity' => 'musicTrack',
        'attribute' => 'songTerm',
        'limit' => 10,
    ], $params);

    $client = new Client();
    $res = $client->get(ITUNES_API_END_POINT, [
        'query' => $params,
    ]);

    $data = json_decode($res->getBody());

    $correct_result = null;
    if (isset($params['artist'])) {
        $filtered_results = array_filter($data->results, function ($result) use ($params) {
            return mb_strrichr($result->artistName, $params['artist']) !== false;
        });
        if (count($filtered_results) > 0) {
            $correct_result = array_shift($filtered_results);
        }
    }

    echo json_encode([
        'status' => isset($data->results) && count($data->results) > 1 ? 'OK' : 'ERROR',
        'correct_result' => $correct_result,
        'results' => $data->results,
    ]);
});

/**
 * Gracenote Rhythm API - create
 *
 */
$app->get('/create', function () use ($api, $app) {
    $params = $app->request()->params();

    // Example
    // $res = $api->create([
    //     'artist_name' => 'EGOIST',
    //     'track_title' => '名前のない怪物',
    // ]);
    // track_title を指定する場合は artist_name は必須です

    $params = array_filter($params);
    $params = array_merge([
            'return_count' => 10,
            'select_extended' => 'cover',
    ], $params);
    $res = $api->create($params);

    echo json_encode([
        'status'=> $res->STATUS,
        'radio_id' => isset($res->RADIO) ? $res->RADIO[0]->ID : null,
        'album' => isset($res->ALBUM) ? $res->ALBUM : [],
    ]);
});

/**
 * Gracenote Rhythem API - event
 *
 * # event
 * - track_skipped - トラックに"skipped"をつける。 プレーキューが移動する
 * - track_like - トラックに"liked"をつける。プレーキューは移動しない
 * - track_dislike - トラックに"disliked"をつける。 プレーキューがリフレッシュされる
 * - artist_like - アーティストに"liked"をつける。プレーキューは移動しない
 * - artist_dislike - アーティストに"disliked"をつける。 プレーキューがリフレッシュされる
 */
$app->get('/feedback', function () use ($api, $app) {
    $params = $app->request()->params();

    $params = array_merge([
        'return_count' => 10,
        'select_extended' => 'cover',
    ], $params);
    // radio_id=RADIO_ID&event=track_played_12345-ABCDEF
    // radio_id=RADIO_ID&event=track_dislike_12345-ABCDEF;track_skipped_12345-ABCDEF
    $res = $api->feedback($params);

    echo json_encode([
        'status' => $res->STATUS,
        'radio_id' => isset($res->RADIO) ? $res->RADIO[0]->ID : null,
        'album' => isset($res->ALBUM) ? $res->ALBUM : [],
    ]);
});

$app->get('/fieldvalues', function () use ($api) {
    $genre = $api->fieldvalues('RADIOGENRE');
    $mood = $api->fieldvalues('RADIOMOOD');
    $era = $api->fieldvalues('RADIOERA');

    echo json_encode([
        'genre' => $genre->GENRE,
        'mood' => $mood->MOOD,
        'era' => $era->ERA,
    ]);
});

$app->get('/fields', function () use ($api) {
    echo json_encode($api->getFieldvalues());
});

$app->run();
