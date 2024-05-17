import { api } from "./api.js";
import { typeset } from "./math.js";

const CONV_UPDATE_INTERVAL = 1000; // ms

/** @param {HTMLElement} element */
function initChatBox(element) {
    const state = {
        people: element.querySelector(".chat-people"),
        conversationSlot: element.querySelector(".-conversation-slot"),
        // L'élement HTML de la personne choisie (la partie de gauche)
        selectedPerson: null,
        // L'élément HTML de la conversation sélectionnée
        conversation: null,
        // Identifiant pour éviter de charger une conversation qu'on doit plus charger
        // (Par exemple quand on clique sur une conversation, puis une autre,
        // et que la requête de la 1ere est complétée après la 2e) 
        curConvLoadId: 0,

        init() {
            this.selectedPerson = this.people.querySelector(".chat-person.-selected");

            this.updatePersonLastMsgListen = this.updatePersonLastMsg.bind(this);
            this.updateConvFromSlot();

            // Changer de conversation quand une personne de la liste est cliquée
            this.people.addEventListener("click", e => {
                const p = e.target.closest(".chat-person");
                if (p !== null && p.dataset.id != null) {
                    // Alors on a cliqué sur une conversation
                    this.selectPerson(p, p.dataset.id);
                }
            })

            element.querySelector("#create-conv")?.addEventListener("click", function () {
                alert('C\'est pas implémenté... va dans profil et clique sur "Démarrer une discussion"');
            })
        },

        // Change de personne sélectionnée et charge la nouvelle conversation dans le slot
        selectPerson(element, id) {
            if (this.selectedPerson !== null && this.selectedPerson.dataset.id === id) {
                // Même conversation sélectionnée, ne rien faire
                return;
            }

            this.selectedPerson?.classList.remove("-selected");
            this.selectedPerson = element;
            element.classList.add("-selected");

            this.loadConv(id);
        },

        // Charge une conversation depuis le serveur, qui renvoie l'HTML de la conversation
        async loadConv(id) {
            const loadId = ++this.curConvLoadId;
            const html = await api.getConversation(id)
            if (loadId === this.curConvLoadId) {
                this.conversationSlot.innerHTML = html;
                this.updateConvFromSlot()
            }
        },

        // Initialise la conversation qui est dans le slot et ajoute/retire les listeners. 
        updateConvFromSlot() {
            const newConv = this.conversationSlot.firstElementChild;
            if (this.conversation === newConv) {
                return;
            } else if (this.conversation !== null) {
                // Retirer le listener de la conversation précédente
                this.conversation.removeEventListener("lastMessageUpdated", this.updatePersonLastMsgListen);
            }

            this.conversation = this.conversationSlot.firstElementChild;
            if (this.conversation != null) {
                initConversation(this.conversation);
                this.conversation.addEventListener("lastMessageUpdated", this.updatePersonLastMsgListen);
            }
        },

        // fonction qui est appelée quand la conversation reçoit un message
        // (on a besoin de deux variables sinon impossible de retirer le listener)
        updatePersonLastMsgListen: null,
        updatePersonLastMsg(e) {
            const txt = e.detail.txt;
            this.selectedPerson.querySelector(".-last-msg").textContent = txt;
        }
    }

    state.init();
    element.chatState = state;
}

