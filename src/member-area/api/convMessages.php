<?php

$api = true;
require "../_common.php";
require_once "../_chatMessage.php";

/*
 * GET /member-area/api/convMessages.php
 * Renvoie l'HTML des messages d'une conversation, envoyés après un message choisi.
 * 
 * Paramètres (URL) : 
 * ?id : l'identifiant de la conversation
 * ?since : l'identifiant du dernier message vu, les messages avec un identifiant identique
 *              ou antérieur ne seront pas renvoyés (si non spécifié, tous les messages seront envoyés)
 * 
 * Retour :
 * 200 OK : l'HTML des messages
 *          les headers First-Message-Id et Last-Message-Id sont remplis avec leurs identifiants respectifs
 * 204 No Content : réponse vide, aucun message trouvé
 * 
 * POST /member-area/api/convMessages.php
 * Envoie un message dans la conversation, et renvoie tous les messages envoyés après un message choisi.
 * 
 * Paramètres (URL) :
 * ?id : l'identifiant de la conversation
 * ?since=int : voir la méthode GET
 * 
 * Paramètres (JSON) :
 * {
 *     "content" : string // le contenu du message
 * }
 * 
 * Retour :
 * 200 OK : l'HTML des messages, incluant le nouveau message (voir GET)
 */

if (empty($_GET["id"])) {
    bail(400);
}

$convId = $_GET["id"];
$conv = User\findConversation($user["id"], $convId);

if ($conv === null) {
    bail(404);
}

// Id du dernier message vu
$since = is_numeric($_GET["since"] ?? null) ? intval($_GET["since"]) : null;

function lastMessages(array $conv, ?int $since) {
    $first = null;
    $last = null;
    
    // Enregistrer l'HTML dans un string pour changer les headers
    // sinon PHP se plaint de headers déjà envoyés
    ob_start();
    
    foreach ($conv["messages"] as $msg) {
        if ($since === null || $msg["id"] > $since) {
            if ($first === null) { $first = $msg["id"]; }
            $last = $msg["id"];
            
            chatMessage($msg["id"], $msg["author"], $msg["content"]);
        }
    }

    if ($first !== null) {
        header("First-Message-Id: " . $first);
    }
    if ($last !== null) {
        header("Last-Message-Id: " . $last);
    }
    
    if ($first === null && $last === null) {
        bail(204); // No content
    }
    
    echo ob_get_clean();
}

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    lastMessages($conv, $since);
} else if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        bail(400);
    }

    $content = substr(trim($data["content"] ?? ""), 0, 2000);
    if (empty($content)) {
        bail(400);
    }

    $msgId = ConversationDB\addMessage($convId, $user["id"], $content, $conv);
    if ($msgId === false) {
        bail(500);
    }

    if ($since !== null) {
        lastMessages($conv, $since);
    } else {
        chatMessage($msgId, $user["id"], $content);
    }
} else {
    bail(405); // Method not allowed
}