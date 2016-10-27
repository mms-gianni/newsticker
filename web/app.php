<?php

require_once __DIR__.'/../vendor/autoload.php';


use Symfony\Component\HttpFoundation\Request;


$app = new Silex\Application();
$app['debug'] = true;

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array (
        'driver'    => 'pdo_mysql',
        'host'      => 'mysql_read.someplace.tld',
        'dbname'    => 'my_database',
        'user'      => 'my_username',
        'password'  => 'my_password',
        'charset'   => 'utf8mb4',
    ),
));


$app->get('/form/{channel}', function ($channel) use ($app) {
    return $app['twig']->render('form.twig', array(
        'channel' => $channel,
    ));
});


$app->get('/db/', function () use ($app) {

    $mongo = new MongoDB\Driver\Manager("mongodb://peter:dermeter@ds031257.mlab.com:31257/newsticker");
    var_dump($mongo);

    /*$bulk = new MongoDB\Driver\BulkWrite;
    $muid = $bulk->insert(['x' => 1]);
    var_dump("insert ".$muid);
    $return = $mongo->executeBulkWrite('newsticker.gugus', $bulk);
    var_dump($return);*/
    return "";
});

$app->post('/form/submit/{channel}', function ( Request $request, $channel) use ($app) {

    $mongo = new MongoDB\Driver\Manager("mongodb://peter:dermeter@ds031257.mlab.com:31257/newsticker");
    $bulk = new MongoDB\Driver\BulkWrite;

    $title = $request->get('title');
    $body = $request->get('body');
    $muid = $request->get('muid');
    $time = time();

    if ($muid != '') {
        $bulk->update(
            [
            /*    "_id" => [
                    "$oid" => $muid
                ]*/
                'muid' => $app->escape($muid),
            ],
            [
                'title' => $app->escape($title),
                'body' => $app->escape($body)
            ]);

        $message  = '{"muid":"'.$app->escape($muid).'",';
        $message .= '"action":"update",';
    } else {
        $muid = $bulk->insert([
                'muid' => $app->escape($muid),
                'time' => $app->escape($time),
                'title' => $app->escape($title),
                'body' => $app->escape($body)
            ]);

        $message  = '{"muid":"'.$muid.'",';
        $message .= '"action":"add",';
    }

    $mongo->executeBulkWrite('newsticker.'.$channel, $bulk);

    $message .= '"time":"'.time().'",';
    $message .= '"title":"'.$app->escape($title).'",';
    $message .= '"body":"'.$app->escape($body).'"}';

    $fanout = new Fanout\Fanout('f4ba43e0', '49R+AYRkC4HbwiC1EL1LOA==');
    $fanout->publish($channel, $message );
    return $message ;
});

$app->post('/form/delete/{channel}/{muid}', function (Request $request, $channel, $muid) use ($app) {

    $message  = '{"muid":"'.$muid.'",';
    $message .= '"action":"delete"}';

    $fanout = new Fanout\Fanout('f4ba43e0', '49R+AYRkC4HbwiC1EL1LOA==');
    $fanout->publish($channel, $message );
    return $message ;
});

$app->get('/api/initial/{channel}', function ($channel) use ($app) {

    $mongo = new MongoDB\Driver\Manager("mongodb://peter:dermeter@ds031257.mlab.com:31257/newsticker");
    //$filter = ['x' => ['$gt' => 1]];
    $filter = [];
    $options = [
        'projection' => ['_id' => 0],
        'sort' => ['time' => 1],
    ];

    $query = new MongoDB\Driver\Query($filter, $options);

    $result = $mongo->executeQuery('newsticker.'.$channel, $query);
    header('Content-Type: application/json');
    return $app->json(json_encode($result->toArray()));
});



$app->run();
?>