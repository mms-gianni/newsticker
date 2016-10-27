<?php

require_once __DIR__.'/../vendor/autoload.php';


use Symfony\Component\HttpFoundation\Request;


$app = new Silex\Application();
$app['debug'] = true;

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));

$dburl = parse_url(getenv("CLEARDB_DATABASE_URL"));

$dbhost = $dburl["host"];
$dbuser = $dburl["user"];
$dbpass = $dburl["pass"];
$dbport = $dburl["port"];
$dbname = substr($dburl["path"], 1);

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array (
        'driver'    => 'pdo_mysql',
        'host'      => $dbhost,
        'dbname'    => $dbname,
        'user'      => $dbuser,
        'password'  => $dbpass,
        'port'      => $dbport,
        'charset'   => 'utf8mb4',
    ),
));


$app->get('/form/{channel}', function ($channel) use ($app) {
    return $app['twig']->render('form.twig', array(
        'channel' => $channel,
    ));
});


$app->get('/db/', function () use ($app) {
/*
    $sql = "SELECT * FROM messages LIMIT 10";
    $post = $app['db']->fetchAssoc($sql);
*/


        $message_arr = array(
            'body' => "testbody"
        );
        print_r($message_arr);
        $app['db']->insert('messages', $message_arr);
/*
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

    $muid = $app->escape($request->get('muid'));

    $message_arr = array(
        'title' => $app->escape($request->get('title')),
        'body' => $app->escape($request->get('body'))
    );

    if ($muid != '') {

        $row_arr = array(
            'channel' => $channel,
            'muid' => $muid
        );
        $app['db']->update('messages', $message_arr, $row_arr);
        $message_arr = array_merge($message_arr, $row_arr, array('action' => 'update'));
        //$message_arr = array_merge($message_arr, );
    } else {

        $message_arr_add = array(
            'channel' => $channel,
            'muid' => uniqid(),
            'time' => time()
        );
        $message_arr = array_merge($message_arr, $message_arr_add);
        $app['db']->insert('messages', $message_arr);
        $message_arr = array_merge($message_arr, array('action' => 'add'));
    }

    $message = json_encode($message_arr);
    $fanout = new Fanout\Fanout('f4ba43e0', '49R+AYRkC4HbwiC1EL1LOA==');
    $fanout->publish($channel, $message );
    return $message;
});

$app->post('/form/delete/{channel}/{muid}', function (Request $request, $channel, $muid) use ($app) {

    $message_arr = array(
        'muid' => $muid
    );
    $app['db']->delete('messages', $message_arr);
    $message_arr = array_merge($message_arr, array('action' => 'delete'));

    $message = json_encode($message_arr);
    $fanout = new Fanout\Fanout('f4ba43e0', '49R+AYRkC4HbwiC1EL1LOA==');
    $fanout->publish($channel, $message );
    return $message ;
});

$app->get('/api/initial/{channel}', function ($channel) use ($app) {

    $message = $app['db']->fetchAll('SELECT * FROM messages WHERE channel = ?', array($channel));
    return $app->json($message);
});



$app->run();
?>