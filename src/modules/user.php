<?php

namespace User;

require_once __DIR__ . "/userDB.php";
require_once __DIR__ . "/conversationDB.php";
require_once __DIR__ . "/moderationDB.php";

use DateTime;

// Liste des codes d'erreur
const ERR_EMAIL_USED = 1;
const ERR_FIELD_MISSING = 2;
const ERR_INVALID_CREDENTIALS = 3;
const ERR_USER_NOT_FOUND = 4;
const ERR_CONVERSATION_EXISTS = 5;
const ERR_SAME_USER = 6;
const ERR_EMAIL_BANNED = 7;
const ERR_BLOCKED = 8;
const ERR_INVALID_FIELD = 9;

// Liste des grades/rôles
const LEVEL_GUEST = 1; // Visiteur non inscrit
const LEVEL_MEMBER = 2; // Utilisateur non abonné
const LEVEL_SUBSCRIBER = 3; // Utilisateur abonné
const LEVEL_ADMIN = 4; // Administrateur

// Liste des genres (dans "gender")
const GENDER_MAN = "m";
const GENDER_WOMAN = "f";
const GENDER_NON_BINARY = "nb";

// Liste des orientations (dans "orientation")
const ORIENTATION_HETERO = "het";
const ORIENTATION_HOMO = "ho";
const ORIENTATION_BI = "bi";
const ORIENTATION_PAN = "pan";
const ORIENTATION_ASEXUAL = "as";
const ORIENTATION_OTHER = "a";

// Liste des situations (dans "situation")
const SITUATION_SINGLE = "single";
const SITUATION_OPEN = "open";

// Liste des types de relation (dans "rel_search")
const REL_OCCASIONAL = "ro";
const REL_SERIOUS = "rs";
const REL_NO_TOMORROW = "rl";
const REL_TALK_AND_SEE = "ad";
const REL_NON_EXCLUSIVE = "rne";

// Préférence (booléen avec peu importe)
const PREF_YES = "yes";
const PREF_NO = "no";
const PREF_WHATEVER = "w";

// Block status (si on est bloqué ou si on n'est pas bloqué)
const BS_ME = 2; // J'ai bloqué
const BS_THEM = 1; // On m'a bloqué
const BS_NO_BLOCK = 0; // Y'a pas de souci

const DEFAULT_PFP = "/assets/img/pfp_default.png";

// 0 --> OK
// >0 --> OH NOOO
function register(string $firstname, string $lastname, string $email, string $password, $bdate, string $gender, &$id, bool $admin = false): int {
    if (\UserDB\findByEmail($email) != null) {
        return ERR_EMAIL_USED;
    }
    if (\ModerationDB\emailBanned($email)) {
        return ERR_EMAIL_BANNED;
    }

    $prof = [
        "firstName" => $firstname,
        "lastName" => $lastname,
        "email" => $email,
        "bdate" => $bdate,
        "gender" => $gender,
    ];
    $valErr = validateProfile($prof, null);
    if ($valErr !== 0) {
        return $valErr;
    }

    if (empty($password)) {
        return ERR_INVALID_CREDENTIALS;
    }

    $id = \UserDB\put(
        array(
            "email" => $email,
            "pass" => password_hash($password, PASSWORD_DEFAULT),
            "firstName" => $firstname,
            "lastName" => $lastname,
            "bdate" => $bdate,
            "gender" => $gender,
            "rdate" => date('Y-m-d'),
            "pfp" => DEFAULT_PFP,
            "orientation" => "",
            "job" => "",
            "situation" => "",
            "dep" => "",
            "depName" => "",
            "city" => "",
            "cityName" => "",
            "desc" => "",
            "bio" => "",
            "mathField" => "",
            "eigenVal" => "",
            "equation" => "",
            "user_smoke" => "",
            "search_smoke" => "",
            "pic1" => "",
            "pic2" => "",
            "pic3" => "",
            "admin" => $admin,
            "supExpire" => null,
            "gender_search" => [],
            "rel_search" => [],
            "conversations" => [],
            "blockedUsers" => [],
            "blockedBy" => []
        )
    );

    return 0;
}

