<?php

$api = true;
require "../_common.php";
require_once "../_conversation.php";

/*
 * GET /member-area/conversations.php
 * Sends the entire HTML of a conversation.
 * 
 * Parameters (URL) :
 * ?id : the conversation id
 * 
 * Returns : the entire HTML of a conversation.
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