<?php

// 2019: This never worked. I ended up using Paypal. I'm keeping this here anyway

require 'config.php';
require $_SERVER['DOCUMENT_ROOT'].'/classes/Session.class.php';
require $_SERVER['DOCUMENT_ROOT'].'/classes/Player.class.php';
require $_SERVER['DOCUMENT_ROOT'].'/classes/System.class.php';
require $_SERVER['DOCUMENT_ROOT'].'/classes/Premium.class.php';

$session = new Session();
$system = new System();
$premium = new Premium();

if($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST)){
    exit();
}

if(!isset($_POST['id']) || !isset($_POST['fingerprint'])){
    exit();
}

require 'pagarme-php/Pagarme.php';
Pagarme::setApiKey("hegame123");

$premium->debug();

$id = $_POST['id'];
$fingerprint = $_POST['fingerprint'];


if(!PagarMe::validateFingerprint($id, $fingerprint)) {
    //TODO: report
    exit();
}

$status = $_POST['current_status'];

if($status == 'paid'){
    
    $premium->setAsPaid($id);
    
} else {    
    
    if(isset($_POST['refuse_reason'])){
        $reason = $_POST['refuse_reason'];
    } else {
        $reason = '';
    }
    
    $premium->refused($id, $reason);
    
}