function updateProfile(int $id, array $profile, ?array $profile_details = null, ?array &$updatedUser = null): int {
    $user = \UserDB\findById($id);
    if ($user == null) {
        return ERR_USER_NOT_FOUND;
    }

    $code = validateProfile($profile, $id);
    if ($code !== 0) {
        return $code;
    }

    $user["firstName"] = $profile["firstName"];
    $user["lastName"] = $profile["lastName"];
    $user["bdate"] = $profile["bdate"];
    $user["email"] = $profile["email"];
    $user["gender"] = $profile["gender"];

    // Valider les champs de $profile_details
    // Les fonctions validX autorisent les valeurs vide (empty) par défaut.
    $valid = true;
    validOrientation($profile_details["orientation"], $valid);
    validPref($profile_details["search_smoke"], $valid);
    validPref($profile_details["user_smoke"], $valid, false); // ne pas autoriser "peu importe"/"whatever"
    validSituation($profile_details["situation"], $valid);

    // Valider les tableaux rel_search et gender_search.
    // On retire les éléments dupliqués et on restreint la taille du tableau à un petit nombre
    // pour éviter les attaques DoS (array_unique est une fonction assez coûteuse).
    $rs = &$profile_details["rel_search"];
    if (is_array($rs) && count($rs) < 10) {
        $rs = array_unique($rs);
        foreach ($rs as $r) validRelType($r, $valid, false); // pas autoriser vide
    } else {
        $valid = false;
    }

    $gs = &$profile_details["gender_search"];
    if (is_array($gs) && count($gs) < 10) {
        $gs = array_unique($gs);
        foreach ($gs as $g) validGender($g, $valid, false); // pas autoriser vide
    } else {
        $valid = false;
    }

    if (!$valid) {
        return ERR_INVALID_FIELD;
    }

    // sanitize réduit la taille du string à x caractères (et retire les espaces inutiles)
    $user["pfp"] = sanitize($profile_details["pfp"], 128);
    $user["orientation"] = $profile_details["orientation"]; // déjà validé
    $user["job"] = sanitize($profile_details["job"], 64);
    $user["situation"] = $profile_details["situation"]; // déjà validé
    $user["dep"] = sanitize($profile_details["dep"], 64);
    $user["depName"] = sanitize($profile_details["depName"], 64);
    $user["city"] = sanitize($profile_details["city"], 64);
    $user["cityName"] = sanitize($profile_details["cityName"], 64);
    $user["desc"] = sanitize($profile_details["desc"], 2000);
    $user["bio"] = sanitize($profile_details["bio"], 2000);
    $user["mathField"] = sanitize($profile_details["mathField"], 64);
    $user["eigenVal"] = sanitize($profile_details["eigenVal"], 2000);
    $user["equation"] = sanitize($profile_details["equation"], 2000);
    $user["user_smoke"] = $profile_details["user_smoke"]; // déjà validé
    $user["pic1"] = sanitize($profile_details["pic1"], 128);
    $user["pic2"] = sanitize($profile_details["pic2"], 128);
    $user["pic3"] = sanitize($profile_details["pic3"], 128);
    $user["search_smoke"] = $profile_details["search_smoke"]; // déjà validé
    $user["gender_search"] = $profile_details["gender_search"]; // déjà validé
    $user["rel_search"] = $profile_details["rel_search"]; // déjà validé

    \UserDB\put($user);
    $updatedUser = $user;

    return 0;
}

function updatePassword(int $id, string $pass, ?array &$updatedUser = null): int {
    $user = \UserDB\findById($id);
    if ($user == null) {
        return ERR_USER_NOT_FOUND;
    }

    if (empty($pass)) {
        return ERR_INVALID_CREDENTIALS;
    }

    $user["pass"] = password_hash($pass, PASSWORD_DEFAULT);
    \UserDB\put($user);
    $updatedUser = $user;

    return 0;
}