/** @param {HTMLElement} element */
export function initConversation(element) {
    const id = element.dataset.id;
    if (id == null) {
        return;
    }

    const state = {
        id,
        lastSeenMsgId: null,
        updateHandle: null,
        elems: {
            root: element,
            messages: element.querySelector(".-messages"),
            msgForm: element.querySelector(".-send"),
            msgInput: element.querySelector(".-msg-input")
        },

        init() {
            if (this.elems.msgForm !== null) {
                this.elems.msgForm.addEventListener("submit", e => {
                    e.preventDefault();
                    this.postMessage();
                });
            }

            if (this.elems.messages.children.length > 0) {
                this.lastSeenMsgId = this.elems.messages.lastElementChild.dataset.id;
            }

            // Supprimer un message quand on clique sur le bouton "supprimer"
            // Ou lancer un signalement si on clique sur "signaler"
            this.elems.messages.addEventListener("click", e => {
                if (e.target.classList.contains("-delete")) {
                    // trouver le message parent
                    const msg = e.target.closest(".chat-message");
                    if (msg != null) {
                        if (confirm("Voulez-vous vraiment supprimer ce message ?")) {
                            this.deleteMessage(msg);
                        }
                    }
                } else if (e.target.classList.contains("-report")) {
                    const msg = e.target.closest(".chat-message");
                    if (msg != null) {
                        let r = prompt("Écrivez en quoi ce message est problématique afin de le signaler.")
                        r = r?.trim(); // retirer les espaces en trop
                        if (r) { // pas vide, pas null
                            this.reportMessage(msg, r);
                        }
                    }
                }
            })

            // Planifier la maj 
            this.updateHandle = setTimeout(this.updateTick.bind(this), CONV_UPDATE_INTERVAL);

            this.scrollToBottom();

            typeset(() => [this.elems.root]);
        },

        postMessage() {
            const content = this.elems.msgInput.value;
            // On évite d'envoyer un message vide
            if (content.trim() === "") {
                return;
            }

            // On retire le message de l'input
            this.elems.msgInput.value = "";

            // On envoie le message au serveur.
            api.sendMessage(this.id, content, this.lastSeenMsgId)
                .then(res => this.receiveMessages(res));
        },

        receiveMessages({ html, firstMsgId, lastMsgId }) {
            console.log(`Receiving messages: [${firstMsgId}, ${lastMsgId}]`);

            // Pour savoir si on doit scroll tout en bas après ou non
            const scrollDown = this.scrollCloseEnough();
            // On met l'HTML reçu à la fin de la liste des messages
            this.elems.messages.insertAdjacentHTML("beforeend", html);

            // Parcourir tous les messages envoyés par le serveur pour rafraîchir les équations.
            // Et aussi upprimer les doublons (peut arriver si on a les requêtes dans le désordre)     
            
            const messages = [];
            for (let i = this.elems.messages.children.length - 1; i >= 0; i--) {
                const msg = this.elems.messages.children[i];
                const msgId = msg.dataset.id;

                // Ancien message, déjà vu, donc faut supprimer
                if (this.lastSeenMsgId !== null && msgId <= this.lastSeenMsgId) {
                    msg.remove();
                } else {
                    messages.push(msg);
                }

                // Fin des messages envoyés par le serveur
                if (msg.dataset.id <= firstMsgId) {
                    break;
                }
            }
            // Rafraîchir mathJax
            typeset(()=>messages);
            
            this.lastSeenMsgId = lastMsgId

            // On envoie un événement pour mettre à jour le dernier message affiché sur la liste
            // des personnes.
            this.elems.root.dispatchEvent(new CustomEvent("lastMessageUpdated", {
                detail: {
                    txt: this.elems.messages.lastElementChild.querySelector(".-content").textContent
                }
            }))

            if (scrollDown) {
                this.scrollToBottom();
            }
        },

        deleteMessage(msgElement) {
            const msgId = parseInt(msgElement.dataset.id);
            api.deleteMessage(this.id, msgId)
                .then(() => msgElement.remove());
        },

        reportMessage(msgElement, reason) {
            const msgId = parseInt(msgElement.dataset.id);
            api.reportMessage(this.id, msgId, reason)
                .then(() => alert("Signalement envoyé !"));
        },

        // Renvoie true si le scroll est suffisamment proche de la fin des messages
        scrollCloseEnough() {
            const m = this.elems.messages;
            return m.scrollHeight - m.clientHeight - m.scrollTop < 50;
        },

        // Scrolle tout en bas des messages
        scrollToBottom() {
            const m = this.elems.messages;
            m.scrollTo(0, m.scrollHeight);
        },

        // Met à jour la liste des messages
        async updateTick() {
            // Si on a été retiré de l'écran, on arrête de faire des requêtes
            if (!this.alive) {
                return;
            }

            try {
                // Recevoir les messages de l'API (voir convMessages.php)
                const m = await api.getMessages(this.id, this.lastSeenMsgId);
                if (m !== null && this.alive) {
                    // Il y en a (et on est tjrs affiché), alors ajouter ces messages
                    this.receiveMessages(m);
                }
            } finally {
                // On relance la mise à jour après un certain temps (si on est tjrs affiché)
                if (this.alive) {
                    this.updateHandle = setTimeout(() => this.updateTick(), CONV_UPDATE_INTERVAL);
                }
            }
        },

        // False si la conversation n'est plus affichée à l'écran
        // (donc si on a changé de conversation)
        get alive() {
            return this.elems.root.isConnected;
        }
    }

    state.init();
    element.convState = state;
}

/**
 * Boîte de dialogue Conversation
 * Utilise un élément personnalisé (custom element) pour afficher une conversation dans une boîte de dialogue.
 */
const convDialogTemplate = document.createElement("template");
convDialogTemplate.innerHTML = `
<link rel="stylesheet" href="/assets/style.css">
<style>
#dialog {
    display: flex;
    flex-direction: column;
    padding: 0;

    & > .-header {
        display: flex;
        & > .-title {
            margin: 4px 8px;
            margin-bottom: 2px;
            flex-grow: 1;
            font-size: 1.33em;
        }
        & > .-close {
            border: none;
            background-color: transparent;
            & > .-icon {
                display: block;
                margin: auto 0;
                font-size: 1.5em;
                color: white; 
                pointer-events: none; 
            }
            &:hover {
                background-color: rgba(255, 255, 255, 0.3);
            }
            &:active {
                background-color: rgba(255, 255, 255, 0.2);
            }
        }

        background-color: #a30000;
        color: white;
    }

    & > .-slot {
        display: contents;
    }

    border: 1px solid rgb(129, 129, 129);
    border-radius: 4px;

    height: 95%;
    width: 90%;

    box-shadow: 0px 0px 5px 1px rgba(0, 0, 0, 0.2);

    /** Mobile */
    @media (max-width: 768px) {
        width: 98%;
    }
}

#dialog:not([open]) {
    display: none;
}

::slotted(.chat-conversation) {
    flex-grow: 1;
    padding: 8px;
    overflow: auto;
}
</style>
<dialog id="dialog">
    <form class="-header" method="dialog">
    <h2 class="-title">Conversation</h2>
    <button class="-close"><div class="material-symbols-rounded -icon">close</div> </button>
    </form>
    <slot></slot>
</dialog>
`;
export class ConversationDialog extends HTMLElement {
    constructor() {
        super();
        this.dom = this.attachShadow({ mode: "open" });
        this.dom.replaceChildren(convDialogTemplate.content.cloneNode(true));
        this.dialog = this.dom.getElementById("dialog");
        this.dialog.addEventListener("close", () => this.remove());
    }

    show(html) {
        this.dialog.showModal();
        this.innerHTML = html;
        initConversation(this.querySelector(".chat-conversation"));
    }
}
customElements.define("conversation-dialog", ConversationDialog);

export async function fetchAndOpenConv(convId) {
    const conversationHTML = await api.getConversation(convId);
    const dialog = document.body.appendChild(new ConversationDialog());
    dialog.show(conversationHTML);
}

/*
Initialisation de la boîte de chat
*/

const box = document.getElementById("chat-box")
if (box) {
    initChatBox(box);
}