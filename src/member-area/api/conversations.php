<?php

$api = true;
require "../_common.php";
require_once "../_conversation.php";

/*
 * GET /member-area/conversations.php
 * Renvoie l'HTML d'une conversation.  
 * 
 * Paramètres (URL) :
 * ?id : l'identifiant de la conversation
 * 
 * Retour : l'HTML complet de la conversation
 */

UserSession\requireLevel(User\LEVEL_SUBSCRIBER);

$convId = $_GET["id"];
$conv = User\findConversation($user["id"], $convId);

if ($conv === null) {
    conversation(null, null);
    bail(404);
} else {
    conversation($convId, $user["id"]);
}