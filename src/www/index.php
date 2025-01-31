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

if (empty($_POST['msg'])){$date='2023-11-24';} else {$date=$_POST['msg'];}

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
            div{float:left;clear:both;width:95vw;padding:0.25em 1em;border:1px solid #000;background-color:antiquewhite;}
            table{float:left;clear:none;margin:1rem 1rem 1rem 0;border:1px solid #aaa;box-shadow:3px 3px 10px #777;}
            caption{font-size:1.25rem;font-weight:bold;}
            input[type=file]{background-color:white;}
            input{cursor:pointer;}
        </style>
        </head>
        <body><form name="892d183ba51083fc2a0b3d4d6453e20b" id="892d183ba51083fc2a0b3d4d6453e20b" method="post" enctype="multipart/form-data">';
$html.='<h1>The Asset Object</h1>';
$html.='<div><label for="input">Test file upload</label><input type="text" value="'.$date.'" name="msg" id="input" style="margin:0.25em;"/><input type="submit" name="process" id="process" style="margin:0.25em;" value="Process"/></div>';
$html.='</form>';

$html.='<table>';
$html.='<caption>Currencies</caption>';
$rates=new Rates();
foreach($rates->getCurrencies() as $id=>$name){
    $html.='<tr><td>'.$id.'</td><td>'.$name.'</td></tr>';

}
$html.='</table>';

$html.='<table>';
$html.='<caption>Exchange rates</caption>';
foreach($rates->getRates(new \DateTime($date)) as $key=>$value){
    if (is_object($value)){
        $value=$value->format('Y-m-d');
    }
    $html.='<tr><td>'.$key.'</td><td>'.$value.'</td></tr>';
}
$html.='</table>';

$html.='<table>';
$html.='<caption>Asset instance</caption>';
$asset=new Asset(123.54,'$');
foreach($asset->getArray() as $key=>$value){
    if (is_object($value)){
        $value=$value->format('Y-m-d');
    }
    $html.='<tr><td>'.$key.'</td><td>'.$value.'</td></tr>';
}
$html.='</table>';

$html.='</body></html>';
echo $html;

?>