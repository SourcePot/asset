<?php
/*
* This file creates an HTML page as user interface to play with the asset class
* @package email
* @author Carsten Wallenhauer <admin@datapool.info>
* @copyright 2024 to today Carsten Wallenhauer
* @license https://opensource.org/license/mit MIT
*/
	
declare(strict_types=1);
	
namespace SourcePot\Asset;
	
mb_internal_encoding("UTF-8");

require_once('../php/Rates.php');
require_once('../php/Asset.php');

$ratesObj=new Rates();
$rates=$ratesObj->getCurrencies();

if (empty($_POST['value'])){$value='123.45';} else {$value=$_POST['value'];}
if (empty($_POST['unit'])){$unit='USD';} else {$unit=$_POST['unit'];}
if (empty($_POST['dateTime'])){$dateTime='2025-01-15T16:00:00';} else {$dateTime=$_POST['dateTime'];}

// compile html
$html='<!DOCTYPE html>
        <html xmlns="http://www.w3.org/1999/xhtml" lang="en">
        <head>
        <meta charset="utf-8">
        <title>Asset</title>
        <style>
            *{font-family: system-ui;font-size:12px;}
            h1{font-size:18px;}
            h2{font-size:16px;}
            tr:hover{background-color:#ccc;}
            td{border-left:1px dotted #444;padding:2px;}
            p{float:left;clear:both;}
            embed{float:left;clear:both;max-width:30vw;}
            div{float:left;clear:both;width:95vw;padding:0.25em 1em;}
            div.control{background-color:antiquewhite;border:1px solid #000;}
            table{float:left;clear:none;margin:1rem 1rem 1rem 0;border:1px solid #aaa;box-shadow:3px 3px 10px #777;}
            caption{font-size:1.25rem;font-weight:bold;}
            input[type=file]{background-color:white;}
            input{cursor:pointer;}
        </style>
        </head>
        <body><form name="892d183ba51083fc2a0b3d4d6453e20b" id="892d183ba51083fc2a0b3d4d6453e20b" method="post" enctype="multipart/form-data">';
$html.='<h1>Evaluation Page for the Asset-Package</h1>';
$html.='<div class="control"><h2>Asset properties for instantiation</h2>';
$html.='<input type="text" pattern="[0-9.]*" value="'.$value.'" name="value" id="value" style="margin:0.25em;"/>';
$html.='<select name="unit" id="unit">';
foreach($rates as $id=>$name){
    $selected=($id===$unit)?' selected':'';
    $html.='<option value="'.$id.'"'.$selected.'>'.$name.'</option>';
}
$html.='</select>';
$html.='<input type="datetime-local" value="'.$dateTime.'" name="dateTime" id="dateTime" style="margin:0.25em;"/>';
$html.='<input type="submit" name="set" id="set" style="margin:0.25em;" value="Set"/></div>';
$html.='</div>';
$html.='</form>';

// create asset
$asset=new Asset(floatval($value),$unit,new \DateTime($dateTime));

$html.='<table>';
$html.='<caption>Asset instance ['.$unit.']</caption>';
foreach($asset->getArray() as $key=>$value){
    if (is_object($value)){
        $value=$value->format('Y-m-d');
    }
    $html.='<tr><td>'.$key.'</td><td>'.$value.'</td></tr>';
}
$html.='</table>';

// derived asset
try {
    $unit='USD';
    $asset->setUnit($unit);
    $assetArr=$asset->getArray();
} catch (\Exception $e) {
    $assetArr=['Error'=>$e->getMessage()];
}
$html.='<table>';
$html.='<caption>Asset instance set unit to "'.$unit.'"</caption>';
foreach($assetArr as $key=>$value){
    if (is_object($value)){
        $value=$value->format('Y-m-d');
    }
    $html.='<tr><td>'.$key.'</td><td>'.$value.'</td></tr>';
}
$html.='</table>';

// add interest
$interestRate=4;
$years=20;
$steps=$asset->addIntrestYearly($interestRate,$years);
$html.='<table>';
$html.='<caption>Added interest at '.$interestRate.'% over '.$years.' years</caption>';
foreach($asset->getArray() as $key=>$value){
    if (is_object($value)){
        $value=$value->format('Y-m-d');
    }
    $html.='<tr><td>'.$key.'</td><td>'.$value.'</td></tr>';
}
$html.='</table>';

// add plot
$jsDataStr='';
$html.='<div><h3>Added interest at '.$interestRate.'% over '.$years.' years</h3><div id="myplot"></div></div>';
foreach($steps as $year=>$data){
    if (empty($jsDataStr)){
        $jsDataStr.='{Date: new Date("'.$data['dateTime'].'"), Interest:'.$data['interest'].', Amount:'.$data['value'].'}';
    } else {
        $jsDataStr.=',{Date: new Date("'.$data['dateTime'].'"), Interest:'.$data['interest'].', Amount:'.$data['value'].'}';
    }
}
$html.='<script>var data=['.$jsDataStr.'];</script>';
$html.='<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>';
$html.='<script src="index.js"></script>';
$html.='</body></html>';
echo $html;

?>