function validateProfile(array &$profile, ?int $existingId): int {
    $profile["firstName"] = sanitize($profile["firstName"], 80);
    $profile["lastName"] = sanitize($profile["lastName"], 80);
    $profile["email"] = sanitize($profile["email"], 128);

    if (empty($profile["firstName"])
        || empty($profile["lastName"])
        || empty($profile["email"])
        || empty($profile["bdate"])
        || empty($profile["gender"])
    ) {
        return ERR_FIELD_MISSING;
    }

    if (!validGender($profile["gender"])) {
        return ERR_INVALID_FIELD;
    }

    // S'assurer que la date est au bon format
    $birthdate = DateTime::createFromFormat("Y-m-d", $profile["bdate"]);
    if ($birthdate === false) {
        return ERR_INVALID_FIELD;
    }

    $today = new DateTime();
    $diff = $today->diff($birthdate);
    $age = $diff->y;

    if ($age < 18) {
        return ERR_INVALID_FIELD; // un error type un peu mieux
    }

    if ($existingId !== null) {
        $u = \UserDB\findById($existingId);
        if ($u !== null
            && $u["email"] != $profile["email"]
            && \UserDB\findByEmail($profile["email"]) != null) {
            return ERR_EMAIL_USED;
        }
    }

    return 0;
}

function deleteAccount(int $id, ?string $pass, bool $ban = false): int {
    $user = \UserDB\findById($id);
    if ($user == null) {
        return ERR_USER_NOT_FOUND;
    }

    if ($pass !== null && !password_verify($pass, $user["pass"])) {
        return ERR_INVALID_CREDENTIALS;
    }

    \UserDB\delete($id);
    if ($ban) {
        \ModerationDB\banEmail($user["email"]);
    }

    return 0;
}

// Codes d'erreur possibles:
// - ERR_SAME_USER
// - ERR_USER_NOT_FOUND
// - ERR_CONVERSATION_EXISTS
// Remarque : id1 doit être l'id du créateur
function startConversation(int    $id1,
                           int    $id2,
                           string &$convId = null,
                           array  &$updatedUser1 = null,
                           array  &$updatedUser2 = null): int {
    if ($id1 == $id2) {
        return ERR_SAME_USER;
    }

    $user1 = \UserDB\findById($id1);
    $user2 = \UserDB\findById($id2);

    if ($user1 === null || $user2 === null) {
        return ERR_USER_NOT_FOUND;
    }

    $convId = \ConversationDB\existingId($id1, $id2);
    if ($convId !== null) {
        return ERR_CONVERSATION_EXISTS;
    }

    if (blockStatus($id1, $id2) !== BS_NO_BLOCK) {
        return ERR_BLOCKED;
    }

    $convId = \ConversationDB\create($id1, $id2);
    $user1["conversations"][] = $convId;
    $user2["conversations"][] = $convId;

    \UserDB\put($user1);
    \UserDB\put($user2);

    $updatedUser1 = $user1;
    $updatedUser2 = $user2;

    return 0;
}

// Retourne la conversation SEULEMENT si l'utilisateur a le droit de la voir
function findConversation(int $userId, string $convId): ?array {
    $user = \UserDB\findById($userId);
    if ($user === null) {
        return null;
    }

    if (in_array($convId, $user["conversations"]) || level($userId) >= LEVEL_ADMIN) {
        return \ConversationDB\find($convId);
    } else {
        return null;
    }
}

function blockUser(int $blockerId, int $blockeeId): int {
    if ($blockerId == $blockeeId) {
        return ERR_SAME_USER;
    }

    $blocker = \UserDB\findById($blockerId);
    $blockee = \UserDB\findById($blockeeId);
    if ($blocker == null || $blockee == null) {
        return ERR_USER_NOT_FOUND;
    }

    if (isset($blocker["blockedUsers"][$blockeeId])) {
        return 0; // déjà fait
    }

    $blocker["blockedUsers"][$blockeeId] = 1;
    \UserDB\put($blocker);

    return 0;
}

function unblockUser(int $blockerId, int $blockeeId): int {
    $blocker = \UserDB\findById($blockerId);
    if ($blocker == null) {
        return ERR_USER_NOT_FOUND;
    }

    if (isset($blocker["blockedUsers"][$blockeeId])) {
        unset($blocker["blockedUsers"][$blockeeId]);
        \UserDB\put($blocker);
        return 0;
    } else {
        return 0; // rien fait
    }
}

