<?php

$api = true;
require "../_common.php";
require_once "../_chatMessage.php";
require_once "../../modules/moderationDB.php";

/**
 * POST /member-area/api/reports.php
 * Ajoute un signalement d'un message dans une conversation
 *
 * Entrée (JSON) :
 * {
 *     "convId": string, // L'id de conversation
 *     "msgId": int // L'id du message
 *     "reason": string // La justification du signalement
 * } 
 * 
 * Retour :
 * 200 OK
 * 
 * DELETE /member-area/api/reports.php
 * Supprime un signalement (admin uniquement)
 * 
 * Entrée :
 * ?id : l'id du signalement
 * 
 * Retour :
 * 200 OK
 */

UserSession\requireLevel(User\LEVEL_SUBSCRIBER);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        bail(400);
    }

    $convId = $data["convId"] ?? null;
    if (!is_string($convId)) {
        bail(400);
    }
    
    $msgId = $data["msgId"] ?? null;
    if (!is_int($msgId)) {
        bail(400);
    }
    
    $reason = trim($data["reason"] ?? "");
    if (empty($reason)) {
        bail(400);
    }
    
    $conv = ConversationDB\find($data["convId"]);
    if ($conv === null) {
        bail(404);
    }
    
    foreach ($conv["messages"] as $msg) {
        if ($msg["id"] === $data["msgId"]) {
            if ($msg["author"] === $user["id"]) {
                bail(403);
            }
            
            ModerationDB\addReport($convId, $msgId, $user["id"], $reason);
            exit;
        }
    }
    
    bail(404);
} else if ($_SERVER["REQUEST_METHOD"] === "DELETE") {
    if (User\level($user["id"]) < User\LEVEL_ADMIN) {
        bail(400);
    }

    $id = $_GET["id"] ?? null;
    if (!is_numeric($id)) {
        bail(400);
    }
    
    if (!ModerationDB\deleteReport(intval($id))) {
        bail(404);
    }
}
else {
    bail(400);
}