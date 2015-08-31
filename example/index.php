<?php

// Gracenote API
define('GRACENOTE_CLIENT_ID', '7514112-DACDA96EDADA824BDA8735B8921CDEC7');
// iTunes API
define('ITUNES_API_END_POINT', 'https://itunes.apple.com/search?country=JP&entity=song');

require 'vendor/autoload.php';

use Gracenote\Rhythm\RhythmAPI;
use GuzzleHttp\Client;

$app = new \Slim\Slim();
$api = new RhythmAPI(GRACENOTE_CLIENT_ID);

$app->get('/', function () use ($api) {
    echo json_encode([
        'status'=> 'ok',
        'client_id' => $api->user_id,
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

    $client = new Client();
    $res = $client->get(ITUNES_API_END_POINT, [
        'query' => [
            'country' => 'JP',
            'entity' => 'musicTrack',
            'term' => $params['term'],
            'limit' => 10,
        ]
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
    $res = $api->create($params);

    echo json_encode([
        'status'=> $res->STATUS,
        'album' => isset($res->ALBUM) ? $res->ALBUM : [],
    ]);
});

/**
 * Gracenote Rhythem API - event
 *
 * # event
 * - track_skipped - トラックに”skipped”をつける。 プレーキューが移動する
 * - track_like - トラックに"liked"をつける。プレーキューは移動しない
 * - track_dislike - トラックに”disliked”をつける。 プレーキューがリフレッシュされる
 * - artist_like - アーティストに”liked”をつける。プレーキューは移動しない
 * - artist_dislike - アーティストに”disliked”をつける。 プレーキューがリフレッシュされる
 */
$app->get('/feedback', function () use ($api, $app) {
    $params = $app->request()->params();

    //

    // radio_id=RADIO_ID&event=track_played_12345-ABCDEF
    $res = $api->feedback(/**********/);

    echo json_encode([
        'status' => $res->STATUS,
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