<?php

require 'classes/Session.class.php';
require 'classes/Ranking.class.php';
require 'classes/Forum.class.php';

$session  = new Session();
$ranking = new Ranking();
$forum = new Forum();

$ranking->updateTimePlayed();

$forum->logout();


$session->logout();



if($session->issetFBLogin()){
    
    require_once 'classes/Facebook.class.php';

    $facebook = new Facebook(array(
        'appId' => 'REDACTED',
        'secret' => 'REDACTED'
    ));

    $facebook->destroySession();
    
}

header("Location:index.php");
exit();