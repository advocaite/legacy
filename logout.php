<?php

require $_SERVER['DOCUMENT_ROOT'].'/classes/Session.class.php';
require $_SERVER['DOCUMENT_ROOT'].'/classes/Ranking.class.php';
require $_SERVER['DOCUMENT_ROOT'].'/classes/Forum.class.php';

$session  = new Session();
$ranking = new Ranking();


$ranking->updateTimePlayed();

//$forum->logout();


$session->logout();



if($session->issetFBLogin()){
    
    require_once $_SERVER['DOCUMENT_ROOT'].'/classes/Facebook.class.php';

    $facebook = new Facebook(array(
        'appId' => 'hegame123',
        'secret' => 'hegame123'
    ));

    $facebook->destroySession();
    
}

header("Location:index.php");
exit();