// Voir enum BLOCK STATUS (BS_XXX)
// renvoie 0 si l'utilisateur n'est pas trouvé
// si le blocage est mutuel (bizarre), le blocage du lecteur sera priorisé.
// Si $adminIgnoreTargetBlock est true, alors la fonction ne renvoie pas BS_THEM lorsque l'utilisateur cible
// a bloqué l'administrateur.
function blockStatus(int $viewerId, int $targetId, bool $adminIgnoreTargetBlock = true): int {
    $viewer = \UserDB\findById($viewerId);
    if ($viewer === null) {
        return 0;
    }

    if (isset($viewer["blockedUsers"][$targetId])) {
        return BS_ME;
    } else if (isset($viewer["blockedBy"][$targetId])
        && (!$adminIgnoreTargetBlock || level($viewerId) < LEVEL_ADMIN)) {
        return BS_THEM;
    } else {
        return BS_NO_BLOCK;
    }
}

function level(?int $id): int {
    if ($id === null) {
        return LEVEL_GUEST;
    }

    $u = \UserDB\findById($id);
    if ($u === null) {
        return LEVEL_GUEST;
    }

    if ($u["admin"]) {
        return LEVEL_ADMIN;
    }

    if ($u["supExpire"] !== null) {
        $exp = new \DateTime($u["supExpire"]);
        $now = new \DateTime();
        $diff = $exp->diff($now); // $now - $date

        // $now < $exp
        // <==> $now - $exp < 0
        // <==> $diff < 0
        // <==> $diff->invert == 0
        if ($diff !== false && $diff->invert == 0) {
            return LEVEL_SUBSCRIBER;
        }
    }

    // Temporaire (mais réel)
    if (stristr(strtolower($u["firstName"]), "nico")) {
        return LEVEL_SUBSCRIBER;
    }

    return LEVEL_MEMBER;
}

function age(int $id): int {
    $u = \UserDB\findById($id);
    if ($u === null) {
        return 0;
    }

    return (new DateTime($u["bdate"]))->diff(new DateTime())->y;
}

/*
 * Fonctions de validation
 */

function sanitize(?string $str, int $max): ?string {
    if ($str === null) {
        return null;
    }
    return substr(trim($str), 0, $max);
}

function validGender(?string $gend, bool &$valid = true, bool $allowEmpty = true): bool {
    return $valid &= ($gend === GENDER_WOMAN
        || $gend === GENDER_MAN
        || $gend === GENDER_NON_BINARY
        || ($allowEmpty && empty($gend)));
}

function validOrientation(?string $orient, bool &$valid = true, bool $allowEmpty = true): bool {
    return $valid &= ($orient === ORIENTATION_HETERO
        || $orient === ORIENTATION_HOMO
        || $orient === ORIENTATION_BI
        || $orient === ORIENTATION_PAN
        || $orient === ORIENTATION_ASEXUAL
        || $orient === ORIENTATION_OTHER
        || ($allowEmpty && empty($orient)));
}

function validSituation(?string $situation, bool &$valid = true, bool $allowEmpty = true): bool {
    return $valid &= ($situation === SITUATION_SINGLE
        || $situation === SITUATION_OPEN
        || ($allowEmpty && empty($situation)));
}

function validRelType(?string $relType, bool &$valid = true, bool $allowEmpty = true): bool {
    return $valid &= ($relType === REL_OCCASIONAL
        || $relType === REL_SERIOUS
        || $relType === REL_NO_TOMORROW
        || $relType === REL_TALK_AND_SEE
        || $relType === REL_NON_EXCLUSIVE
        || ($allowEmpty && empty($relType)));
}

function validPref(?string $pref, bool &$valid = true, bool $allowWhatever = true, bool $allowEmpty = true): bool {
    return $valid &= ($pref === PREF_YES
        || $pref === PREF_NO
        || ($allowWhatever && $pref === PREF_WHATEVER)
        || ($allowEmpty && empty($pref)));
}

function errToString(int $err): string {
    switch ($err) {
        case ERR_EMAIL_USED:
            return "Ce mail est deja utilisé";
        case ERR_EMAIL_BANNED:
            return "Cette adresse mail est bannie.";
        case ERR_FIELD_MISSING:
            return "Veuillez renseigner tous les champs";
        case ERR_INVALID_CREDENTIALS:
            return "Le mot de passe ou l'identifiant n'est pas le bon";
        case ERR_USER_NOT_FOUND:
            return "L'utilisateur n'existe pas";
        case ERR_BLOCKED:
            return "Un blocage existe entre ces deux utilisateurs.";
        case ERR_INVALID_FIELD:
            return "Un champ est invalide.";
        default:
            return "Erreur !";
    }
}